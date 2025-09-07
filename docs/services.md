# Services

The Bedrock HTML Editor package includes several services that handle the core functionality of the package.

## BedrockAgentService

This service handles communication with Amazon Bedrock AgentCore. It initializes the AWS SDK client, invokes the Bedrock agent, and processes the responses.

### Key Methods

#### `invokeAgent($prompt, $sessionId = null)`

Invokes the Bedrock agent with a prompt and returns the response.

**Parameters:**
- `$prompt` (string): The prompt to send to the Bedrock agent
- `$sessionId` (string|null): Optional session ID for the Bedrock agent

**Returns:**
- array: The response from the Bedrock agent

```php
public function invokeAgent(string $prompt, ?string $sessionId = null): array
{
    try {
        // Generate a session ID if not provided
        $sessionId = $sessionId ?: $this->generateSessionId();
        
        // Invoke the Bedrock agent
        $response = $this->client->invokeAgent([
            'agentId' => $this->config['bedrock']['agent_id'],
            'agentAliasId' => $this->config['bedrock']['agent_alias_id'],
            'sessionId' => $sessionId,
            'inputText' => $prompt,
        ]);
        
        // Process the response
        $completion = '';
        foreach ($response['completion'] as $chunk) {
            if (isset($chunk['chunk']['bytes'])) {
                $completion .= $chunk['chunk']['bytes'];
            }
        }
        
        return [
            'success' => true,
            'completion' => $completion,
            'session_id' => $sessionId,
        ];
    } catch (\Exception $e) {
        $this->logError('Error invoking Bedrock agent', [
            'error' => $e->getMessage(),
            'prompt' => $prompt,
        ]);
        
        return [
            'success' => false,
            'error' => 'An unexpected error occurred',
            'message' => $e->getMessage(),
        ];
    }
}
```

#### `generateSessionId()`

Generates a unique session ID for the Bedrock agent.

**Returns:**
- string: A unique session ID

```php
public function generateSessionId(): string
{
    return 'session-' . uniqid() . '-' . time();
}
```

#### `validateConfiguration()`

Validates the configuration for the Bedrock agent.

**Returns:**
- array: Validation result

```php
public function validateConfiguration(): array
{
    $errors = [];
    
    // Check AWS credentials
    if (empty($this->config['aws']['key'])) {
        $errors[] = 'AWS access key is required';
    }
    
    if (empty($this->config['aws']['secret'])) {
        $errors[] = 'AWS secret key is required';
    }
    
    // Check Bedrock configuration
    if (empty($this->config['bedrock']['agent_id'])) {
        $errors[] = 'Bedrock agent ID is required';
    }
    
    if (empty($this->config['bedrock']['agent_alias_id'])) {
        $errors[] = 'Bedrock agent alias ID is required';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
    ];
}
```

#### `testConnection()`

Tests the connection to the Bedrock agent.

**Returns:**
- array: Test result

```php
public function testConnection(): array
{
    try {
        $result = $this->invokeAgent('Hello, this is a test message.');
        
        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'Connection successful' : 'Connection failed',
            'details' => $result,
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Connection failed',
            'error' => $e->getMessage(),
        ];
    }
}
```

## HtmlProcessingService

This service handles the processing of HTML content. It uses the BedrockAgentService to modify existing HTML or create new HTML based on user prompts.

### Key Methods

#### `modifyHtml($html, $prompt, $sessionId = null)`

Modifies existing HTML content based on a user prompt.

**Parameters:**
- `$html` (string): The HTML content to modify
- `$prompt` (string): The prompt to send to the Bedrock agent
- `$sessionId` (string|null): Optional session ID for the Bedrock agent

**Returns:**
- array: The modified HTML content and metadata

