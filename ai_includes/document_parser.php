<?php
/**
 * Document Parser - Extracts text from PDF, DOC, and DOCX files
 * Supports: PDF, DOC, DOCX (Max 20MB)
 */

class DocumentParser
{
    const MAX_FILE_SIZE = 20 * 1024 * 1024; // 20MB
    const SUPPORTED_FORMATS = ['pdf', 'doc', 'docx', 'txt'];

    /**
     * Parse document and extract text
     * 
     * @param string $filePath Path to the uploaded file
     * @param string $originalFileName Optional original filename for format detection
     * @return array ['success' => bool, 'text' => string, 'error' => string]
     */
    public static function extractText($filePath, $originalFileName = '')
    {
        // Validate file exists
        if (!file_exists($filePath)) {
            return ['success' => false, 'text' => '', 'error' => 'File not found'];
        }

        // Validate file size
        $fileSize = filesize($filePath);
        if ($fileSize > self::MAX_FILE_SIZE) {
            return ['success' => false, 'text' => '', 'error' => 'File exceeds 20MB limit'];
        }

        // Get file extension - prefer original filename, fallback to temp file
        $pathForExtension = !empty($originalFileName) ? $originalFileName : $filePath;
        $fileExtension = strtolower(pathinfo($pathForExtension, PATHINFO_EXTENSION));
        
        // If still no extension, try MIME type detection
        if (empty($fileExtension)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            
            error_log("No file extension found, MIME type: $mimeType");
            
            // Map MIME types to extensions
            $mimeMap = [
                'application/pdf' => 'pdf',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                'text/plain' => 'txt'
            ];
            
            $fileExtension = $mimeMap[$mimeType] ?? '';
        }

        error_log("Detected file extension: $fileExtension");

        // Route to appropriate parser
        switch ($fileExtension) {
            case 'pdf':
                return self::extractFromPdf($filePath);
            case 'docx':
                return self::extractFromDocx($filePath);
            case 'doc':
                return self::extractFromDoc($filePath);
            case 'txt':
                return self::extractFromTxt($filePath);
            default:
                return ['success' => false, 'text' => '', 'error' => 'Unsupported file format: ' . $fileExtension];
        }
    }

    /**
     * Extract text from PDF using DocumentMetadataExtractor (supports FlateDecode compression)
     */
    private static function extractFromPdf($filePath)
    {
        try {
            // Use our superior DocumentMetadataExtractor which handles compressed PDFs
            require_once __DIR__ . '/DocumentMetadataExtractor.php';
            
            $metadata = DocumentMetadataExtractor::extract($filePath, 'pdf');
            
            // Combine all extracted text for keyword analysis
            $text = '';
            
            // Add title if available
            if (!empty($metadata['title'])) {
                $text .= $metadata['title'] . "\n\n";
            }
            
            // Add authors if available
            if (!empty($metadata['authors'])) {
                $text .= "Authors: " . $metadata['authors'] . "\n\n";
            }
            
            // Add abstract (most important for keywords)
            if (!empty($metadata['abstract'])) {
                $text .= "Abstract: " . $metadata['abstract'] . "\n\n";
            }
            
            if (!empty($text)) {
                error_log("PDF extraction via DocumentMetadataExtractor: extracted " . strlen($text) . " bytes (title: " . strlen($metadata['title'] ?? '') . ", abstract: " . strlen($metadata['abstract'] ?? '') . ")");
                return ['success' => true, 'text' => $text, 'error' => ''];
            }
            
            error_log("PDF extraction resulted in empty text");
            return ['success' => false, 'text' => '', 'error' => 'Could not extract text from PDF'];
        } catch (Exception $e) {
            error_log("PDF parsing exception: " . $e->getMessage());
            return ['success' => false, 'text' => '', 'error' => 'PDF parsing error: ' . $e->getMessage()];
        }
    }

