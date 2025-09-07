# Usage

This document provides detailed examples of how to use the Bedrock HTML Editor package in your Laravel application.

## Basic Usage

### Modifying HTML

To modify existing HTML content based on a user prompt:

```php
use Prasso\BedrockHtmlEditor\Services\HtmlProcessingService;

class PageController extends Controller
{
    protected $htmlService;
    
    public function __construct(HtmlProcessingService $htmlService)
    {
        $this->htmlService = $htmlService;
    }
    
    public function modifyPage(Request $request)
    {
        $html = $request->input('html');
        $prompt = $request->input('prompt');
        
        $result = $this->htmlService->modifyHtml($html, $prompt);
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'html' => $result['modified_html'],
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 500);
        }
    }
}
```

### Creating HTML

To create new HTML content based on a user prompt:

```php
use Prasso\BedrockHtmlEditor\Services\HtmlProcessingService;

class PageController extends Controller
{
    protected $htmlService;
    
    public function __construct(HtmlProcessingService $htmlService)
    {
        $this->htmlService = $htmlService;
    }
    
    public function createPage(Request $request)
    {
        $prompt = $request->input('prompt');
        
        $result = $this->htmlService->createHtml($prompt);
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'html' => $result['html'],
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 500);
        }
    }
}
```

### Saving HTML to Storage

To save HTML content to S3 storage:

```php
use Prasso\BedrockHtmlEditor\Services\HtmlProcessingService;

class PageController extends Controller
{
    protected $htmlService;
    
    public function __construct(HtmlProcessingService $htmlService)
    {
        $this->htmlService = $htmlService;
    }
    
    public function savePage(Request $request)
    {
        $html = $request->input('html');
        $filename = $request->input('filename');
        $siteId = $request->input('site_id');
        $metadata = [
            'title' => $request->input('title'),
            'author' => $request->user()->name,
        ];
        
        $result = $this->htmlService->saveHtml($html, $filename, $siteId, $metadata);
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'path' => $result['path'],
                'url' => $result['url'],
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 500);
        }
    }
}
```

## API Usage

### Using the API Endpoints

The package provides several API endpoints for modifying and creating HTML content, managing templates, and working with HTML components. All API endpoints require authentication using Laravel Sanctum.

#### Modifying HTML

```javascript
// Example using fetch API
const response = await fetch('/api/bedrock-html-editor/modify', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer YOUR_API_TOKEN',
    },
    body: JSON.stringify({
        html: '<html><body><h1>Hello World</h1></body></html>',
        prompt: 'Change the heading to "Welcome to My Website"',
        site_id: 1,
        page_id: 5,
        title: 'Modified Page',
    }),
});

const data = await response.json();
if (data.success) {
    const modifiedHtml = data.html;
    // Use the modified HTML
}
```

#### Creating HTML

```javascript
// Example using fetch API
const response = await fetch('/api/bedrock-html-editor/create', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer YOUR_API_TOKEN',
    },
    body: JSON.stringify({
        prompt: 'Create a landing page for a coffee shop with a header, about section, menu section, and contact form',
        site_id: 1,
        title: 'Coffee Shop Landing Page',
    }),
});

const data = await response.json();
if (data.success) {
    const newHtml = data.html;
    // Use the new HTML
}
```

#### Getting Modification History

```javascript
// Example using fetch API
const response = await fetch('/api/bedrock-html-editor/modifications?site_id=1&page_id=5', {
    method: 'GET',
    headers: {
        'Authorization': 'Bearer YOUR_API_TOKEN',
    },
});

const data = await response.json();
if (data.success) {
    const modifications = data.modifications;
    // Use the modifications
}
```

#### Applying a Modification to a Page

```javascript
// Example using fetch API
const response = await fetch(`/api/bedrock-html-editor/modifications/${modificationId}/apply`, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer YOUR_API_TOKEN',
    },
    body: JSON.stringify({
        page_id: 5,
    }),
});

const data = await response.json();
if (data.success) {
    // Modification applied successfully
}
```

## Working with Templates

### Getting Templates

```javascript
// Example using fetch API
const response = await fetch('/api/bedrock-html-editor/templates?category=landing-page', {
    method: 'GET',
    headers: {
        'Authorization': 'Bearer YOUR_API_TOKEN',
    },
});

const data = await response.json();
if (data.success) {
    const templates = data.templates;
    // Use the templates
}
```

### Creating a Template

```javascript
// Example using fetch API
const response = await fetch('/api/bedrock-html-editor/templates', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer YOUR_API_TOKEN',
    },
    body: JSON.stringify({
        name: 'Product Page',
        category: 'product',
        description: 'A template for product pages',
        html_content: '<!DOCTYPE html><html>...</html>',
        thumbnail_url: 'https://example.com/thumbnails/product-page.png',
        is_active: true,
    }),
});

const data = await response.json();
if (data.success) {
    const template = data.template;
    // Use the template
}
```

## Working with Components

### Getting Components