```php
public function modifyHtml(string $html, string $prompt, ?string $sessionId = null): array
{
    try {
        // Validate the HTML
        $validationResult = $this->validateHtml($html);
        if (!$validationResult['valid'] && $this->config['html_processing']['strict_validation']) {
            return [
                'success' => false,
                'error' => 'Invalid HTML',
                'validation' => $validationResult,
            ];
        }
        
        // Create the prompt for the Bedrock agent
        $fullPrompt = str_replace(
            ['{html}', '{prompt}'],
            [$html, $prompt],
            $this->config['prompts']['modify_html']
        );
        
        // Invoke the Bedrock agent
        $result = $this->bedrockService->invokeAgent($fullPrompt, $sessionId);
        
        if (!$result['success']) {
            return $result;
        }
        
        // Extract the HTML from the response
        $modifiedHtml = $this->extractHtmlFromResponse($result['completion']);
        
        // Validate the modified HTML
        $validationResult = $this->validateHtml($modifiedHtml);
        
        // Sanitize the HTML if configured
        if ($this->config['html_processing']['sanitize_output']) {
            $modifiedHtml = $this->sanitizeHtml($modifiedHtml);
        }
        
        // Minify the HTML if configured
        if ($this->config['html_processing']['minify_output']) {
            $modifiedHtml = $this->minifyHtml($modifiedHtml);
        }
        
        return [
            'success' => true,
            'original_html' => $html,
            'modified_html' => $modifiedHtml,
            'session_id' => $result['session_id'],
            'validation' => $validationResult,
            'size_before' => strlen($html),
            'size_after' => strlen($modifiedHtml),
        ];
    } catch (\Exception $e) {
        $this->logError('Error modifying HTML', [
            'error' => $e->getMessage(),
            'prompt' => $prompt,
        ]);
        
        return [
            'success' => false,
            'error' => 'An unexpected error occurred',
            'message' => $e->getMessage(),
        ];
    }
}
```

#### `createHtml($prompt, $sessionId = null)`

Creates new HTML content based on a user prompt.

**Parameters:**
- `$prompt` (string): The prompt to send to the Bedrock agent
- `$sessionId` (string|null): Optional session ID for the Bedrock agent

**Returns:**
- array: The created HTML content and metadata

```php
public function createHtml(string $prompt, ?string $sessionId = null): array
{
    try {
        // Create the prompt for the Bedrock agent
        $fullPrompt = str_replace(
            '{prompt}',
            $prompt,
            $this->config['prompts']['create_html']
        );
        
        // Invoke the Bedrock agent
        $result = $this->bedrockService->invokeAgent($fullPrompt, $sessionId);
        
        if (!$result['success']) {
            return $result;
        }
        
        // Extract the HTML from the response
        $html = $this->extractHtmlFromResponse($result['completion']);
        
        // Validate the HTML
        $validationResult = $this->validateHtml($html);
        
        // Sanitize the HTML if configured
        if ($this->config['html_processing']['sanitize_output']) {
            $html = $this->sanitizeHtml($html);
        }
        
        // Minify the HTML if configured
        if ($this->config['html_processing']['minify_output']) {
            $html = $this->minifyHtml($html);
        }
        
        return [
            'success' => true,
            'html' => $html,
            'session_id' => $result['session_id'],
            'validation' => $validationResult,
            'size' => strlen($html),
        ];
    } catch (\Exception $e) {
        $this->logError('Error creating HTML', [
            'error' => $e->getMessage(),
            'prompt' => $prompt,
        ]);
        
        return [
            'success' => false,
            'error' => 'An unexpected error occurred',
            'message' => $e->getMessage(),
        ];
    }
}
```

#### `validateHtml($html)`

Validates HTML content for structure and disallowed tags.

**Parameters:**
- `$html` (string): The HTML content to validate

**Returns:**
- array: Validation result

```php
public function validateHtml(string $html): array
{
    $errors = [];
    $warnings = [];
    
    // Check for empty HTML
    if (empty($html)) {
        $errors[] = 'HTML content is empty';
        return [
            'valid' => false,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
    
    // Check for basic HTML structure
    if (!preg_match('/<html.*?>.*?<\/html>/is', $html)) {
        $warnings[] = 'HTML content does not have proper <html> tags';
    }
    
    // Check for disallowed tags
    $dom = new \DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    $xpath = new \DOMXPath($dom);
    
    $allowedTags = $this->config['html_processing']['allowed_tags'];
    $allTags = $xpath->query('//*');
    
    foreach ($allTags as $tag) {
        if (!in_array(strtolower($tag->nodeName), $allowedTags)) {
            $errors[] = "Disallowed tag: {$tag->nodeName}";
        }
    }
    
    // Check for inline scripts and event handlers
    if ($this->config['html_processing']['sanitize_output']) {
        $scripts = $xpath->query('//script');
        if ($scripts->length > 0) {
            $warnings[] = 'HTML contains inline scripts that will be sanitized';
        }
        
        $eventAttributes = $xpath->query('//@*[starts-with(name(), "on")]');
        if ($eventAttributes->length > 0) {
            $warnings[] = 'HTML contains event handlers that will be sanitized';
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings,
    ];
}
```

#### `sanitizeHtml($html)`

Sanitizes HTML content by removing potentially dangerous elements and attributes.

