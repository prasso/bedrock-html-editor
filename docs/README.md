# Bedrock HTML Editor

The Bedrock HTML Editor is a Laravel package that integrates Amazon Bedrock AgentCore to provide AI-powered HTML editing capabilities for your web applications. This package allows you to modify existing HTML pages or create new HTML pages based on user prompts.

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [API Reference](api-reference.md)
- [Models](models.md)
- [Services](services.md)
- [Testing](testing.md)
- [Troubleshooting](troubleshooting.md)

## Installation

### Requirements

- PHP 8.0+
- Laravel 9.0+ / 10.0+ / 11.0+
- AWS account with Bedrock AgentCore access
- S3 bucket for HTML storage

### Installation Steps

1. Add the package to your `composer.json` file:

```json
"require": {
    "prasso/bedrock-html-editor": "dev-main"
}
```

2. Add the repository to your `composer.json` file:

```json
"repositories": [
    {
        "type": "path",
        "url": "packages/prasso/bedrock-html-editor"
    }
]
```

3. Run Composer update:

```bash
composer update
```

4. Publish the configuration file:

```bash
php artisan vendor:publish --provider="Prasso\BedrockHtmlEditor\BedrockHtmlEditorServiceProvider" --tag="bedrock-html-editor-config"
```

5. Run the migrations:

```bash
php artisan migrate
```

## Configuration

Configure the package by editing the `.env` file with your AWS credentials and Bedrock AgentCore settings:

```
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
BEDROCK_AGENT_ID=your_agent_id
BEDROCK_AGENT_ALIAS_ID=your_agent_alias_id
```

For more detailed configuration options, see the [Configuration](configuration.md) documentation.

## Usage

### Basic Usage

#### Modifying HTML

```php
use Prasso\BedrockHtmlEditor\Services\HtmlProcessingService;

public function modifyPage(HtmlProcessingService $htmlService)
{
    $html = '<html><body><h1>Hello World</h1></body></html>';
    $prompt = 'Change the heading to "Welcome to My Website"';
    
    $result = $htmlService->modifyHtml($html, $prompt);
    
    if ($result['success']) {
        $modifiedHtml = $result['modified_html'];
        // Use the modified HTML
    }
}
```

#### Creating HTML

```php
use Prasso\BedrockHtmlEditor\Services\HtmlProcessingService;

public function createPage(HtmlProcessingService $htmlService)
{
    $prompt = 'Create a landing page for a coffee shop with a header, about section, menu section, and contact form';
    
    $result = $htmlService->createHtml($prompt);
    
    if ($result['success']) {
        $newHtml = $result['html'];
        // Use the new HTML
    }
}
```

For more usage examples, see the [Usage](usage.md) documentation.

## License

The Bedrock HTML Editor package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
