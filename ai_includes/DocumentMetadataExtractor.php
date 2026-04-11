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
            // Suppress XML warnings
            libxml_use_internal_errors(true);
            
            $dom = new DOMDocument();
            $dom->loadXML($xml_content);
            
            // Get all text nodes from paragraphs
            $xpath = new DOMXPath($dom);
            
            // Register namespace
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            
            // Extract text from all text elements
            $text_nodes = $xpath->query('//w:t');
            
            $full_text = '';
            foreach ($text_nodes as $node) {
                $full_text .= $node->nodeValue . ' ';
            }
            
            libxml_use_internal_errors(false);
            
            return trim($full_text);
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
     * Uses heuristics to identify title, authors, year, and abstract
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
        
        // Split into lines for better parsing
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines, function($line) { return !empty($line); });
        $lines = array_values($lines);
        
        // ===== EXTRACT TITLE =====
        // Look for the first substantial line (title)
        // Titles are usually: long, capitalized, not too many numbers
        for ($i = 0; $i < min(10, count($lines)); $i++) {
            $line = $lines[$i];
            
            // Skip very short lines, metadata headers
            if (strlen($line) < 20) continue;
            if (stripos($line, 'college') !== false || stripos($line, 'faculty') !== false) continue;
            if (stripos($line, 'mindanao institute') !== false) continue;
            
            // Check if line has mostly uppercase or capitalized words
            $uppercase_ratio = preg_match_all('/[A-Z]/', $line) / strlen($line);
            
            if ($uppercase_ratio > 0.2) { // At least 20% uppercase
                $metadata['title'] = $line;
                $title_index = $i;
                break;
            }
        }
        
        // ===== EXTRACT AUTHORS =====
        // Look for names after title, usually all caps or Title Case
        // Names often appear on consecutive lines
        if (isset($title_index)) {
            $author_candidates = [];
            
            for ($i = $title_index + 1; $i < min($title_index + 15, count($lines)); $i++) {
                $line = $lines[$i];
                
                // Stop if we hit keywords like "Abstract", "ABSTRACT", "Course", etc.
                if (preg_match('/^(ABSTRACT|Abstract|COURSE|Course|Year|JUNE|Bachelor|BACHELOR)/i', $line)) {
                    break;
                }
                
                // Stop if it's an institution name
                if (stripos($line, 'institute') !== false || stripos($line, 'university') !== false) {
                    continue;
                }
                
                // Check if line looks like an author name
                // Author names: multiple words, at least one uppercase word, no common keywords
                if (preg_match('/[A-Z][a-z]+\s+[A-Z]/', $line) && strlen($line) < 100) {
                    if (!preg_match('/presented|submitted|edited|designed|developed/i', $line)) {
                        $author_candidates[] = $line;
                    }
                }
            }
            
            // Join multiple author lines
            foreach ($author_candidates as $author) {
                // Skip if already too many authors
                if (count($metadata['authors']) >= 6) break;
                
                // Clean up author name
                $author = preg_replace('/\s+/', ' ', $author);
                $author = trim($author);
                
                if (!empty($author) && strlen($author) > 2) {
                    $metadata['authors'][] = $author;
                }
            }
        }
        
        // ===== EXTRACT YEAR =====
        // Look for 4-digit year (1900-2100)
        if (preg_match('/\b(19|20)\d{2}\b/', $text, $year_match)) {
            $metadata['year'] = $year_match[1];
        }
        
        // ===== EXTRACT ABSTRACT =====
        // Look for ABSTRACT section
        $abstract_pattern = '/(?:ABSTRACT|Abstract|SUMMARY|Summary)\s*\n(.+?)(?:\n(?:INTRODUCTION|Introduction|KEYWORDS|Keywords|CHAPTER|Chapter|\d+\.|[A-Z][A-Z ]+)|\Z)/is';
        
        if (preg_match($abstract_pattern, $text, $abstract_match)) {
            $abstract = trim($abstract_match[1]);
            
            // Clean up and limit length
            $abstract = preg_replace('/\s+/', ' ', $abstract);
            $abstract = trim($abstract);
            
            // Limit to first 500 characters
            if (strlen($abstract) > 500) {
                $abstract = substr($abstract, 0, 500) . '...';
            }
            
            $metadata['abstract'] = $abstract;
        }
        
        // Join authors into a single string if present
        if (!empty($metadata['authors'])) {
            $metadata['authors'] = implode(', ', $metadata['authors']);
        } else {
            $metadata['authors'] = '';
        }
        
        return $metadata;
    }
}

?>
