<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AWS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AWS SDK and Bedrock AgentCore integration.
    |
    */
    'aws' => [
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'version' => 'latest',
    ],

    /*
    |--------------------------------------------------------------------------
    | Amazon Bedrock Configuration  
    |--------------------------------------------------------------------------
    |
    | Configuration for Bedrock AgentCore Runtime integration.
    |
    */
    'bedrock' => [
        'agent_id' => env('BEDROCK_AGENT_ID'),
        'agent_alias_id' => env('BEDROCK_AGENT_ALIAS_ID', 'TSTALIASID'),
        'session_id' => env('BEDROCK_SESSION_ID'),
        'model_id' => env('BEDROCK_MODEL_ID', 'anthropic.claude-3-sonnet-20240229-v1:0'),
        'max_tokens' => env('BEDROCK_MAX_TOKENS', 4000),
        'temperature' => env('BEDROCK_TEMPERATURE', 0.7),
        'timeout' => env('BEDROCK_TIMEOUT', 30), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | HTML Processing Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for HTML modification and creation.
    |
    */
    'html_processing' => [
        'max_html_size' => env('HTML_EDITOR_MAX_SIZE', 1048576), // 1MB in bytes
        'allowed_tags' => [
            'html', 'head', 'title', 'meta', 'link', 'style', 'script',
            'body', 'div', 'span', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'a', 'img', 'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tr', 'td', 'th',
            'form', 'input', 'textarea', 'select', 'option', 'button', 'label',
            'header', 'nav', 'main', 'section', 'article', 'aside', 'footer',
            'strong', 'em', 'b', 'i', 'u', 'br', 'hr', 'small', 'sub', 'sup'
        ],
        'sanitize_output' => env('HTML_EDITOR_SANITIZE', true),
        'minify_output' => env('HTML_EDITOR_MINIFY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for HTML file storage and versioning.
    |
    */
    'storage' => [
        'disk' => env('HTML_EDITOR_STORAGE_DISK', 's3'),
        'path' => env('HTML_EDITOR_STORAGE_PATH', 'html-modifications'),
        'keep_versions' => env('HTML_EDITOR_KEEP_VERSIONS', 10),
        'backup_enabled' => env('HTML_EDITOR_BACKUP_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting configuration for API endpoints.
    |
    */
    'rate_limiting' => [
        'modify_requests_per_minute' => env('HTML_EDITOR_RATE_LIMIT', 10),
        'create_requests_per_minute' => env('HTML_EDITOR_CREATE_RATE_LIMIT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Prompt Templates
    |--------------------------------------------------------------------------
    |
    | Default prompt templates for common HTML modification tasks.
    |
    */
    'prompts' => [
        'modify_html' => "You are an expert HTML/CSS developer. I need you to modify the following HTML content based on the user's request. Please ensure the output is valid HTML and maintains the structure and functionality of the original content.\n\nOriginal HTML:\n{html}\n\nUser Request: {prompt}\n\nPlease provide only the modified HTML without any explanation or markdown formatting.",
        
        'create_html' => "You are an expert HTML/CSS developer. I need you to create a new HTML webpage based on the following requirements. Please create a complete, valid HTML document with proper structure, semantic markup, and inline CSS styling.\n\nRequirements: {prompt}\n\nPlease provide only the HTML code without any explanation or markdown formatting.",
        
        'validate_html' => "Please validate and fix any issues in the following HTML code. Ensure it follows HTML5 standards, has proper structure, and is semantically correct:\n\n{html}",
    ],
];