**Parameters:**
- `$html` (string): The HTML content to sanitize

**Returns:**
- string: The sanitized HTML content

```php
public function sanitizeHtml(string $html): string
{
    // Use a library like HTML Purifier to sanitize the HTML
    // This is a simplified example
    $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
    $html = preg_replace('/on\w+="[^"]*"/i', '', $html);
    
    return $html;
}
```

#### `minifyHtml($html)`

Minifies HTML content by removing whitespace and comments.

**Parameters:**
- `$html` (string): The HTML content to minify

**Returns:**
- string: The minified HTML content

```php
public function minifyHtml(string $html): string
{
    $htmlMin = new \voku\helper\HtmlMin();
    $htmlMin->doRemoveComments();
    $htmlMin->doRemoveWhitespaceAroundTags();
    $htmlMin->doOptimizeAttributes();
    
    return $htmlMin->minify($html);
}
```

## S3StorageService

This service handles the storage of HTML files in Amazon S3.

### Key Methods

#### `storeHtml($html, $filename, $siteId, $metadata = [])`

Stores HTML content in S3.

**Parameters:**
- `$html` (string): The HTML content to store
- `$filename` (string): The filename for the HTML file
- `$siteId` (int): The site ID
- `$metadata` (array): Optional metadata to store with the HTML file

**Returns:**
- array: Storage result

```php
public function storeHtml(string $html, string $filename, int $siteId, array $metadata = []): array
{
    try {
        $site = Site::find($siteId);
        if (!$site) {
            return [
                'success' => false,
                'error' => 'Site not found',
            ];
        }

        // Ensure the filename has .html extension
        if (!Str::endsWith($filename, '.html')) {
            $filename .= '.html';
        }

        // Create the path for the HTML file
        $path = $site->site_name . '/pages/' . $filename;
        
        // Store the HTML file in S3
        $stored = Storage::disk('s3')->put($path, $html);
        
        if (!$stored) {
            return [
                'success' => false,
                'error' => 'Failed to store HTML file in S3',
            ];
        }

        // Store metadata if provided
        if (!empty($metadata)) {
            $metadataPath = $site->site_name . '/pages/' . pathinfo($filename, PATHINFO_FILENAME) . '.meta.json';
            Storage::disk('s3')->put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
        }

        return [
            'success' => true,
            'path' => $path,
            'url' => S3StorageHelper::getPublicUrl($path),
            'size' => strlen($html),
        ];
    } catch (\Exception $e) {
        Log::error('Error storing HTML in S3', [
            'error' => $e->getMessage(),
            'filename' => $filename,
            'site_id' => $siteId,
        ]);

        return [
            'success' => false,
            'error' => 'Failed to store HTML file: ' . $e->getMessage(),
        ];
    }
}
```

#### `retrieveHtml($path)`

Retrieves HTML content from S3.

**Parameters:**
- `$path` (string): The path to the HTML file in S3

**Returns:**
- array: The HTML content and metadata

```php
public function retrieveHtml(string $path): array
{
    try {
        if (!Storage::disk('s3')->exists($path)) {
            return [
                'success' => false,
                'error' => 'HTML file not found',
            ];
        }

        $html = Storage::disk('s3')->get($path);
        $metadata = [];

        // Check if metadata exists
        $metadataPath = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME) . '.meta.json';
        if (Storage::disk('s3')->exists($metadataPath)) {
            $metadata = json_decode(Storage::disk('s3')->get($metadataPath), true) ?: [];
        }

        return [
            'success' => true,
            'html' => $html,
            'metadata' => $metadata,
            'size' => strlen($html),
            'last_modified' => Storage::disk('s3')->lastModified($path),
            'url' => S3StorageHelper::getPublicUrl($path),
        ];
    } catch (\Exception $e) {
        Log::error('Error retrieving HTML from S3', [
            'error' => $e->getMessage(),
            'path' => $path,
        ]);

        return [
            'success' => false,
            'error' => 'Failed to retrieve HTML file: ' . $e->getMessage(),
        ];
    }
}
```

## S3StorageHelper

This helper class provides utility methods for working with S3 storage.

### Key Methods

#### `getPublicUrl($path)`

Generates a public URL for an S3 object.

**Parameters:**
- `$path` (string): The path to the S3 object

**Returns:**
- string: The public URL for the S3 object

```php
public static function getPublicUrl(string $path): string
{
    // Use the app URL and append the storage path
    return Config::get('app.url') . '/storage/' . $path;
}
```
