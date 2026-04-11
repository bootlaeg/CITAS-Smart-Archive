<?php
/**
 * Enhanced Document Metadata Extractor
 * Extracts title, authors, year, and abstract from DOCX and PDF files
 * Citas Smart Archive System
 */

class DocumentMetadataExtractor {
    
    /**
     * Extract metadata from document file
     * @param string $file_path Full path to document file
     * @param string $file_type File extension (pdf, docx, doc)
     * @return array Extracted metadata or empty array on failure
     */
    public static function extract($file_path, $file_type = 'docx') {
        if (!file_exists($file_path)) {
            return ['error' => 'File not found'];
        }
        
        $file_type = strtolower($file_type);
        
        switch ($file_type) {
            case 'docx':
                return self::extractFromDocx($file_path);
            case 'doc':
                return self::extractFromDoc($file_path);
            case 'pdf':
                return self::extractFromPdf($file_path);
            default:
                return ['error' => 'Unsupported file type'];
        }
    }
    
    /**
     * Extract metadata from DOCX file
     * @param string $file_path Path to DOCX file
     * @return array Metadata (title, authors, year, abstract, page_count)
     */
    private static function extractFromDocx($file_path) {
        try {
            // DOCX is a ZIP file, open it
            $zip = new ZipArchive();
            if ($zip->open($file_path) !== true) {
                return ['error' => 'Failed to open DOCX file'];
            }
            
            // Read the main document XML
            $xml_content = $zip->getFromName('word/document.xml');
            $zip->close();
            
            if (!$xml_content) {
                return ['error' => 'No document content found'];
            }
            
            // Extract all text from XML
            $text = self::extractTextFromWordXml($xml_content);
            
            // Parse metadata from extracted text
            $metadata = self::parseMetadataFromText($text);
            
            // Extract page count
            $page_count = self::extractPageCountFromDocx($file_path);
            if ($page_count) {
                $metadata['page_count'] = $page_count;
            }
            
            return $metadata;
            
        } catch (Exception $e) {
            error_log("DOCX extraction error: " . $e->getMessage());
            return ['error' => 'Failed to extract DOCX metadata: ' . $e->getMessage()];
        }
    }
    
