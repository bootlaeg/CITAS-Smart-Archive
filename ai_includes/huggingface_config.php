<?php
/**
 * Hugging Face API Configuration
 * 
 * Stores API credentials for Hugging Face Inference API
 * Used for document summarization and journal conversion
 */

// Hugging Face API Settings
define('HUGGING_FACE_API_KEY', getenv('HUGGING_FACE_API_KEY') ?: '');
define('HUGGING_FACE_API_URL', 'https://api-inference.huggingface.co/models');

// Model Configuration
// facebook/bart-large-cnn is excellent for long-form content summarization
define('HUGGING_FACE_MODEL', 'facebook/bart-large-cnn');

// Summarization Parameters
define('HUGGING_FACE_MAX_LENGTH', 1500);      // Maximum summary length in tokens
define('HUGGING_FACE_MIN_LENGTH', 500);       // Minimum summary length in tokens
define('HUGGING_FACE_DO_SAMPLE', false);      // Use greedy decoding (faster, more deterministic)
define('HUGGING_FACE_TEMPERATURE', 0.7);     // Randomness in generation (0.7 = moderate)

// API Timeout and Retry Settings
define('HUGGING_FACE_TIMEOUT', 30);           // Timeout in seconds
define('HUGGING_FACE_RETRY_COUNT', 2);        // Number of retries on failure
define('HUGGING_FACE_RETRY_DELAY', 2000);     // Delay between retries in milliseconds

// Logging
define('HUGGING_FACE_LOG_ENABLED', true);
define('HUGGING_FACE_LOG_FILE', __DIR__ . '/../logs/huggingface_api.log');

// Error Handling
define('HUGGING_FACE_USE_FALLBACK', true);    // Use extractive summarization if API fails

?>