```javascript
// Example using fetch API
const response = await fetch('/api/bedrock-html-editor/components?site_id=1&type=header', {
    method: 'GET',
    headers: {
        'Authorization': 'Bearer YOUR_API_TOKEN',
    },
});

const data = await response.json();
if (data.success) {
    const components = data.components;
    // Use the components
}
```

### Creating a Component

```javascript
// Example using fetch API
const response = await fetch('/api/bedrock-html-editor/components', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer YOUR_API_TOKEN',
    },
    body: JSON.stringify({
        name: 'Contact Form',
        type: 'form',
        description: 'Contact form component',
        html_content: '<form>...</form>',
        css_content: 'form { ... }',
        js_content: 'document.querySelector("form").addEventListener("submit", function(e) { ... });',
        thumbnail_url: 'https://example.com/thumbnails/contact-form.png',
        is_global: false,
        site_id: 1,
    }),
});

const data = await response.json();
if (data.success) {
    const component = data.component;
    // Use the component
}
```

## Advanced Usage

### Using Session IDs for Conversation Context

You can use session IDs to maintain context between multiple requests to the Bedrock agent:

```php
use Prasso\BedrockHtmlEditor\Services\HtmlProcessingService;
use Prasso\BedrockHtmlEditor\Services\BedrockAgentService;

class PageController extends Controller
{
    protected $htmlService;
    protected $bedrockService;
    
    public function __construct(HtmlProcessingService $htmlService, BedrockAgentService $bedrockService)
    {
        $this->htmlService = $htmlService;
        $this->bedrockService = $bedrockService;
    }
    
    public function startConversation(Request $request)
    {
        // Generate a session ID
        $sessionId = $this->bedrockService->generateSessionId();
        
        // Store the session ID in the session
        session(['bedrock_session_id' => $sessionId]);
        
        return response()->json([
            'success' => true,
            'session_id' => $sessionId,
        ]);
    }
    
    public function modifyPage(Request $request)
    {
        $html = $request->input('html');
        $prompt = $request->input('prompt');
        $sessionId = session('bedrock_session_id');
        
        $result = $this->htmlService->modifyHtml($html, $prompt, $sessionId);
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'html' => $result['modified_html'],
                'session_id' => $result['session_id'],
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 500);
        }
    }
}
```

### Customizing Prompt Templates

You can customize the prompt templates used by the package by publishing the configuration file and modifying the `prompts` section:

```php
// config/bedrock-html-editor.php
'prompts' => [
    'modify_html' => "You are an expert HTML/CSS developer specializing in responsive design. I need you to modify the following HTML content based on the user's request. Please ensure the output is valid HTML, responsive, and maintains the structure and functionality of the original content.\n\nOriginal HTML:\n{html}\n\nUser Request: {prompt}\n\nPlease provide only the modified HTML without any explanation or markdown formatting.",
    
    'create_html' => "You are an expert HTML/CSS developer specializing in responsive design. I need you to create a new HTML webpage based on the following requirements. Please create a complete, valid HTML document with proper structure, semantic markup, and responsive CSS styling using media queries.\n\nRequirements: {prompt}\n\nPlease provide only the HTML code without any explanation or markdown formatting.",
    
    'validate_html' => "Please validate and fix any issues in the following HTML code. Ensure it follows HTML5 standards, has proper structure, is semantically correct, and is responsive:\n\n{html}",
],
```

### Integrating with a Frontend Framework

Here's an example of how to integrate the package with a Vue.js frontend:

```vue
<template>
  <div>
    <div class="form-group">
      <label for="prompt">Prompt</label>
      <textarea id="prompt" v-model="prompt" class="form-control"></textarea>
    </div>
    
    <div class="form-group">
      <label for="html">HTML</label>
      <textarea id="html" v-model="html" class="form-control"></textarea>
    </div>
    
    <button @click="modifyHtml" class="btn btn-primary">Modify HTML</button>
    
    <div v-if="loading" class="mt-3">
      <div class="spinner-border" role="status">
        <span class="sr-only">Loading...</span>
      </div>
    </div>
    
    <div v-if="result" class="mt-3">
      <h3>Result</h3>
      <pre>{{ result }}</pre>
    </div>
    
    <div v-if="error" class="mt-3 alert alert-danger">
      {{ error }}
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      prompt: '',
      html: '<html><body><h1>Hello World</h1></body></html>',
      result: null,
      error: null,
      loading: false,
    };
  },
  
  methods: {
    async modifyHtml() {
      this.loading = true;
      this.result = null;
      this.error = null;
      
      try {
        const response = await fetch('/api/bedrock-html-editor/modify', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
          body: JSON.stringify({
            html: this.html,
            prompt: this.prompt,
            site_id: 1,
            title: 'Modified Page',
          }),
        });
        
        const data = await response.json();
        
        if (data.success) {
          this.result = data.html;
        } else {
          this.error = data.error || 'An error occurred';
        }
      } catch (error) {
        this.error = error.message;
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
```