    /**
     * Extract text from Word XML
     * @param string $xml_content XML content from word/document.xml
     * @return string Extracted text
     */
    private static function extractTextFromWordXml($xml_content) {
        try {
            libxml_use_internal_errors(true);
            
            // Try method 1: Parse with namespace support
            $dom = new DOMDocument();
            
            // First, try parsing as-is with namespace handling
            if (@$dom->loadXML($xml_content)) {
                $xpath = new DOMXPath($dom);
                $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                
                // Try to get text elements with namespace
                $text_nodes = $xpath->query('//w:t');
                
                if ($text_nodes && $text_nodes->length > 0) {
                    $full_text = '';
                    foreach ($text_nodes as $node) {
                        $full_text .= $node->nodeValue . ' ';
                    }
                    
                    $result = trim($full_text);
                    if (strlen($result) > 50) {
                        libxml_use_internal_errors(false);
                        return $result;
                    }
                }
            }
            
            // Method 2: Remove namespaces and try again
            $xml_clean = preg_replace('/<(\w+):([^>]*)\s+xmlns[^=]*="[^"]*"/i', '<$1:$2', $xml_content);
            $xml_clean = preg_replace('/ xmlns[^=]*="[^"]*"/i', '', $xml_clean);
            
            $dom = new DOMDocument();
            if (@$dom->loadXML($xml_clean)) {
                $xpath = new DOMXPath($dom);
                
                // Try without namespace
                $text_nodes = $xpath->query('//t');
                
                if ($text_nodes && $text_nodes->length > 0) {
                    $full_text = '';
                    foreach ($text_nodes as $node) {
                        $full_text .= $node->nodeValue . ' ';
                    }
                    
                    $result = trim($full_text);
                    if (strlen($result) > 50) {
                        libxml_use_internal_errors(false);
                        return $result;
                    }
                }
            }
            
            // Method 3: Extract using simple string patterns
            // Look for text in <w:t>...</w:t> tags
            if (preg_match_all('/<w:t[^>]*>([^<]*)<\/w:t>/u', $xml_content, $matches)) {
                $full_text = implode(' ', $matches[1]);
                $result = trim($full_text);
                if (strlen($result) > 50) {
                    libxml_use_internal_errors(false);
                    return $result;
                }
            }
            
            // Method 4: Extract all text-like content
            if (preg_match_all('>([^<>{]+)</w:t>|<w:t[^>]*>([^<]*)</', $xml_content, $matches)) {
                $text_parts = array_merge($matches[1] ?? [], $matches[2] ?? []);
                $full_text = implode(' ', array_filter($text_parts));
                $result = trim($full_text);
                if (strlen($result) > 20) {
                    libxml_use_internal_errors(false);
                    return $result;
                }
            }
            
            libxml_use_internal_errors(false);
            return '';
            
        } catch (Exception $e) {
            error_log("Word XML parsing error: " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Extract metadata from DOC file
     * Limited support - older binary format
     * @param string $file_path Path to DOC file
     * @return array Metadata
     */
    private static function extractFromDoc($file_path) {
        // DOC is binary format, harder to parse
        // Try to read as text stream and extract patterns
        try {
            $content = file_get_contents($file_path);
            
            // Remove binary garbage, keep ASCII readable text
            $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $content);
            $content = preg_replace('/\s+/', ' ', $content);
            
            return self::parseMetadataFromText($content);
        } catch (Exception $e) {
            return ['error' => 'DOC format support is limited. Please use DOCX or PDF.'];
        }
    }
    
    /**
     * Extract metadata from PDF file
     * @param string $file_path Path to PDF file
     * @return array Metadata
     */
    private static function extractFromPdf($file_path) {
        try {
            // Try to extract text from PDF
            $content = self::extractTextFromPdf($file_path);
            
            if (empty($content)) {
                return ['error' => 'Unable to extract text from PDF'];
            }
            
            $metadata = self::parseMetadataFromText($content);
            
            // Extract page count
            $page_count = self::extractPageCountFromPdf($file_path);
            if ($page_count) {
                $metadata['page_count'] = $page_count;
            }
            
            return $metadata;
        } catch (Exception $e) {
            return ['error' => 'Failed to extract PDF metadata: ' . $e->getMessage()];
        }
    }
    
    /**
     * Extract text from PDF (handles FlateDecode compression)
     * @param string $file_path Path to PDF file
     * @return string Extracted text
     */
    private static function extractTextFromPdf($file_path) {
        try {
            $content = file_get_contents($file_path);
            $text = '';
            
            // Step 1: Try to extract from uncompressed BT...ET blocks first
            if (preg_match_all('/BT\s*(.+?)\s*ET/s', $content, $matches)) {
                foreach ($matches[1] as $block) {
                    $text .= self::extractTextFromTextBlock($block) . ' ';
                }
            }
            
            // Step 2: Extract and decompress FlateDecode streams using /Length to determine stream size
            // Use a regex that captures both the length and the stream marker position
            if (preg_match_all('/\/Length\s+(\d+).*?stream[\r\n]+/s', $content, $stream_matches, PREG_OFFSET_CAPTURE)) {
                foreach ($stream_matches[0] as $idx => $full_match) {
                    $stream_length = intval($stream_matches[1][$idx][0]);
                    $match_text = $full_match[0];
                    $match_pos = $full_match[1];
                    
                    // The stream data starts right after "stream" and the line ending(s)
                    $stream_start = $match_pos + strlen($match_text);
                    
                    // Extract exactly $stream_length bytes
                    if ($stream_start + $stream_length <= strlen($content)) {
                        $stream_data = substr($content, $stream_start, $stream_length);
                        
                        // Try to decompress
                        $decompressed = self::decompressPdfStream($stream_data);
                        
                        if ($decompressed && !empty(trim($decompressed))) {
                            // Extract text from decompressed content
                            if (preg_match_all('/BT\s*(.+?)\s*ET/s', $decompressed, $text_matches)) {
                                foreach ($text_matches[1] as $block) {
                                    $extracted = self::extractTextFromTextBlock($block);
                                    if (!empty(trim($extracted))) {
                                        $text .= $extracted . ' ';
                                    }
                                }
                            }
                            
                            // Also try to extract text operators directly
                            if (preg_match_all('/\(([^)]{5,})\)\s*Tj/', $decompressed, $tj_matches)) {
                                foreach ($tj_matches[1] as $str) {
                                    $decoded = self::decodePdfString($str);
                                    if (trim($decoded) !== '' && strlen(trim($decoded)) > 2) {
                                        $text .= $decoded . ' ';
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            // Step 3: If still no text, try alternative text extraction
            if (strlen(trim($text)) < 100) {
                if (preg_match_all('/\(([^\(\)]{10,200})\)/', $content, $matches)) {
                    foreach ($matches[1] as $str) {
                        if (self::isLikelyTextContent($str)) {
                            $text .= $str . ' ';
                        }
                    }
                }
            }
            
            return trim($text);
        } catch (Exception $e) {
            error_log("PDF text extraction error: " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Decompress a PDF stream using FlateDecode
     * @param string $stream_data Raw stream data
     * @return string|null Decompressed data or null if decompression fails
     */
    private static function decompressPdfStream($stream_data) {
        try {
            // Don't pass empty data
            if (empty($stream_data)) {
                return null;
            }
            
            // Remove trailing whitespace/control characters that aren't part of the compressed data
            // PDF streams can have extra whitespace on their boundaries
            $stream_data = rtrim($stream_data, " \t\n\r\0");
            
            if (empty($stream_data)) {
                return null;
            }
            
            // Try gzuncompress first (for zlib wrapper) - works for most PDFs
            set_error_handler(function($errno, $errstr) {
                // Suppress error output
            });
            
            $decompressed = gzuncompress($stream_data);
            
            if ($decompressed !== false) {
                restore_error_handler();
                return $decompressed;
            }
            
            restore_error_handler();
            
            // Try gzinflate (for raw deflate data)
            set_error_handler(function($errno, $errstr) {
                // Suppress error output
            });
            
            $decompressed = gzinflate($stream_data);
            
            restore_error_handler();
            
            if ($decompressed !== false) {
                return $decompressed;
            }
            
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Extract text from a PDF text block (BT...ET section)
     * @param string $block Text block content
     * @return string Extracted text
     */
    private static function extractTextFromTextBlock($block) {
        $text = '';
        
        // PDF text can be fragmented across multiple text positioning commands
        // Text appears in TJ (Show Text with Positioning) and Tj (Show Text) operators
        // Format: TJ [(String1) offset (String2) offset ...] TJ
       
        // Extract all TJ array text operators - these contain the actual visible text
        // Pattern: [...text strings and numbers...] TJ
        if (preg_match_all('/\[([^\[\]]*?)\]\s*TJ/s', $block, $tj_matches)) {
            foreach ($tj_matches[1] as $tj_array) {
                // Extract all parenthesized strings from this TJ array (in order)
                if (preg_match_all('/\(([^()\\\\]*(?:\\\\.[^()\\\\]*)*)\)/', $tj_array, $str_matches)) {
                    foreach ($str_matches[1] as $str) {
                        $decoded = self::decodePdfString($str);
                        // Concatenate directly (PDF handles spacing with positioning)
                        $text .= $decoded;
                    }
                }
            }
            // Add space after each TJ operation
            $text = trim($text) . ' ';
        }
        
        // Also try simple Tj operators (show text without positioning array)
        if (preg_match_all("/\\(([^()\\\\]*(?:\\\\.[^()\\\\]*)*)\\)\\s*Tj/", $block, $matches)) {
            foreach ($matches[1] as $str) {
                $decoded = self::decodePdfString($str);
                if (trim($decoded) !== '') {
                    $text .= $decoded . ' ';
                }
            }
        }
        
        return trim($text);
    }
    
    /**
     * Decode PDF string escape sequences
     * @param string $str PDF encoded string
     * @return string Decoded string
     */
    private static function decodePdfString($str) {
        // Remove PDF escape sequences
        $str = preg_replace('/\\\\([0-7]{1,3})/', chr(intval('$1', 8)), $str);
        $str = preg_replace('/\\\\n/', "\n", $str);
        $str = preg_replace('/\\\\r/', "\r", $str);
        $str = preg_replace('/\\\\t/', "\t", $str);
        $str = preg_replace('/\\\\/', '', $str);
        
        return $str;
    }
    
    /**
     * Check if a string is likely text content (not metadata)
     * @param string $str String to check
     * @return bool True if likely text content
     */
    private static function isLikelyTextContent($str) {
        // Filter out strings that look like binary data or metadata
        if (strlen($str) < 10) return false;
        
        // Count printable ASCII characters
        $printable = preg_match_all('/[\x20-\x7E\n\r\t]/', $str);
        $total = strlen($str);
        
        // If more than 50% are printable, consider it text
        return ($printable / $total) > 0.5;
    }
    
    /**
     * Parse metadata from text content
     * @param string $text Full document text
     * @return array Metadata with keys: title, authors, year, abstract
     */
    private static function parseMetadataFromText($text) {
        $metadata = [
            'title' => '',
            'authors' => [],
            'year' => '',
            'abstract' => '',
            'degree' => ''
        ];
        
        // First extract year and abstract from raw text BEFORE collapsing whitespace
        
        // ===== EXTRACT YEAR =====
        if (preg_match('/\b(19|20)(\d{2})\b/', $text, $year_match)) {
            $metadata['year'] = $year_match[1] . $year_match[2];
        }
        
        // ===== EXTRACT ABSTRACT =====
        // Look for the ABSTRACT section with proper content (not table of contents)
        // Extract the FULL abstract without truncation
        $abstract_patterns = [
            // Pattern 1: ABSTRACT keyword followed by real content ending at Keywords or ACKNOWLEDGMENT
            '/ABSTRACT\s+([A-Z][\s\S]{100,}?)(?:Keywords|KEYWORDS|ACKNOWLEDGMENT|Chapter|$)/i',
        ];
        
        foreach ($abstract_patterns as $pattern) {
            if (preg_match($pattern, $text, $abstract_match)) {
                $abstract = trim($abstract_match[1]);
                // Clean up and validate
                $abstract = preg_replace('/\s+/', ' ', $abstract);
                
                // Must be substantial and contain neurological/medical content
                if (strlen($abstract) > 150 && preg_match('/(disease|disorder|patient|study|clinical|system)/i', $abstract)) {
                    $metadata['abstract'] = $abstract;
                    break;
                }
            }
        }
        
        // ===== EXTRACT TITLE (before collapsing whitespace) =====
        // Look for the first capitalized phrase before "Research" or "Presented"
        $title_patterns = [
            // Pattern 1: Title followed by : or line break, before "Research/Capstone" or "Presented"
            '/^([A-Z][A-Za-z0-9\s:&\(\),-]+?)(?:\s+A\s+Research|\s+A\s+Capstone|\s+Presented|\n)/m',
            // Pattern 2: NeuroGuard specific pattern
            '/^([A-Z][A-Za-z0-9\s:&\(\),-]+?)\s+A\s+(?:Research|Capstone)/m',
            // Pattern 3: Title before "Presented to"
            '/^([A-Z][A-Za-z0-9\s:&\(\),-]+?)\s+Presented\s+to/m',
        ];
        
        foreach ($title_patterns as $pattern) {
            if (preg_match($pattern, $text, $title_match)) {
                $title = trim($title_match[1]);
                // Remove extra whitespace
                $title = preg_replace('/\s+/', ' ', $title);
                if (strlen($title) > 20) {
                    $metadata['title'] = $title;
                    break;
                }
            }
        }
        
        // ===== EXTRACT AUTHORS =====
        // Look for author patterns in thesis documents
        // Pattern 1: ALL CAPS LASTNAME, Mixed Case Names INITIAL. (like "DELGADO, MARCO ANTONIO R.")
        
        $found_authors = [];
        
        // Try to find authors in the format: "LASTNAME, FIRSTNAME MIDDLENAMES INITIAL."
        // This catches patterns like "DELGADO, MARCO ANTONIO R." or "FONTANILLA, CARLA BEATRIZ T."
        if (preg_match_all('/([A-Z]{2,}),\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]*)*)\s+([A-Z][A-Z]\.?)\b/', $text, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $lastname = trim($matches[1][$i]);
                $firstname = trim($matches[2][$i]);
                $initial = trim($matches[3][$i]);
                
                // Reconstruct as "FirstName LastName Initial"
                $author = $firstname . ' ' . $lastname . ' ' . $initial;
                $author = preg_replace('/\s+/', ' ', trim($author));
                
                // Validate - should be a realistic name
                if (strlen($author) > 6 && str_word_count($author) >= 2) {
                    if (!preg_match('/JUNE|MAY|APRIL|DEAN|FACULTY|COLLEGE|BACHELOR|DEGREE|APPROVAL|RESEARCH/i', $author)) {
                        if (!in_array($author, $found_authors)) {
                            $found_authors[] = $author;
                            if (count($found_authors) >= 6) break;
                        }
                    }
                }
            }
        }
        
        // Fallback: Look for names after "submitted by" or "prepared and submitted by"
        if (count($found_authors) < 2) {
            if (preg_match_all('/submitted by\s+([A-Za-z\s,.]+?)(?:,\s+in partial|in partial)/is', $text, $matches)) {
                $names_string = $matches[1][0];
                // Split on comma and "and"
                $individual_names = preg_split('/,\s+(?:and\s+)?/', $names_string, 6);
                foreach ($individual_names as $name) {
                    $name = trim(preg_replace('/\s+/', ' ', $name));
                    if (strlen($name) > 5 && !in_array($name, $found_authors)) {
                        $found_authors[] = $name;
                    }
                }
            }
        }
        
        // Join authors
        $metadata['authors'] = !empty($found_authors) ? implode(', ', array_slice($found_authors, 0, 6)) : '';
        
        // Extract degree/major
        $metadata['degree'] = self::extractDegreeFromText($text);
        
        error_log("Parsed metadata: Title=" . substr($metadata['title'], 0, 50) . " | Year=" . $metadata['year'] . " | Authors=" . substr($metadata['authors'], 0, 50) . " | Degree=" . $metadata['degree']);
        
        return $metadata;
    }
    
    /**
     * Extract degree/major from document text
     * Looks for patterns like "BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY"
     * @param string $text Document text
     * @return string Degree and major or empty string
     */
    private static function extractDegreeFromText($text) {
        // Patterns for degree types - ordered from most to least specific
        $degree_patterns = [
            // Most specific: Bachelor of Science in MAJOR
            '/BACHELOR\s+OF\s+SCIENCE\s+IN\s+([A-Z\s]{3,}?)(?:\s+(?:DELGADO|FONTANILLA|REYES|SANTOS|MAGBANUA|VILLANUEVA|DELOS|SANTOS|BY|[A-Z]{2,}\s*,)|\n|$)/i',
            
            // Bachelor of MAJOR (with Arts, Music, etc.)
            '/BACHELOR\s+OF\s+([A-Z\s]{3,}?)(?:\s+(?:DELGADO|FONTANILLA|REYES|SANTOS|MAGBANUA|VILLANUEVA|DELOS|SANTOS|BY|IN|[A-Z]{2,}\s*,)|\n|$)/i',
            
            // B.S. in MAJOR
            '/B\.?S\.?\s+(?:IN\s+)?([A-Z\s]{3,}?)(?:\s+(?:DELGADO|FONTANILLA|REYES|SANTOS|MAGBANUA|VILLANUEVA|DELOS|SANTOS|BY|[A-Z]{2,}\s*,)|\n|$)/i',
            
            // Master degrees
            '/MASTER\s+OF\s+([A-Z\s]{3,}?)(?:\s+(?:DELGADO|FONTANILLA|REYES|SANTOS|MAGBANUA|VILLANUEVA|DELOS|SANTOS|BY|[A-Z]{2,}\s*,)|\n|$)/i',
            
            // M.A. in MAJOR
            '/M\.?A\.?\s+(?:IN\s+)?([A-Z\s]{3,}?)(?:\s+(?:DELGADO|FONTANILLA|REYES|SANTOS|MAGBANUA|VILLANUEVA|DELOS|SANTOS|BY|[A-Z]{2,}\s*,)|\n|$)/i',
            
            // Alternative patterns
            '/For\s+the\s+Degree\s+of\s+([A-Z][A-Za-z\s]{3,}?)(?:\s+(?:DELGADO|FONTANILLA|REYES|SANTOS|MAGBANUA|VILLANUEVA|DELOS|SANTOS|BY)|\n|$)/i',
        ];
        
        foreach ($degree_patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $degree = trim($matches[1]);
                // Clean up the degree string
                $degree = preg_replace('/\s+/', ' ', $degree);
                $degree = trim($degree, ' .,');
                
                // Must be reasonable length and likely degree-related
                if (strlen($degree) > 3 && strlen($degree) < 100) {
                    return $degree;
                }
            }
        }
        
        return '';
    }
    
    /**
     * Extract page count from DOCX file (from document properties)
     * @param string $file_path Path to DOCX file
     * @return int|null Page count or null if not found
     */
    private static function extractPageCountFromDocx($file_path) {
        try {
            $zip = new ZipArchive();
            if ($zip->open($file_path) !== true) {
                return null;
            }
            
            // Try to read app.xml which contains page count
            $app_xml = $zip->getFromName('docProps/app.xml');
            $zip->close();
            
            if ($app_xml) {
                libxml_use_internal_errors(true);
                $dom = new DOMDocument();
                if (@$dom->loadXML($app_xml)) {
                    $xpath = new DOMXPath($dom);
                    
                    // Register the namespace
                    $xpath->registerNamespace('ep', 'http://schemas.openxmlformats.org/officeDocument/2006/extended-properties');
                    
                    // Try to get Pages element with namespace
                    $pages = $xpath->query('//ep:Pages');
                    
                    if ($pages && $pages->length > 0) {
                        $page_count = intval($pages->item(0)->nodeValue);
                        if ($page_count > 0) {
                            libxml_use_internal_errors(false);
                            return $page_count;
                        }
                    }
                    
                    // Try without namespace in case namespace prefix is different
                    $pages = $xpath->query('//*[local-name()="Pages"]');
                    if ($pages && $pages->length > 0) {
                        $page_count = intval($pages->item(0)->nodeValue);
                        if ($page_count > 0) {
                            libxml_use_internal_errors(false);
                            return $page_count;
                        }
                    }
                }
                libxml_use_internal_errors(false);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Page count extraction error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Extract page count from PDF file
     * @param string $file_path Path to PDF file
     * @return int|null Page count or null if not found
     */
    private static function extractPageCountFromPdf($file_path) {
        try {
            $content = file_get_contents($file_path);
            
            if (!$content) {
                return null;
            }
            
            // Look for the Root object and Pages reference
            // PDF structure: catalogs usually have /Type /Catalog and /Pages reference
            // Pages object has /Kids array with individual page references
            
            // Try to find /Type /Catalog and extract /Pages
            if (preg_match('/\/Type\s*\/Catalog.*?\/Pages\s*(\d+)\s+0\s+R/s', $content, $matches)) {
                $pages_obj_num = $matches[1];
                
                // Now find the Pages object and count /Kids
                if (preg_match("/$pages_obj_num\\s+0\\s+obj.*?\\/Type\s*\\/Pages.*?\\/Kids\s*\\[([^\\]]+)\\]/s", $content, $pages_matches)) {
                    $kids = $pages_matches[1];
                    // Count the number of references (each looks like "N 0 R")
                    preg_match_all('/(\d+)\s+0\s+R/', $kids, $page_refs);
                    $page_count = count($page_refs[0]);
                    if ($page_count > 0) {
                        return $page_count;
                    }
                }
            }
            
            // Fallback: Count all page objects (objects with /Type /Page)
            preg_match_all('/\/Type\s*\/Page[^s]/', $content, $matches);
            if (count($matches[0]) > 0) {
                return count($matches[0]);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("PDF page count extraction error: " . $e->getMessage());
            return null;
        }
    }
}
