# Troubleshooting

This guide provides solutions to common issues you might encounter when using the Bedrock HTML Editor package.

## Installation Issues

### Package Not Found

**Issue**: Composer cannot find the package.

**Solution**: Make sure you have added the repository to your `composer.json` file:

```json
"repositories": [
    {
        "type": "path",
        "url": "packages/prasso/bedrock-html-editor"
    }
]
```

### Migration Failed

**Issue**: The migration fails with an error.

**Solution**: Make sure your database connection is configured correctly and that you have the necessary permissions to create tables. You can try running the migration with the `--verbose` flag to get more information:

```bash
php artisan migrate --verbose
```

## Configuration Issues

### AWS Credentials Not Working

**Issue**: The package cannot connect to AWS services.

**Solution**: Check your AWS credentials in the `.env` file:

```
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
```

Make sure the credentials have the necessary permissions to access Bedrock AgentCore services.

### Bedrock Agent Not Found

**Issue**: The package cannot find the Bedrock agent.

**Solution**: Check your Bedrock agent configuration in the `.env` file:

```
BEDROCK_AGENT_ID=your_agent_id
BEDROCK_AGENT_ALIAS_ID=your_agent_alias_id
```

Make sure the agent exists and is properly configured in the AWS Bedrock console.

## API Issues

### Authentication Failed

**Issue**: API requests return a 401 Unauthorized error.

**Solution**: Make sure you are including the correct API token in the request header:

```
Authorization: Bearer YOUR_API_TOKEN
```

You can generate a new token using Laravel Sanctum:

```php
$user = User::find(1);
$token = $user->createToken('api-token')->plainTextToken;
```

### Rate Limiting

**Issue**: API requests are being rate limited.

**Solution**: The package includes rate limiting to prevent abuse. You can adjust the rate limits in the configuration file:

```php
'rate_limiting' => [
    'modify_requests_per_minute' => env('HTML_EDITOR_RATE_LIMIT', 10),
    'create_requests_per_minute' => env('HTML_EDITOR_CREATE_RATE_LIMIT', 5),
],
```

## HTML Processing Issues

### Invalid HTML

**Issue**: The package reports that the HTML is invalid.

**Solution**: Make sure the HTML you are providing is well-formed and follows HTML5 standards. You can use an online HTML validator to check your HTML.

If you need to disable strict validation, you can set the `strict_validation` option to `false` in the configuration file:

```php
'html_processing' => [
    'strict_validation' => false,
    // ...
],
```

### HTML Too Large

**Issue**: The package reports that the HTML is too large.

**Solution**: The package has a limit on the size of HTML content to prevent memory issues. You can adjust this limit in the configuration file:

```php
'html_processing' => [
    'max_html_size' => env('HTML_EDITOR_MAX_SIZE', 1048576), // 1MB in bytes
    // ...
],
```

## Bedrock Agent Issues

### Agent Not Responding

**Issue**: The Bedrock agent is not responding or is timing out.

**Solution**: Check the agent's status in the AWS Bedrock console. Make sure it is active and properly configured.

You can also adjust the timeout setting in the configuration file:

```php
'bedrock' => [
    'timeout' => env('BEDROCK_TIMEOUT', 30), // seconds
    // ...
],
```

### Poor Quality Responses

**Issue**: The Bedrock agent is returning poor quality HTML or not following the prompt correctly.

**Solution**: You can adjust the model parameters in the configuration file:

```php
'bedrock' => [
    'model_id' => env('BEDROCK_MODEL_ID', 'anthropic.claude-3-sonnet-20240229-v1:0'),
    'max_tokens' => env('BEDROCK_MAX_TOKENS', 4000),
    'temperature' => env('BEDROCK_TEMPERATURE', 0.7),
    // ...
],
```

Try lowering the temperature for more deterministic responses, or increasing it for more creative responses.

You can also customize the prompt templates in the configuration file:

```php
'prompts' => [
    'modify_html' => "You are an expert HTML/CSS developer. I need you to modify the following HTML content based on the user's request. Please ensure the output is valid HTML and maintains the structure and functionality of the original content.\n\nOriginal HTML:\n{html}\n\nUser Request: {prompt}\n\nPlease provide only the modified HTML without any explanation or markdown formatting.",
    // ...
],
```

## Storage Issues

### S3 Storage Failed

**Issue**: The package cannot store HTML files in S3.

**Solution**: Check your S3 configuration in the `.env` file:

```
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_bucket
```

Make sure the bucket exists and the credentials have the necessary permissions to write to it.

You can also check the storage configuration in the package configuration file:

```php
'storage' => [
    'disk' => env('HTML_EDITOR_STORAGE_DISK', 's3'),
    'path' => env('HTML_EDITOR_STORAGE_PATH', 'html-modifications'),
    // ...
],
```

## Debugging

### Enabling Debug Mode

To enable debug mode, set the `APP_DEBUG` environment variable to `true` in your `.env` file:

```
APP_DEBUG=true
```

This will provide more detailed error messages when something goes wrong.

### Logging

The package logs errors and important events to the Laravel log. You can check the log file at `storage/logs/laravel.log` for more information about errors.

You can also customize the logging behavior in the `config/logging.php` file.

### Testing the Bedrock Agent Connection

You can test the connection to the Bedrock agent using the `BedrockAgentService`:

```php
use Prasso\BedrockHtmlEditor\Services\BedrockAgentService;

$bedrockService = app(BedrockAgentService::class);
$result = $bedrockService->testConnection();

if ($result['success']) {
    echo "Connection successful!";
} else {
    echo "Connection failed: " . $result['error'];
}
```

## Common Error Messages

### "AWS access key is required"

This error occurs when the AWS access key is not set in the `.env` file. Make sure you have set the `AWS_ACCESS_KEY_ID` environment variable.

### "Bedrock agent ID is required"

This error occurs when the Bedrock agent ID is not set in the `.env` file. Make sure you have set the `BEDROCK_AGENT_ID` environment variable.

### "HTML content is too large"

This error occurs when the HTML content exceeds the maximum size limit. You can adjust this limit in the configuration file.

### "Disallowed tag: script"

This error occurs when the HTML content contains a tag that is not allowed. You can adjust the list of allowed tags in the configuration file.

## Getting Help

If you encounter an issue that is not covered in this troubleshooting guide, you can:

1. Check the [Laravel documentation](https://laravel.com/docs) for general Laravel issues
2. Check the [AWS Bedrock documentation](https://docs.aws.amazon.com/bedrock/) for Bedrock-specific issues
3. Open an issue on the [GitHub repository](https://github.com/prasso/bedrock-html-editor) for the package
4. Contact the package maintainers for support