    /**
     * Extract text from DOCX files
     */
    private static function extractFromDocx($filePath)
    {
        try {
            // Try using PHPOffice if available
            if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                require_once __DIR__ . '/../vendor/autoload.php';
                
                if (class_exists('PhpOffice\PhpWord\IOFactory')) {
                    $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
                    $text = '';
                    
                    foreach ($phpWord->getSections() as $section) {
                        foreach ($section->getElements() as $element) {
                            if (method_exists($element, 'getText')) {
                                $text .= $element->getText() . " ";
                            }
                        }
                    }
                    
                    return ['success' => true, 'text' => $text, 'error' => ''];
                }
            }

            // Fallback: ZIP extraction method
            $text = self::docxZipExtraction($filePath);
            if (!empty($text)) {
                return ['success' => true, 'text' => $text, 'error' => ''];
            }

            return ['success' => false, 'text' => '', 'error' => 'Could not extract text from DOCX'];
        } catch (Exception $e) {
            return ['success' => false, 'text' => '', 'error' => 'DOCX parsing error: ' . $e->getMessage()];
        }
    }

    /**
     * Extract text from DOC files (legacy Office format)
     */
    private static function extractFromDoc($filePath)
    {
        try {
            // Try using PHPOffice if available
            if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                require_once __DIR__ . '/../vendor/autoload.php';
                
                if (class_exists('PhpOffice\PhpWord\IOFactory')) {
                    $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
                    $text = '';
                    
                    foreach ($phpWord->getSections() as $section) {
                        foreach ($section->getElements() as $element) {
                            if (method_exists($element, 'getText')) {
                                $text .= $element->getText() . " ";
                            }
                        }
                    }
                    
                    return ['success' => true, 'text' => $text, 'error' => ''];
                }
            }

            // For DOC files, try using command line tool only if shell_exec is available
            if (function_exists('shell_exec') && self::commandExists('catdoc')) {
                $outputFile = tempnam(sys_get_temp_dir(), 'doc_');
                $command = "catdoc " . escapeshellarg($filePath);
                
                try {
                    $text = @shell_exec($command);
                    if (!empty($text)) {
                        return ['success' => true, 'text' => $text, 'error' => ''];
                    }
                } catch (Exception $e) {
                    // shell_exec failed, continue to next fallback
                }
            }

            return ['success' => false, 'text' => '', 'error' => 'DOC format requires additional tools'];
        } catch (Exception $e) {
            return ['success' => false, 'text' => '', 'error' => 'DOC parsing error: ' . $e->getMessage()];
        }
    }

    /**
     * Extract text from plain text files
     */
    private static function extractFromTxt($filePath)
    {
        try {
            $text = file_get_contents($filePath);
            if ($text !== false) {
                return ['success' => true, 'text' => $text, 'error' => ''];
            }
            return ['success' => false, 'text' => '', 'error' => 'Could not read text file'];
        } catch (Exception $e) {
            return ['success' => false, 'text' => '', 'error' => 'TXT parsing error: ' . $e->getMessage()];
        }
    }

    /**
     * Basic PDF text extraction without external libraries
     * Extracts text streams from PDF content using multiple methods
     * Now handles compressed (FlateDecode) content streams
     */
    private static function basicPdfExtraction($filePath)
    {
        try {
            $content = file_get_contents($filePath);
            if ($content === false || empty($content)) {
                return '';
            }
            
            $text = '';
            
            // First, try to decompress FlateDecode streams
            $decompressed = self::decompressFlatEncodedStreams($content);
            if (!empty($decompressed)) {
                // Try text extraction on decompressed content
                $text = self::extractTextFromDecompressed($decompressed);
                if (!empty($text)) {
                    return $text;
                }
            }
            
            // Method 1: Extract text from BT...ET blocks (text showing operations)
            if (preg_match_all('/BT(.+?)ET/s', $content, $matches)) {
                foreach ($matches[1] as $match) {
                    // Extract text within parentheses
                    if (preg_match_all('/\((.*?)\)/s', $match, $textMatches)) {
                        foreach ($textMatches[1] as $textMatch) {
                            $decoded = self::decodePdfText($textMatch);
                            if (!empty($decoded)) {
                                $text .= $decoded . " ";
                            }
                        }
                    }
                    // Also try angle brackets (hex encoded)
                    if (preg_match_all('/<([0-9A-Fa-f]+)>/s', $match, $hexMatches)) {
                        foreach ($hexMatches[1] as $hex) {
                            $decoded = self::decodeHexPdf($hex);
                            if (!empty($decoded)) {
                                $text .= $decoded . " ";
                            }
                        }
                    }
                }
            }
            
            // Method 2: Try extracting from /Contents streams directly
            if (empty($text) && preg_match_all('/stream\s*(.+?)\s*endstream/s', $content, $streamMatches)) {
                foreach ($streamMatches[1] as $stream) {
                    // Look for text commands in the stream
                    if (preg_match_all('/\((.*?)\)\s*T[jJ]/', $stream, $cmdMatches)) {
                        foreach ($cmdMatches[1] as $textMatch) {
                            $decoded = self::decodePdfText($textMatch);
                            if (!empty($decoded)) {
                                $text .= $decoded . " ";
                            }
                        }
                    }
                }
            }
            
            // Method 3: Look for text marked between "(" and ")" anywhere
            if (empty($text) && strlen($content) > 100) {
                if (preg_match_all('/\(([^\(\)]{5,200})\)/s', $content, $generalMatches)) {
                    $foundCount = 0;
                    foreach ($generalMatches[1] as $match) {
                        $decoded = self::decodePdfText($match);
                        if (!empty($decoded) && mb_strlen($decoded) > 2) {
                            $text .= $decoded . " ";
                            $foundCount++;
                            if ($foundCount > 50) break; // Limit to prevent garbage
                        }
                    }
                }
            }
            
            return trim($text);
        } catch (Exception $e) {
            error_log("PDF extraction error: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Decompress FlateDecode-encoded streams from PDF
     * PDFs often use /FlateDecode to compress content streams
     * Uses proper zlib decompression via gzuncompress or raw deflate
     * 
     * @param string $pdfContent Raw PDF file content
     * @return string Decompressed content or empty string
     */
    private static function decompressFlatEncodedStreams($pdfContent)
    {
        try {
            $decompressed = '';
            
            // Find all stream objects with declared lengths
            if (preg_match_all('/\/Length\s+(\d+).*?stream\s*\n(.+?)\nendstream/s', $pdfContent, $matches)) {
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $declaredLength = (int)$matches[1][$i];
                    $streamData = $matches[2][$i];
                    
                    // Trim to declared length
                    $streamTrimmed = substr($streamData, 0, $declaredLength);
                    
                    // Check if this stream is actually compressed (has zlib/deflate header)
                    // Valid zlib headers start with 0x78 (120 decimal)
                    if (strlen($streamTrimmed) < 2 || ord($streamTrimmed[0]) !== 0x78) {
                        // Not compressed, skip
                        continue;
                    }
                    
                    // Try decompression methods in order
                    $result = null;
                    
                    // Method 1: Try gzuncompress (handles zlib format with headers)
                    $result = @gzuncompress($streamTrimmed);
                    
                    // Method 2: Try gzinflate with header skipped (raw deflate)
                    if ($result === false && strlen($streamTrimmed) > 6) {
                        $deflate_data = substr($streamTrimmed, 2, strlen($streamTrimmed) - 6);
                        $result = @gzinflate($deflate_data);
                    }
                    
                    if ($result !== false && !empty($result)) {
                        $decompressed .= $result . "\n";
                    }
                }
            }
            
            return $decompressed;
        } catch (Exception $e) {
            error_log("FlateDecode decompression error: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Extract text from decompressed PDF content
     * Looks for text commands in the decompressed stream
     * Handles both simple text "(text)" and kerned arrays "[(text)offset(more)offset...]"
     * 
     * @param string $decompressed Decompressed PDF stream content
     * @return string Extracted text
     */
    private static function extractTextFromDecompressed($decompressed)
    {
        try {
            $text = '';
            
            // Extract text between BT and ET operators
            if (preg_match_all('/BT(.+?)ET/s', $decompressed, $matches)) {
                foreach ($matches[1] as $match) {
                    // Match TJ commands with text arrays: [(text1)offset(text2)offset...] TJ
                    // This handles kerning adjustments - PRIORITY since it's more common in this PDF
                    if (preg_match_all('/\[\s*(.+?)\s*\]\s*T[Jj]/s', $match, $arrayMatches)) {
                        foreach ($arrayMatches[1] as $arrayContent) {
                            // Extract all (text) patterns from within the array
                            // Add spaces between array elements
                            if (preg_match_all('/\(([^\(\)]*)\)/s', $arrayContent, $textMatches)) {
                                foreach ($textMatches[1] as $idx => $textMatch) {
                                    $decoded = self::decodePdfText($textMatch);
                                    if (!empty($decoded)) {
                                        // Add space before this segment if not first
                                        if ($idx > 0 && !empty($text) && substr($text, -1) !== ' ') {
                                            // Check if we should add a space (not already separated)
                                            $text .= $decoded;
                                        } else {
                                            $text .= $decoded;
                                        }
                                    }
                                }
                            }
                            // Add space after TJ command content
                            if (!empty($text) && substr($text, -1) !== ' ') {
                                $text .= " ";
                            }
                        }
                    }
                    
                    // Match Tj commands with simple text: (text) Tj
                    if (preg_match_all('/\(([^\(\)]*)\)\s*T[jJ]/s', $match, $textMatches)) {
                        foreach ($textMatches[1] as $textMatch) {
                            $decoded = self::decodePdfText($textMatch);
                            if (!empty($decoded)) {
                                $text .= $decoded . " ";
                            }
                        }
                    }
                    
                    // Match hex strings followed by Tj command
                    if (preg_match_all('/<([0-9A-Fa-f]+)>\s*T[Jj]/s', $match, $hexMatches)) {
                        foreach ($hexMatches[1] as $hex) {
                            $decoded = self::decodeHexPdf($hex);
                            if (!empty($decoded)) {
                                $text .= $decoded . " ";
                            }
                        }
                    }
                }
            }
            
            return trim($text);
        } catch (Exception $e) {
            error_log("Decompressed text extraction error: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Decode PDF-encoded text from parentheses
     */
    private static function decodePdfText($text)
    {
        // Remove escape sequences
        $text = preg_replace('/\\\\[\(\)\\\\]/', '', $text);
        
        // Remove PDF command codes
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Try to handle common PDF encodings
        $text = preg_replace('/\\\\[0-7]{1,3}/', '', $text); // Octal codes
        
        // Keep only printable ASCII and UTF-8
        $text = preg_replace('/[^\x20-\x7E\xA0-\xFF]/', '', $text);
        
        return trim($text);
    }

    /**
     * Decode hex-encoded PDF text
     */
    private static function decodeHexPdf($hex)
    {
        // Convert hex string to binary
        $text = '';
        for ($i = 0; $i < strlen($hex); $i += 2) {
            $byte = hexdec(substr($hex, $i, 2));
            if ($byte >= 32 && $byte <= 126) { // Printable ASCII
                $text .= chr($byte);
            }
        }
        
        return trim($text);
    }

    /**
     * Extract text from DOCX using ZIP method
     * DOCX is a ZIP file containing XML documents
     */
    private static function docxZipExtraction($filePath)
    {
        try {
            $zip = new ZipArchive();
            if ($zip->open($filePath) !== true) {
                return '';
            }

            // Extract document.xml which contains the main content
            $xmlString = $zip->getFromName('word/document.xml');
            if ($xmlString === false) {
                $zip->close();
                return '';
            }

            // Parse XML and extract text
            $xml = simplexml_load_string($xmlString);
            if ($xml === false) {
                $zip->close();
                return '';
            }

            // Register namespace
            $namespaces = $xml->getNamespaces(true);
            $text = '';

            // Extract all text nodes
            foreach ($xml->xpath('.//w:t') as $textElement) {
                $text .= (string)$textElement . " ";
            }

            $zip->close();
            return $text;
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Check if command exists on system (works on both Windows and Unix)
     */
    private static function commandExists($command)
    {
        // If shell_exec is disabled, we can't check for commands
        if (!function_exists('shell_exec')) {
            return false;
        }
        
        $os = strtoupper(substr(PHP_OS, 0, 3));
        
        try {
            if ($os === 'WIN') {
                // Windows: use 'where' command
                $returnVal = @shell_exec("where $command 2>nul");
            } else {
                // Unix/Linux: use 'command -v'
                $returnVal = @shell_exec("command -v $command 2>/dev/null");
            }
            
            return !empty($returnVal);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Clean and normalize extracted text
     * More lenient than before - preserves common punctuation and UTF-8
     */
    public static function cleanText($text)
    {
        // Preserve original if empty
        if (empty(trim($text))) {
            return '';
        }
        
        // Remove excessive whitespace but preserve line breaks
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n\n+/', '\n', $text);
        
        // Remove control characters but keep spaces, newlines, and printable characters
        // NOTE: Do NOT use /u flag here - it causes PCRE UTF-8 mode to misbehave with hex ranges
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Normalize line endings
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        
        // Add spaces before capital letters that follow lowercase letters (improves word separation)
        // This handles cases like "BlockchainService" -> "Blockchain Service"
        $text = preg_replace('/([a-z])([A-Z])/', '$1 $2', $text);
        
        // Trim each line
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines, 'strlen'); // Remove empty lines
        $text = implode(" ", $lines); // Join with spaces
        
        // Final trim and normalize whitespace
        $text = trim($text);
        $text = preg_replace('/\s+/', ' ', $text); // Normalize multiple spaces to single
        
        return $text;
    }
}
?>
