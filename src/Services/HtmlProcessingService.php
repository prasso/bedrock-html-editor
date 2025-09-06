<?php

namespace Prasso\BedrockHtmlEditor\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use voku\helper\HtmlMin;
use DOMDocument;
use DOMXPath;
use App\Models\Site;
use App\Models\SitePages;

class HtmlProcessingService
{
    protected BedrockAgentService $bedrockService;
    protected S3StorageService $s3StorageService;
    protected array $config;

    public function __construct(BedrockAgentService $bedrockService, S3StorageService $s3StorageService = null)
    {
        $this->bedrockService = $bedrockService;
        $this->s3StorageService = $s3StorageService ?? new S3StorageService();
        $this->config = config('bedrock-html-editor');
    }

    /**
     * Modify existing HTML content based on user prompt
     *
     * @param string $html
     * @param string $prompt
     * @param string|null $sessionId
     * @return array
     */
    public function modifyHtml(string $html, string $prompt, ?string $sessionId = null): array
    {
        try {
            // Validate input HTML size
            if (strlen($html) > $this->config['html_processing']['max_html_size']) {
                return [
                    'success' => false,
                    'error' => 'HTML content exceeds maximum allowed size',
                ];
            }

            // Validate HTML structure
            $validationResult = $this->validateHtml($html);
            if (!$validationResult['valid']) {
                Log::warning('Invalid HTML provided for modification', [
                    'errors' => $validationResult['errors'],
                ]);
            }

            // Prepare prompt using template
            $fullPrompt = str_replace(
                ['{html}', '{prompt}'],
                [$html, $prompt],
                $this->config['prompts']['modify_html']
            );

            // Send to Bedrock AgentCore
            $response = $this->bedrockService->invokeAgent($fullPrompt, $sessionId);

            if (!$response['success']) {
                return $response;
            }

            $modifiedHtml = $this->extractHtmlFromResponse($response['completion']);

            // Sanitize output if enabled
            if ($this->config['html_processing']['sanitize_output']) {
                $modifiedHtml = $this->sanitizeHtml($modifiedHtml);
            }

            // Minify output if enabled
            if ($this->config['html_processing']['minify_output']) {
                $modifiedHtml = $this->minifyHtml($modifiedHtml);
            }

            // Validate the modified HTML
            $modifiedValidation = $this->validateHtml($modifiedHtml);

            return [
                'success' => true,
                'original_html' => $html,
                'modified_html' => $modifiedHtml,
                'prompt' => $prompt,
                'session_id' => $response['session_id'] ?? null,
                'validation' => $modifiedValidation,
                'size_before' => strlen($html),
                'size_after' => strlen($modifiedHtml),
            ];

        } catch (\Exception $e) {
            Log::error('Error during HTML modification', [
                'error' => $e->getMessage(),
                'prompt' => $prompt,
                'html_length' => strlen($html),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to modify HTML: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create new HTML content based on user prompt
     *
     * @param string $prompt
     * @param string|null $sessionId
     * @return array
     */
    public function createHtml(string $prompt, ?string $sessionId = null): array
    {
        try {
            // Prepare prompt using template
            $fullPrompt = str_replace(
                '{prompt}',
                $prompt,
                $this->config['prompts']['create_html']
            );

            // Send to Bedrock AgentCore
            $response = $this->bedrockService->invokeAgent($fullPrompt, $sessionId);

            if (!$response['success']) {
                return $response;
            }

            $html = $this->extractHtmlFromResponse($response['completion']);

            // Sanitize output if enabled
            if ($this->config['html_processing']['sanitize_output']) {
                $html = $this->sanitizeHtml($html);
            }

            // Minify output if enabled
            if ($this->config['html_processing']['minify_output']) {
                $html = $this->minifyHtml($html);
            }

            // Validate the created HTML
            $validation = $this->validateHtml($html);

            return [
                'success' => true,
                'html' => $html,
                'prompt' => $prompt,
                'session_id' => $response['session_id'] ?? null,
                'validation' => $validation,
                'size' => strlen($html),
            ];

        } catch (\Exception $e) {
            Log::error('Error during HTML creation', [
                'error' => $e->getMessage(),
                'prompt' => $prompt,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create HTML: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Extract HTML content from Bedrock response
     *
     * @param string $response
     * @return string
     */
    protected function extractHtmlFromResponse(string $response): string
    {
        // Remove markdown code blocks if present
        $html = preg_replace('/```html\s*(.*?)\s*```/s', '$1', $response);
        $html = preg_replace('/```\s*(.*?)\s*```/s', '$1', $html);

        // Trim whitespace
        $html = trim($html);

        // If no HTML tags detected, wrap in basic HTML structure
        if (!preg_match('/<html|<HTML/', $html) && !empty($html)) {
            if (!preg_match('/<body|<BODY/', $html)) {
                $html = "<html>\n<head>\n<title>Generated Page</title>\n</head>\n<body>\n{$html}\n</body>\n</html>";
            }
        }

        return $html;
    }

    /**
     * Validate HTML structure and content
     *
     * @param string $html
     * @return array
     */
    public function validateHtml(string $html): array
    {
        $errors = [];
        $warnings = [];

        try {
            // Create DOMDocument for validation
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->validateOnParse = true;
            
            // Suppress warnings for validation
            libxml_use_internal_errors(true);
            $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            
            $libxmlErrors = libxml_get_errors();
            foreach ($libxmlErrors as $error) {
                if ($error->level === LIBXML_ERR_ERROR || $error->level === LIBXML_ERR_FATAL) {
                    $errors[] = trim($error->message);
                } else {
                    $warnings[] = trim($error->message);
                }
            }
            
            libxml_clear_errors();
            libxml_use_internal_errors(false);

            // Check for disallowed tags
            $xpath = new DOMXPath($dom);
            $allElements = $xpath->query('//*');
            
            foreach ($allElements as $element) {
                $tagName = strtolower($element->nodeName);
                if (!in_array($tagName, $this->config['html_processing']['allowed_tags'])) {
                    $warnings[] = "Potentially unsafe or disallowed tag: {$tagName}";
                }
            }

            // Check for inline scripts (potential security risk)
            $scripts = $xpath->query('//script[not(@src)]');
            if ($scripts->length > 0) {
                $warnings[] = 'Inline scripts detected - potential security risk';
            }

            // Check for inline event handlers
            $eventHandlers = $xpath->query('//*[@onclick or @onload or @onerror or @onmouseover]');
            if ($eventHandlers->length > 0) {
                $warnings[] = 'Inline event handlers detected - potential security risk';
            }

        } catch (\Exception $e) {
            $errors[] = 'Failed to parse HTML: ' . $e->getMessage();
        }

        return [
            'valid' => empty($errors),
            'errors' => array_unique($errors),
            'warnings' => array_unique($warnings),
        ];
    }

    /**
     * Sanitize HTML content
     *
     * @param string $html
     * @return string
     */
    protected function sanitizeHtml(string $html): string
    {
        try {
            $dom = new DOMDocument('1.0', 'UTF-8');
            libxml_use_internal_errors(true);
            $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            
            $xpath = new DOMXPath($dom);

            // Remove potentially dangerous elements
            $dangerousElements = $xpath->query('//script[not(@src)] | //object | //embed | //iframe[not(@src)]');
            foreach ($dangerousElements as $element) {
                $element->parentNode->removeChild($element);
            }

            // Remove dangerous attributes
            $allElements = $xpath->query('//*');
            foreach ($allElements as $element) {
                $attributesToRemove = [];
                foreach ($element->attributes as $attr) {
                    if (strpos($attr->name, 'on') === 0) { // onclick, onload, etc.
                        $attributesToRemove[] = $attr->name;
                    }
                }
                
                foreach ($attributesToRemove as $attrName) {
                    $element->removeAttribute($attrName);
                }
            }

            libxml_use_internal_errors(false);
            return $dom->saveHTML();

        } catch (\Exception $e) {
            Log::warning('HTML sanitization failed, returning original', [
                'error' => $e->getMessage()
            ]);
            return $html;
        }
    }

    /**
     * Minify HTML content
     *
     * @param string $html
     * @return string
     */
    protected function minifyHtml(string $html): string
    {
        try {
            $htmlMin = new HtmlMin();
            $htmlMin->doOptimizeViaHtmlDomParser(true);
            $htmlMin->doRemoveComments(true);
            $htmlMin->doSumUpWhitespace(true);
            $htmlMin->doRemoveWhitespaceAroundTags(true);
            
            return $htmlMin->minify($html);
        } catch (\Exception $e) {
            Log::warning('HTML minification failed, returning original', [
                'error' => $e->getMessage()
            ]);
            return $html;
        }
    }

    /**
     * Save HTML content to storage
     *
     * @param string $html
     * @param string $filename
     * @param int $siteId
     * @param array $metadata
     * @return array
     */
    public function saveHtml(string $html, string $filename, int $siteId, array $metadata = []): array
    {
        return $this->s3StorageService->storeHtml($html, $filename, $siteId, $metadata);
    }

    /**
     * Load HTML content from storage
     *
     * @param string $path
     * @return array
     */
    public function loadHtml(string $path): array
    {
        return $this->s3StorageService->retrieveHtml($path);
    }
    
    /**
     * Save HTML content for a specific site page
     *
     * @param string $html
     * @param int $siteId
     * @param int $pageId
     * @param array $metadata
     * @return array
     */
    public function savePageHtml(string $html, int $siteId, int $pageId, array $metadata = []): array
    {
        return $this->s3StorageService->storePageHtml($html, $siteId, $pageId, $metadata);
    }
    
    /**
     * List all HTML files for a site
     *
     * @param int $siteId
     * @return array
     */
    public function listSiteHtmlFiles(int $siteId): array
    {
        return $this->s3StorageService->listSiteHtmlFiles($siteId);
    }
    
    /**
     * Delete an HTML file from storage
     *
     * @param string $path
     * @return array
     */
    public function deleteHtml(string $path): array
    {
        return $this->s3StorageService->deleteHtml($path);
    }
}
