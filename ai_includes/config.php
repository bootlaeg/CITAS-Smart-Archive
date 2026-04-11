<?php
/**
 * AI Classification Configuration
 * Central configuration for the AI-powered thesis classification system
 */

return [
    // Ollama Configuration
    'ollama' => [
        'enabled' => true,
        'base_url' => 'http://localhost:11434',
        'model' => 'mistral',
        'timeout' => 120, // seconds
        'temperature' => 0.3, // Lower for more consistent classifications
    ],
    
    // Classification Settings
    'classification' => [
        'auto_classify_on_upload' => true,  // Automatically classify when thesis is uploaded
        'async_mode' => true,               // Use background processing
        'batch_size' => 20,                 // Number of theses to classify in batch
        'min_confidence' => 50,             // Minimum confidence threshold (0-100)
    ],
    
    // Keywords Configuration
    'keywords' => [
        'max_keywords' => 5,                // Number of keywords to extract
        'min_length' => 2,                  // Minimum keyword length
        'max_length' => 50,                 // Maximum keyword length
    ],
    
    // Subject Categories
    'subjects' => [
        'AI & Machine Learning',
        'Database Systems',
        'Cybersecurity',
        'Cloud Computing',
        'Web Development',
        'Mobile Development',
        'Natural Language Processing',
        'Computer Vision',
        'Data Science',
        'Software Engineering',
        'Networks & Communication',
        'Operating Systems',
        'Human-Computer Interaction',
        'Graphics & Visualization',
        'Internet of Things',
        'Blockchain & Cryptocurrency',
        'Other'
    ],
    
    // Research Methods
    'research_methods' => [
        'Empirical Study',
        'Literature Review',
        'Case Study',
        'Experimental Study',
        'Qualitative Research',
        'Quantitative Research',
        'Mixed Methods',
        'Survey/Questionnaire',
        'Simulation & Modeling',
        'Comparative Analysis',
        'MRAD',
        'Other'
    ],
    
    // Complexity Levels
    'complexity_levels' => [
        'beginner' => 'Introductory level research with basic concepts',
        'intermediate' => 'Standard research level with some advanced concepts',
        'advanced' => 'Complex research with cutting-edge topics and sophisticated methodology'
    ],
    
    // Cache Settings
    'cache' => [
        'enabled' => true,
        'ttl' => 86400, // 24 hours
    ],
    
    // Logging
    'logging' => [
        'enabled' => true,
        'level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
    ]
];
?>
