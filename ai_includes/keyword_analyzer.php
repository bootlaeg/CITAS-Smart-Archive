<?php
/**
 * Keyword Analyzer - Extracts keywords from text using text analysis and pattern matching
 * Falls back to AI if fewer than 3 keywords found
 */

class KeywordAnalyzer
{
    // Common stop words to exclude
    private static $stopWords = [
        'the', 'a', 'an', 'and', 'or', 'but', 'if', 'then', 'else', 'when', 'where',
        'why', 'how', 'what', 'which', 'who', 'whom', 'whose', 'to', 'from', 'by',
        'for', 'with', 'in', 'on', 'at', 'is', 'are', 'was', 'were', 'be', 'been',
        'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could',
        'should', 'may', 'might', 'must', 'can', 'this', 'that', 'these', 'those',
        'of', 'as', 'about', 'through', 'during', 'since', 'until', 'while', 'before',
        'after', 'above', 'below', 'up', 'down', 'out', 'off', 'over', 'under',
        'again', 'further', 'then', 'once', 'here', 'there', 'all', 'both', 'each',
        'few', 'more', 'most', 'other', 'some', 'such', 'only', 'just', 'very',
        'too', 'so', 'than', 'not', 'no', 'nor', 'it', 'its', 'i', 'you', 'he',
        'she', 'we', 'they', 'am', 'as', 'should', 'also', 'been', 'being', 'does'
    ];

    /**
     * Analyze text and extract keywords using multiple methods
     * 
     * @param string $text The text to analyze
     * @param string $abstract Optional abstract to prioritize keywords from
     * @param int $minKeywords Minimum keywords to return (default: 5)
     * @return array ['keywords' => [...], 'method' => 'document|ai-required']
     */
    public static function analyzeText($text, $abstract = '', $minKeywords = 5)
    {
        $keywords = [];

        // Method 1: Extract high-frequency meaningful words (PRIORITY - most reliable)
        $frequencyKeywords = self::extractFrequencyKeywords($text, 15);
        $keywords = array_merge($keywords, $frequencyKeywords);

        // Method 2: Extract technical terms and capitalized phrases
        $technicalTerms = self::extractTechnicalTerms($text);
        $keywords = array_merge($keywords, $technicalTerms);

        // Method 3: Extract noun phrases (high threshold to avoid junk)
        $nounPhrases = self::extractNounPhrases($text);
        $keywords = array_merge($keywords, $nounPhrases);

        // Remove duplicates and sort by relevance
        $keywords = array_unique(array_map('strtolower', $keywords));
        
        // If abstract provided, prioritize keywords from abstract
        if (!empty($abstract)) {
            $abstractKeywords = self::extractFrequencyKeywords($abstract, 5);
            $keywords = array_merge($abstractKeywords, $keywords);
        }

        // Remove duplicates again after merging
        $keywords = array_unique($keywords);

        // Limit to reasonable number and ensure minimum quality
        $keywords = self::filterAndSortKeywords($keywords, $minKeywords);

        // Return result with method indicator
        $method = count($keywords) >= 3 ? 'document' : 'ai-required';
        
        return [
            'keywords' => array_slice($keywords, 0, $minKeywords),
            'method' => $method,
            'count' => count($keywords)
        ];
    }

    /**
     * Extract technical terms and capitalized phrases
     * Looks for proper nouns, acronyms, and technical jargon
     */
    private static function extractTechnicalTerms($text)
    {
        $terms = [];

        // Find capitalized words (proper nouns/technical terms)
        // IMPORTANT: Use space only, not \s, to avoid capturing across line breaks
        if (preg_match_all('/\b([A-Z][a-z]+(?:\ [A-Z][a-z]+)*)\b/', $text, $matches)) {
            $terms = array_merge($terms, $matches[1]);
        }

        // Find acronyms (all caps words of 2-5 letters)
        if (preg_match_all('/\b([A-Z]{2,5})\b/', $text, $matches)) {
            $terms = array_merge($terms, $matches[1]);
        }

        // Find hyphenated technical terms
        if (preg_match_all('/\b([a-z]+-[a-z]+(?:-[a-z]+)*)\b/i', $text, $matches)) {
            $terms = array_merge($terms, $matches[1]);
        }

        // Clean and deduplicate
        $terms = array_unique($terms);
        
        // Filter out common non-meaningful capitalized words and stop words
        $terms = array_filter($terms, function($term) {
            return !in_array(strtolower($term), self::$stopWords) && strlen($term) > 2;
        });

        return array_values($terms);
    }

