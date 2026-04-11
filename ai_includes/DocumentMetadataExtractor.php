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
     * @return array Metadata (title, authors, year, abstract)
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
            return self::parseMetadataFromText($text);
            
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
            
            return self::parseMetadataFromText($content);
        } catch (Exception $e) {
            return ['error' => 'Failed to extract PDF metadata: ' . $e->getMessage()];
        }
    }
    
    /**
     * Extract text from PDF (basic method)
     * @param string $file_path Path to PDF file
     * @return string Extracted text
     */
    private static function extractTextFromPdf($file_path) {
        try {
            $content = file_get_contents($file_path);
            
            // Extract text streams from PDF
            // Look for text objects (BT...ET blocks)
            $text = '';
            
            // Pattern to find text content
            if (preg_match_all('/BT\s*(.+?)\s*ET/s', $content, $matches)) {
                foreach ($matches[1] as $block) {
                    // Extract strings in parentheses
                    if (preg_match_all('/\((.*?)\)\s*T[jdf]/s', $block, $strings)) {
                        foreach ($strings[1] as $str) {
                            // Decode PDF string escape sequences
                            $str = preg_replace('/\\\([0-7]{1,3})/', chr(intval('$1', 8)), $str);
                            $str = preg_replace('/\\\n/', '', $str);
                            $text .= $str . ' ';
                        }
                    }
                }
            }
            
            return trim($text);
        } catch (Exception $e) {
            return '';
        }
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
            'abstract' => ''
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
        
        error_log("Parsed metadata: Title=" . substr($metadata['title'], 0, 50) . " | Year=" . $metadata['year'] . " | Authors=" . substr($metadata['authors'], 0, 50));
        
        return $metadata;
    }
}

?>
