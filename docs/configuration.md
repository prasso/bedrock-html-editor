# Configuration

The Bedrock HTML Editor package provides extensive configuration options to customize its behavior. The configuration file is located at `config/bedrock-html-editor.php`.

## Publishing the Configuration

To publish the configuration file to your application, run the following command:

```bash
php artisan vendor:publish --provider="Prasso\BedrockHtmlEditor\BedrockHtmlEditorServiceProvider" --tag="bedrock-html-editor-config"
```

## Configuration Options

### AWS Configuration

```php
'aws' => [
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'version' => 'latest',
],
```

| Option | Description | Default |
|--------|-------------|---------|
| region | The AWS region where your Bedrock agent is deployed | `us-east-1` |
| key | Your AWS access key ID | `null` |
| secret | Your AWS secret access key | `null` |
| version | The AWS SDK version | `latest` |

### Bedrock Configuration

```php
'bedrock' => [
    'agent_id' => env('BEDROCK_AGENT_ID'),
    'agent_alias_id' => env('BEDROCK_AGENT_ALIAS_ID', 'TSTALIASID'),
    'session_id' => env('BEDROCK_SESSION_ID'),
    'model_id' => env('BEDROCK_MODEL_ID', 'anthropic.claude-3-sonnet-20240229-v1:0'),
    'max_tokens' => env('BEDROCK_MAX_TOKENS', 4000),
    'temperature' => env('BEDROCK_TEMPERATURE', 0.7),
    'timeout' => env('BEDROCK_TIMEOUT', 30), // seconds
],
```

| Option | Description | Default |
|--------|-------------|---------|
| agent_id | Your Bedrock agent ID | `null` |
| agent_alias_id | Your Bedrock agent alias ID | `TSTALIASID` |
| session_id | A default session ID for the Bedrock agent | `null` |
| model_id | The model ID to use for the Bedrock agent | `anthropic.claude-3-sonnet-20240229-v1:0` |
| max_tokens | The maximum number of tokens to generate | `4000` |
| temperature | The temperature for the model (0.0 to 1.0) | `0.7` |
| timeout | The timeout for the Bedrock agent in seconds | `30` |

### HTML Processing Configuration

```php
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
```

| Option | Description | Default |
|--------|-------------|---------|
| max_html_size | The maximum size of HTML content in bytes | `1048576` (1MB) |
| allowed_tags | An array of allowed HTML tags | See above |
| sanitize_output | Whether to sanitize the HTML output | `true` |
| minify_output | Whether to minify the HTML output | `false` |

### Storage Configuration

```php
'storage' => [
    'disk' => env('HTML_EDITOR_STORAGE_DISK', 's3'),
    'path' => env('HTML_EDITOR_STORAGE_PATH', 'html-modifications'),
    'keep_versions' => env('HTML_EDITOR_KEEP_VERSIONS', 10),
    'backup_enabled' => env('HTML_EDITOR_BACKUP_ENABLED', true),
],
```

| Option | Description | Default |
|--------|-------------|---------|
| disk | The storage disk to use for HTML files | `s3` |
| path | The base path for HTML files | `html-modifications` |
| keep_versions | The number of versions to keep for each HTML file | `10` |
| backup_enabled | Whether to create backups of HTML files | `true` |

### Rate Limiting Configuration

```php
'rate_limiting' => [
    'modify_requests_per_minute' => env('HTML_EDITOR_RATE_LIMIT', 10),
    'create_requests_per_minute' => env('HTML_EDITOR_CREATE_RATE_LIMIT', 5),
],
```

| Option | Description | Default |
|--------|-------------|---------|
| modify_requests_per_minute | The number of modify requests allowed per minute | `10` |
| create_requests_per_minute | The number of create requests allowed per minute | `5` |

### Prompt Templates

```php
'prompts' => [
    'modify_html' => "You are an expert HTML/CSS developer. I need you to modify the following HTML content based on the user's request. Please ensure the output is valid HTML and maintains the structure and functionality of the original content.\n\nOriginal HTML:\n{html}\n\nUser Request: {prompt}\n\nPlease provide only the modified HTML without any explanation or markdown formatting.",
    
    'create_html' => "You are an expert HTML/CSS developer. I need you to create a new HTML webpage based on the following requirements. Please create a complete, valid HTML document with proper structure, semantic markup, and inline CSS styling.\n\nRequirements: {prompt}\n\nPlease provide only the HTML code without any explanation or markdown formatting.",
    
    'validate_html' => "Please validate and fix any issues in the following HTML code. Ensure it follows HTML5 standards, has proper structure, and is semantically correct:\n\n{html}",
],
```

| Option | Description |
|--------|-------------|
| modify_html | The prompt template for modifying HTML content |
| create_html | The prompt template for creating new HTML content |
| validate_html | The prompt template for validating HTML content |

## Environment Variables

Here's a list of all the environment variables used by the Bedrock HTML Editor package:

```
# AWS Configuration
AWS_DEFAULT_REGION=us-east-1
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret

# Bedrock Configuration
BEDROCK_AGENT_ID=your_agent_id
BEDROCK_AGENT_ALIAS_ID=your_agent_alias_id
BEDROCK_SESSION_ID=optional_session_id
BEDROCK_MODEL_ID=anthropic.claude-3-sonnet-20240229-v1:0
BEDROCK_MAX_TOKENS=4000
BEDROCK_TEMPERATURE=0.7
BEDROCK_TIMEOUT=30

# HTML Processing Configuration
HTML_EDITOR_MAX_SIZE=1048576
HTML_EDITOR_SANITIZE=true
HTML_EDITOR_MINIFY=false

# Storage Configuration
HTML_EDITOR_STORAGE_DISK=s3
HTML_EDITOR_STORAGE_PATH=html-modifications
HTML_EDITOR_KEEP_VERSIONS=10
HTML_EDITOR_BACKUP_ENABLED=true

# Rate Limiting Configuration
HTML_EDITOR_RATE_LIMIT=10
HTML_EDITOR_CREATE_RATE_LIMIT=5
```