    /**
     * Extract high-frequency keywords using TF-IDF-like approach
     */
    private static function extractFrequencyKeywords($text, $limit = 10)
    {
        // First, extract important 2-3 word phrases by looking for repeated sequences
        $phrases = self::extractRepeatedPhrases($text);
        
        // Then tokenize and get single words
        $words = self::tokenize($text);
        $frequencies = array_count_values($words);
        arsort($frequencies);
        
        $candidates = array_keys(array_slice($frequencies, 0, $limit));
        $keywords = array_filter($candidates, function($word) {
            return strlen($word) >= 4;
        });
        
        // Combine phrases and single words, with phrases having priority
        $result = array_merge($phrases, array_values($keywords));
        return array_values(array_unique($result));
    }
    
    /**
     * Extract phrases that appear multiple times in text
     * Looks specifically for repeated 2-3 word sequences
     */
    private static function extractRepeatedPhrases($text)
    {
        $text = strtolower($text);
        $phrases = [];
        
        // Extract 2-word phrases
        $pattern = '/\b([a-z]{3,})\s+([a-z]{3,})\b/';
        if (preg_match_all($pattern, $text, $matches)) {
            $phraseFreq = [];
            for ($i = 0; $i < count($matches[0]); $i++) {
                $w1 = $matches[1][$i];
                $w2 = $matches[2][$i];
                
                // Skip if either word is a stop word
                if (in_array($w1, self::$stopWords) || in_array($w2, self::$stopWords)) {
                    continue;
                }
                
                $phrase = "$w1 $w2";
                $phraseFreq[$phrase] = ($phraseFreq[$phrase] ?? 0) + 1;
            }
            
            // Get phrases that repeat (appear 2+ times)
            foreach ($phraseFreq as $phrase => $count) {
                if ($count >= 2) {
                    $phrases[] = $phrase;
                }
            }
        }
        
        // Extract 3-word phrases if not enough phrases found
        if (count($phrases) < 5) {
            $pattern = '/\b([a-z]{3,})\s+([a-z]{3,})\s+([a-z]{3,})\b/';
            if (preg_match_all($pattern, $text, $matches)) {
                $phraseFreq = [];
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $w1 = $matches[1][$i];
                    $w2 = $matches[2][$i];
                    $w3 = $matches[3][$i];
                    
                    // Skip if majority are stop words
                    $stopCount = 0;
                    if (in_array($w1, self::$stopWords)) $stopCount++;
                    if (in_array($w2, self::$stopWords)) $stopCount++;
                    if (in_array($w3, self::$stopWords)) $stopCount++;
                    
                    if ($stopCount >= 2) continue; // Skip if 2+ stop words
                    
                    $phrase = "$w1 $w2 $w3";
                    if (strlen($phrase) <= 35) { // Reasonable length
                        $phraseFreq[$phrase] = ($phraseFreq[$phrase] ?? 0) + 1;
                    }
                }
                
                // Get 3-word phrases that repeat 2+ times, but NO stop words!
                foreach ($phraseFreq as $phrase => $count) {
                    if ($count >= 2) {
                        // STRICT: reject any phrase containing ANY stop word
                        $words = explode(' ', $phrase);
                        $hasStopWord = false;
                        foreach ($words as $word) {
                            if (in_array($word, self::$stopWords)) {
                                $hasStopWord = true;
                                break;
                            }
                        }
                        // Only keep if NO stop words
                        if (!$hasStopWord) {
                            $phrases[] = $phrase;
                        }
                    }
                }
            }
        }
        
        return array_values(array_unique(array_slice($phrases, 0, 5)));
    }

    /**
     * Extract noun phrases using STRICT pattern matching
     * Only extracts very clean, complete noun phrases
     */
    private static function extractNounPhrases($text)
    {
        $phrases = [];
        
        // Split text into sentences to avoid capturing across sentence boundaries
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($sentences as $sentence) {
            // Look for capitalized phrases (likely proper nouns or specific terms)
            // Must have at least one uppercase letter and be short enough
            if (preg_match_all('/\b(?:[A-Z][a-z]+(?:\s+[A-Z][a-z]+)?)\b/', $sentence, $matches)) {
                foreach ($matches[0] as $phrase) {
                    $trimmed = trim($phrase);
                    if (strlen($trimmed) <= 35) { // Max 35 chars for phrase
                        $phrases[] = $trimmed;
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($phrases)));
    }

    /**
     * Tokenize text into words
     */
    private static function tokenize($text)
    {
        // Convert to lowercase
        $text = strtolower($text);
        
        // Remove special characters, keep only letters and numbers
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        
        // Split into words
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Filter: remove stop words and short words
        $words = array_filter($words, function($word) {
            return !in_array($word, self::$stopWords) && strlen($word) > 2;
        });

        return array_values($words);
    }

    /**
     * Filter and sort keywords by relevance
     */
    private static function filterAndSortKeywords($keywords, $minKeywords)
    {
        // AGGRESSIVE filter: Remove any keyword containing stop words
        $keywords = array_filter($keywords, function($kw) {
            // Skip keywords that contain stop words (especially in phrases)
            if (strpos($kw, ' ') !== false) {
                // Multi-word: check if ANY word is a stop word
                $words = explode(' ', strtolower($kw));
                foreach ($words as $word) {
                    if (in_array($word, self::$stopWords)) {
                        return false; // Reject this keyword
                    }
                }
            }
            
            // Single word: check length
            $len = strlen($kw);
            // Keep keywords between 4-50 characters (short phrases)
            return $len >= 4 && $len <= 50;
        });

        // Additional filter: limit words per phrase to max 3, language-based quality
        $keywords = array_filter($keywords, function($kw) {
            $wordCount = count(explode(' ', $kw));
            return $wordCount <= 3;
        });

        // Sort by length (longer, more specific phrases first) then alphabetically
        usort($keywords, function($a, $b) {
            $lenDiff = strlen($b) - strlen($a);
            return $lenDiff !== 0 ? $lenDiff : strcmp($a, $b);
        });

        return array_slice($keywords, 0, max($minKeywords * 2, 20)); // Keep up to 20 candidates
    }

    /**
     * Get recommendation on whether to use document keywords or AI
     */
    public static function shouldUseDocumentKeywords($keywords)
    {
        return count($keywords) >= 3;
    }

    /**
     * Format keywords for display
     */
    public static function formatKeywords($keywords, $delimiter = ', ')
    {
        return implode($delimiter, array_map('trim', $keywords));
    }

    /**
     * Generate full AI classification for thesis
     * STEP 3: Uses Ollama to generate Subject Category, Research Method, Complexity Level, and Keywords
     * 
     * @param string $text The document text to analyze
     * @param string $abstract Optional abstract for context
     * @param string $title Optional thesis title
     * @param string $model Ollama model to use (default: mistral)
     * @return array with keys: subject_category, research_method, complexity_level, keywords, error
     */
    public static function generateAIClassification($text, $abstract = '', $title = '', $model = 'mistral')
    {
        try {
            require_once __DIR__ . '/ollama_service_curl.php';
            
            $ollama = new OllamaServiceCurl($model);
            
            // Clean text of invalid UTF-8 characters before processing
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            $abstract = mb_convert_encoding($abstract, 'UTF-8', 'UTF-8');
            $title = mb_convert_encoding($title, 'UTF-8', 'UTF-8');
            
            // Prepare context from abstract and text
            $context = '';
            if (!empty($title)) {
                $context .= "Thesis Title: " . substr($title, 0, 100) . "\n";
            }
            if (!empty($abstract) && strlen($abstract) > 50) {
                $context .= "Abstract: " . substr($abstract, 0, 500) . "\n\n";
            }
            
            // Add first part of document text
            $context .= "Content: " . substr($text, 0, 1500);
            
            // Create a SPECIFIC prompt for citations that asks for author names and years
            $prompt = "Based on this academic content, answer these questions briefly:\n\n$context\n\n1. Main subject/field (1-3 words):\n2. Primary research method (1-3 words):\n3. Complexity (beginner/intermediate/advanced):\n4. Top 5 keywords (comma-separated):\n5. Academic citations with author names and years (e.g., 'Smith et al. (2020), Johnson & Lee (2019)' or 'none'):\n6. Author names (comma-separated, e.g., 'John Smith, Jane Doe' or 'Not specified'):\n\nAnswer format: Use only numbered lines. For citations, list author names/years or organizations.";

            error_log("Sending simplified prompt to Ollama/mistral");
            error_log("Prompt length: " . strlen($prompt));
            
            // Get AI response
            $response = $ollama->prompt($prompt, ['temperature' => 0.3]);
            
            error_log("Raw Ollama response: " . substr($response, 0, 500));
            
            // Parse the response manually since it's in simple text format
            $lines = array_filter(array_map('trim', explode("\n", $response)));
            
            error_log("Total lines in response: " . count($lines));
            error_log("Lines received: " . implode(" | ", array_slice($lines, 0, 10)));
            
            $subjectCategory = '';
            $researchMethod = '';
            $complexityLevel = 'intermediate';
            $keywords = [];
            $citations = [];
            $author = '';
            
            // Initialize extraction counters
            $extracted = [1 => false, 2 => false, 3 => false, 4 => false, 5 => false, 6 => false];
            
            // Try to extract values from numbered lines (format: "1. value")
            foreach ($lines as $index => $line) {
                // Remove leading "1.", "2.", "3.", "4.", "5." from lines
                if (preg_match('/^\d+\.\s*(.+)$/', $line, $matches)) {
                    $lineNum = intval(substr($line, 0, 1));
                    $value = trim($matches[1]);
                    
                    error_log("Found line $lineNum: " . substr($value, 0, 100));
                    $extracted[$lineNum] = true;
                    
                    if ($lineNum === 1) {
                        $subjectCategory = $value;
                    } elseif ($lineNum === 2) {
                        $researchMethod = $value;
                    } elseif ($lineNum === 3) {
                        $complexityLevel = strtolower($value);
                        if (!in_array($complexityLevel, ['beginner', 'intermediate', 'advanced'])) {
                            $complexityLevel = 'intermediate';
                        }
                    } elseif ($lineNum === 4) {
                        // Split by comma for keywords
                        $keywords = array_filter(array_map('trim', explode(',', $value)));
                    } elseif ($lineNum === 5) {
                        // Split by comma for citations (or skip if line starts with 'none')
                        if (!preg_match('/^none/i', trim($value))) {
                            $citations = array_filter(array_map('trim', explode(',', $value)));
                        }
                    } elseif ($lineNum === 6) {
                        // Extract author names (skip if line starts with 'not specified')
                        if (!preg_match('/^not specified/i', trim($value))) {
                            $author = trim($value);
                        }
                    }
                }
            }
            
            error_log("Extraction status: " . json_encode($extracted));
            error_log("Parsed subject: $subjectCategory" . ($extracted[1] ? ' ✓' : ' ✗'));
            error_log("Parsed method: $researchMethod" . ($extracted[2] ? ' ✓' : ' ✗'));
            error_log("Parsed complexity: $complexityLevel" . ($extracted[3] ? ' ✓' : ' ✗'));
            error_log("Parsed keywords: " . implode(', ', array_slice($keywords, 0, 5)) . ($extracted[4] ? ' ✓' : ' ✗'));
            error_log("Parsed citations from AI: " . count($citations) . " items" . ($extracted[5] ? ' ✓' : ' ✗'));
            error_log("Parsed author: $author" . ($extracted[6] ? ' ✓' : ' ✗'));
            
            // FALLBACK: If any critical field is missing, try alternative extraction methods
            if (empty($subjectCategory) && !empty($title)) {
                error_log("⚠️  Subject category is empty, using title as fallback");
                $subjectCategory = substr($title, 0, 50);
            }
            
            if (empty($researchMethod)) {
                error_log("⚠️  Research method is empty, trying to extract from text...");
                // Try to find research method indicators in the text
                if (preg_match('/(qualitative|quantitative|mixed methods|empirical|case study|systematic review|meta-analysis|experimental|observational)/i', $text, $matches)) {
                    $researchMethod = ucfirst($matches[1]);
                    error_log("   Found research method: $researchMethod");
                } else {
                    $researchMethod = "Not Identified";
                    error_log("   Could not identify research method, using default");
                }
            }
            
            // If no citations found via AI, try to extract them from the text itself
            if (count($citations) === 0) {
                error_log("No citations from AI, attempting fallback extraction from text...");
                $citations = self::extractCitationsFromText($text);
                error_log("Fallback citations found: " . count($citations) . " - " . implode(', ', array_slice($citations, 0, 3)));
            }
            
            // If no author found via AI, try to extract from text
            if (empty($author)) {
                error_log("No author from AI, attempting fallback extraction from text...");
                $author = self::extractAuthorFromText($text);
                if (!empty($author)) {
                    error_log("Fallback author found: $author");
                } else {
                    error_log("Could not extract author from text fallback");
                }
            }
            
            return [
                'subject_category' => $subjectCategory,
                'research_method' => $researchMethod,
                'complexity_level' => $complexityLevel,
                'keywords' => array_slice($keywords, 0, 7),
                'citations' => $citations,
                'author' => $author,
                'error' => null
            ];
            
        } catch (Exception $e) {
            error_log("AI Classification Error: " . $e->getMessage());
            return [
                'subject_category' => '',
                'research_method' => '',
                'complexity_level' => 'intermediate',
                'keywords' => [],
                'citations' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Extract citations from text using regex patterns (fallback method)
     * Extracts only URLs/DOIs
     * @param string $text The text to extract citations from
     * @return array Array of URLs
     */
    public static function extractCitationsFromText($text)
    {
        $citations = [];
        
        // Extract all URLs starting with https:// or http://
        if (preg_match_all('/(https?:\/\/[^\s\)]+)/', $text, $matches)) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $url = trim($matches[1][$i]);
                
                // Filter for valid URL patterns
                if (preg_match('/(doi\.org|www\.|digital\.|ahrq|medicalbuyer|healthit|publications|nih|github|arxiv|researchgate)/', $url) && !in_array($url, $citations)) {
                    $citations[] = $url;
                }
            }
        }
        
        // Remove duplicates and keep all unique citations
        $citations = array_unique($citations);
        $citations = array_values($citations);
        return $citations;
    }
    
    /**
     * Format citations - just pass through raw URLs
     * @param array $citations Array of raw URLs
     * @return array Formatted citations
     */
    public static function formatCitationsMultipleStyles($citations)
    {
        return [
            'raw_citations' => $citations,
            'in_text_apa' => [],
            'narrative' => [],
            'url_references' => $citations,
            'reference_list' => []
        ];
    }

    /**
     * Extract metadata (Title, Author, Year, Abstract) from document text
     * Uses simple pattern matching and heuristics
     * 
     * @param string $text The document text
     * @return array with keys: title, author, year, abstract
     */
    public static function extractMetadata($text)
    {
        $metadata = [
            'title' => '',
            'author' => '',
            'year' => '',
            'abstract' => ''
        ];

        // Normalize text - remove excessive whitespace but preserve structure
        $normalizedText = preg_replace('/\s+/', ' ', $text);
        
        // Extract Title - multiple strategies for robustness
        
        // Strategy 1: Look for the longest first sentence (common in DOCX/PDF)
        $lines = array_filter(explode("\n", $text));
        if (!empty($lines)) {
            // Get first non-empty line
            $firstLine = trim($lines[0]);
            if (strlen($firstLine) >= 10 && strlen($firstLine) <= 200) {
                // Check if it looks like a title (ends with common title punctuation or followed by newline)
                $metadata['title'] = preg_replace('/\s+/', ' ', $firstLine);
            }
        }
        
        // Strategy 2: If no title found, look for pattern like "Title:" or major heading
        if (empty($metadata['title'])) {
            if (preg_match('/^(?:title|heading|name)[\s:]+(.+?)(?:\n|Abstract|Author|Introduction|[0-9]\.|$)/im', $normalizedText, $matches)) {
                $title = trim($matches[1]);
                $title = preg_replace('/\s+/', ' ', $title);
                if (strlen($title) >= 10 && strlen($title) <= 200) {
                    $metadata['title'] = $title;
                }
            }
        }
        
        // Strategy 3: Take first 50-200 char sentence if still empty
        if (empty($metadata['title'])) {
            if (preg_match('/(.{50,200}?)[\.\!?\n]/', $normalizedText, $matches)) {
                $title = trim($matches[1]);
                if (strlen($title) >= 10) {
                    $metadata['title'] = $title;
                }
            }
        }

        // Extract Year (look for 4-digit years between 1990-2030)
        if (preg_match('/\b(19\d{2}|20\d{2})\b/', $text, $matches)) {
            $metadata['year'] = $matches[1];
        }

        // Extract Author (look for patterns like "By John Smith" or "Author: John Smith")
        if (preg_match('/(?:by|author|written by)[\s:]+([A-Z][a-z]+ [A-Z][a-z]+(?:\s[A-Z][a-z]+)?)/i', $text, $matches)) {
            $metadata['author'] = trim($matches[1]);
        }

        // Extract Abstract (look for abstract section)
        if (preg_match('/abstract[\s:]*(.+?)(?:introduction|keywords|references|introduction|1\.|[0-9]\.|$)/ims', $text, $matches)) {
            $abstract = trim($matches[1]);
            // Clean up and limit length
            $abstract = preg_replace('/\s+/', ' ', $abstract);
            $abstract = substr($abstract, 0, 500);
            $metadata['abstract'] = $abstract;
        }

        // If abstract not found, use first 300 chars that seem like introduction
        if (empty($metadata['abstract'])) {
            $intro = substr($text, 0, 800);
            $intro = preg_replace('/\s+/', ' ', $intro);
            $intro = trim(substr($intro, 0, 500));
            if (strlen($intro) > 50) {
                $metadata['abstract'] = $intro;
            }
        }

        return $metadata;
    }

    /**
     * Polish and format abstract text using AI
     * Fixes typos, adds proper spacing, and improves readability
     * Preserves grammar and meaning
     * 
     * @param string $abstract The abstract text to polish
     * @param string $model Ollama model to use (default: mistral)
     * @return array with keys: success, polished_text, error
     */
    public static function polishAbstractText($abstract, $model = 'mistral')
    {
        if (empty($abstract) || strlen($abstract) < 20) {
            return [
                'success' => false,
                'polished_text' => $abstract,
                'error' => 'Abstract too short to polish'
            ];
        }

        try {
            require_once __DIR__ . '/ollama_service_curl.php';
            
            $ollama = new OllamaServiceCurl($model);
            
            // Clean text of invalid UTF-8 characters
            $abstract = mb_convert_encoding($abstract, 'UTF-8', 'UTF-8');
            
            // Create a specific prompt for text polishing
            $prompt = "You are a text formatting expert. Your task is to ONLY fix typos and improve spacing in the following academic text. DO NOT change grammar, meaning, or structure. Just clean it up.\n\nRules:\n1. Fix obvious typos (e.g., 'helath' -> 'health')\n2. Add proper spacing (remove extra spaces, fix line breaks)\n3. Ensure punctuation has proper spacing\n4. Make it readable but preserve all original content\n5. Do NOT rephrase or change word choice\n6. Keep the same tone and structure\n\nText to polish:\n\"$abstract\"\n\nReturn ONLY the polished text, nothing else. No explanations or comments.";

            error_log("Sending polish request to Ollama");
            
            // Get AI response with lower temperature for consistency
            $response = $ollama->prompt($prompt, ['temperature' => 0.1]);
            
            if (empty($response)) {
                return [
                    'success' => false,
                    'polished_text' => $abstract,
                    'error' => 'AI returned empty response'
                ];
            }

            // Clean up the response - remove quotes if wrapped
            $polished = trim($response);
            if ((substr($polished, 0, 1) === '"' && substr($polished, -1) === '"') ||
                (substr($polished, 0, 1) === "'" && substr($polished, -1) === "'")) {
                $polished = substr($polished, 1, -1);
            }

            error_log("Text polishing successful. Original length: " . strlen($abstract) . ", Polished length: " . strlen($polished));
            
            return [
                'success' => true,
                'polished_text' => $polished,
                'error' => null
            ];
            
        } catch (Exception $e) {
            error_log("Text polishing error: " . $e->getMessage());
            return [
                'success' => false,
                'polished_text' => $abstract,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Polish and format title text using AI
     * Fixes typos, adds proper spacing, improves readability
     * Preserves grammar and meaning
     * 
     * @param string $title The title text to polish
     * @param string $model Ollama model to use (default: mistral)
     * @return array with keys: success, polished_text, error
     */
    public static function polishTitleText($title, $model = 'mistral')
    {
        if (empty($title) || strlen($title) < 5) {
            return [
                'success' => false,
                'polished_text' => $title,
                'error' => 'Title too short to polish'
            ];
        }

        try {
            require_once __DIR__ . '/ollama_service_curl.php';
            
            $ollama = new OllamaServiceCurl($model);
            
            // Clean text of invalid UTF-8 characters
            $title = mb_convert_encoding($title, 'UTF-8', 'UTF-8');
            
            // Create a specific prompt for title polishing
            $prompt = "You are a title formatting expert. Your task is to ONLY fix typos, spacing, and capitalize properly in the following title. DO NOT change meaning or structure.\n\nRules:\n1. Fix obvious typos (e.g., 'helath' -> 'health')\n2. Add proper spacing (remove extra spaces)\n3. Fix capitalization for proper title format\n4. Ensure punctuation is correct\n5. Do NOT change word choice or meaning\n6. Keep the same length and concept\n\nTitle to polish:\n\"$title\"\n\nReturn ONLY the polished title, nothing else. No explanations or comments.";

            error_log("Sending title polish request to Ollama");
            
            // Get AI response with lower temperature for consistency
            $response = $ollama->prompt($prompt, ['temperature' => 0.1]);
            
            if (empty($response)) {
                return [
                    'success' => false,
                    'polished_text' => $title,
                    'error' => 'AI returned empty response'
                ];
            }

            // Clean up the response - remove quotes if wrapped
            $polished = trim($response);
            if ((substr($polished, 0, 1) === '"' && substr($polished, -1) === '"') ||
                (substr($polished, 0, 1) === "'" && substr($polished, -1) === "'")) {
                $polished = substr($polished, 1, -1);
            }

            error_log("Title polishing successful. Original length: " . strlen($title) . ", Polished length: " . strlen($polished));
            
            return [
                'success' => true,
                'polished_text' => $polished,
                'error' => null
            ];
            
        } catch (Exception $e) {
            error_log("Title polishing error: " . $e->getMessage());
            return [
                'success' => false,
                'polished_text' => $title,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract author names from text content
     * Looks for common patterns and locations where authors are typically found
     * 
     * @param string $text Full document text
     * @return string Extracted and formatted author names or empty string
     */
    public static function extractAuthorFromText($text)
    {
        try {
            // Split text into lines
            $lines = array_map('trim', explode("\n", $text));
            $authors = [];
            
            // Strategy 1: Look for author names near the beginning (first 50 lines are usually title/author section)
            $beginningText = implode("\n", array_slice($lines, 0, 50));
            
            // PATTERN 0A: Look for "Authors:" section (can span multiple lines until "Affiliations:")
            // This is the PRIMARY pattern - handles most academic papers
            $authorMatch = [];
            if (preg_match('/authors?\s*:\s*(.+?)(?:affiliations?|journal|abstract|introduction|keywords)/is', $beginningText, $authorMatch)) {
                $authorSection = trim($authorMatch[1]);
                
                // Remove leading symbols like asterisks
                $authorSection = preg_replace('/^\s*[\*†‡§¶]/','', $authorSection);
                $authorSection = trim($authorSection);
                
                // Split by commas to get author segments
                $authorList = array_map('trim', explode(',', $authorSection));
                
                foreach ($authorList as $segment) {
                    $author = trim($segment);
                    
                    // Extract just the name part before titles like "Prof", "Dr", "PhD", etc
                    if (preg_match('/^([A-Za-z\s\-\'\.]*?)(?:\s+(?:Prof|Dr|MD|PhD|MSc|MBA|Sc\.D))/i', $author, $nameMatch)) {
                        $author = trim($nameMatch[1]);
                    }
                    
                    // Also handle case where author is followed by title like "Chou Kue , Digital Health Scientist"
                    $author = trim(preg_replace('/\s+[A-Z][a-z]+\s(?:scientist|fellow|researcher|chairman|director|manager)/i', '', $author));
                    
                    // Remove superscript characters  
                    $author = str_replace(['¹', '²', '³', '⁴', '⁵', '⁶', '⁷', '⁸', '⁹', '⁰'], '', $author);
                    $author = str_replace(['†', '‡', '§', '¶', '*'], '', $author);
                    
                    // Also try to handle any non-ASCII characters that are likely superscripts
                    // Specifically, characters with high byte values (0x80+) but keep Latin letters
                    // More carefully: only remove if not a letter or common punctuation
                    $cleanedAuthor = '';
                    for ($i = 0; $i < strlen($author); $i++) {
                        $char = $author[$i];
                        $byte = ord($char);
                        
                        // Keep ASCII letters, numbers, spaces, hyphens, periods
                        if (($byte >= 32 && $byte < 127) || $byte >= 192) { // ASCII or start of UTF-8 multibyte
                            $cleanedAuthor .= $char;
                        }
                    }
                    $author = $cleanedAuthor;
                    
                    // Fix common spacing issues from PDF extraction
                    // e.g., "RezaBMakkaraka" -> "Reza B Makkaraka", "BMakkaraka" -> "B Makkaraka"
                    $author = preg_replace('/([a-z])([A-Z])/', '$1 $2', $author); // lowercase followed by uppercase
                    // Handle cases like "RezaBMakkaraka" where capital B (middle initial) needs space
                    $author = preg_replace('/([a-z]\s[A-Z])([A-Z][a-z]+)/', '$1 $2', $author);
                    
                    // Clean up multiple spaces
                    $author = preg_replace('/\s+/', ' ', $author);
                    $author = trim($author);
                    
                    // Skip if empty or very short
                    if (strlen($author) < 3) {
                        continue;
                    }
                    
                    // Skip if contains certain keywords indicating it's not an author name
                    if (preg_match('/(university|institute|college|faculty|department|hospital|clinic|center|centre|laboratory|school|academy|association|society|foundation|cisco|flinders|fudan)/i', $author)) {
                        continue;
                    }
                    // Also skip if starts with common titles
                    if (preg_match('/^(digital|health|scientist|professor|prof|dr|phd|md|msc|mba|information|system|education|research|fellow|senior|junior|chair)/i', $author)) {
                        continue;
                    }
                    
                    // Skip if it's a number or date
                    if (preg_match('/^\d+$|^\d{2,4}$|^(january|february|march|april|may|june|july|august|september|october|november|december)$/i', $author)) {
                        continue;
                    }
                    
                    // Must be shorter than 200
                    if (strlen($author) < 200) {
                        // Check it looks like a name: starts with capital letter, then letters/spaces/hyphens
                        // Allows for full names like "Andi Muh Reza B Makkaraka", "Akbar Iskandar", "Wang Yang"
                        if (preg_match('/^[A-Z][a-zA-Z\s\-\']+$/', $author)) {
                            $authors[] = $author;
                        }
                    }
                }
            }
            
            // If we got any authors from pattern 0A, return them immediately - don't try fallback patterns
            // Pattern 0A (matching "Authors:" section) is most reliable
            if (count($authors) >= 1) {
                error_log("Extracted " . count($authors) . " author(s) from Pattern 0A: " . implode(" | ", $authors));
                return implode(', ', $authors);
            }
            
            // Pattern 0B: If only one author or no match yet, try singular "Author:" with more flexible matching
            if (preg_match('/(^|\n)\s*(author|by|author\s+name)\s*:\s*([^\n,]+)(?:,|$)/im', $beginningText, $matches)) {
                $authorName = trim($matches[3]);
                // Filter out months and dates
                if (!preg_match('/^(january|february|march|april|may|june|july|august|september|october|november|december|\d{2,4})$/i', trim($authorName))) {
                    if (strlen($authorName) > 3) {
                        $authors[] = $authorName;
                    }
                }
            }
            
            // Remove common noise words and separators
            $cleanedBeginning = preg_replace('/\s*(by|of|in|on|from|published|department|university|institute|college|faculty|school)\s*/i', ' ', $beginningText);
            
            // Pattern 2: Look for names with superscript numbers (common in academic papers)
            // e.g., "Andi Muh Reza B Makkaraka¹, Akbar Iskandar², Wang Yang³"
            if (count($authors) < 2 && preg_match_all('/([A-Z][a-z]+[\s]*(?:[A-Z][a-z]+)*[\s]*(?:[A-Z]\.)?[\s]*(?:[A-Z][a-z]+)*)[¹²³⁴⁵⁶⁷⁸⁹⁰\d]/', $beginningText, $matches)) {
                $authors = array_merge($authors, array_map('trim', $matches[1]));
            }
            
            // Pattern 3: Look for typical author name patterns preceded by Dr., Prof., etc.
            if (count($authors) < 1 && preg_match('/(?:Dr\.|Prof\.|M\.D\.|PhD)\s+([A-Z][a-z]+(?:\s+[A-Z][a-z\']+)*)/i', $beginningText, $matches)) {
                $authors[] = trim($matches[1]);
            }
            
            // Clean and deduplicate authors
            $authors = array_unique($authors);
            $authors = array_filter(array_map('trim', $authors));
            
            // Remove entries that are pure dates or too short/long (likely noise)
            $authors = array_filter($authors, function($a) {
                $len = strlen($a);
                // Filter out months, years, and single words
                if (preg_match('/^(january|february|march|april|may|june|july|august|september|october|november|december|\d{2,4})$/i', $a)) {
                    return false;
                }
                // Filter out single words or very short entries (unless they have special chars like apostrophes)
                if (!str_contains($a, ' ') && $len < 4) {
                    return false;
                }
                return $len > 3 && $len < 200;  
            });
            
            if (!empty($authors)) {
                error_log("Extracted authors: " . count($authors) . " - " . implode(" | ", array_slice($authors, 0, 3)));
                // Return all authors comma-separated
                return implode(', ', array_values($authors));
            }
            
            error_log("No author patterns found in text");
            return '';
            
        } catch (Exception $e) {
            error_log("Author extraction error: " . $e->getMessage());
            return '';
        }
    }

}
?>
