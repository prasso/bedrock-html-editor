# API Reference

The Bedrock HTML Editor package provides several API endpoints for modifying and creating HTML content, managing templates, and working with HTML components.

## Authentication

All API endpoints require authentication using Laravel Sanctum. Include your API token in the request header:

```
Authorization: Bearer YOUR_API_TOKEN
```

## Base URL

All API endpoints are prefixed with `/api/bedrock-html-editor`.

## HTML Editor Endpoints

### Modify HTML

Modifies existing HTML content based on a user prompt.

**Endpoint:** `POST /modify`

**Request Body:**

```json
{
  "html": "<html><body><h1>Hello World</h1></body></html>",
  "prompt": "Change the heading to 'Welcome to My Website'",
  "site_id": 1,
  "page_id": 5,
  "title": "Modified Page",
  "session_id": "optional-session-id",
  "save_to_storage": true
}
```

**Response:**

```json
{
  "success": true,
  "modification": {
    "id": 1,
    "user_id": 1,
    "site_id": 1,
    "page_id": 5,
    "title": "Modified Page",
    "prompt": "Change the heading to 'Welcome to My Website'",
    "original_html": "<html><body><h1>Hello World</h1></body></html>",
    "modified_html": "<html><body><h1>Welcome to My Website</h1></body></html>",
    "storage_path": "site-name/pages/modified-page-12345.html",
    "session_id": "session-12345",
    "created_at": "2025-09-06T12:00:00.000000Z",
    "updated_at": "2025-09-06T12:00:00.000000Z"
  },
  "html": "<html><body><h1>Welcome to My Website</h1></body></html>"
}
```

### Create HTML

Creates new HTML content based on a user prompt.

**Endpoint:** `POST /create`

**Request Body:**

```json
{
  "prompt": "Create a landing page for a coffee shop with a header, about section, menu section, and contact form",
  "site_id": 1,
  "title": "Coffee Shop Landing Page",
  "session_id": "optional-session-id",
  "save_to_storage": true
}
```

**Response:**

```json
{
  "success": true,
  "modification": {
    "id": 2,
    "user_id": 1,
    "site_id": 1,
    "title": "Coffee Shop Landing Page",
    "prompt": "Create a landing page for a coffee shop with a header, about section, menu section, and contact form",
    "modified_html": "<!DOCTYPE html><html>...</html>",
    "storage_path": "site-name/pages/coffee-shop-landing-page-12345.html",
    "session_id": "session-12345",
    "created_at": "2025-09-06T12:30:00.000000Z",
    "updated_at": "2025-09-06T12:30:00.000000Z"
  },
  "html": "<!DOCTYPE html><html>...</html>"
}
```

### Get Modification History

Retrieves the modification history for a site or page.

**Endpoint:** `GET /modifications?site_id=1&page_id=5&limit=10`

**Response:**

```json
{
  "success": true,
  "modifications": [
    {
      "id": 1,
      "user_id": 1,
      "site_id": 1,
      "page_id": 5,
      "title": "Modified Page",
      "prompt": "Change the heading to 'Welcome to My Website'",
      "storage_path": "site-name/pages/modified-page-12345.html",
      "created_at": "2025-09-06T12:00:00.000000Z",
      "updated_at": "2025-09-06T12:00:00.000000Z"
    }
  ]
}
```

### Get Modification

Retrieves a specific HTML modification.

**Endpoint:** `GET /modifications/{id}`

**Response:**

```json
{
  "success": true,
  "modification": {
    "id": 1,
    "user_id": 1,
    "site_id": 1,
    "page_id": 5,
    "title": "Modified Page",
    "prompt": "Change the heading to 'Welcome to My Website'",
    "original_html": "<html><body><h1>Hello World</h1></body></html>",
    "modified_html": "<html><body><h1>Welcome to My Website</h1></body></html>",
    "storage_path": "site-name/pages/modified-page-12345.html",
    "session_id": "session-12345",
    "created_at": "2025-09-06T12:00:00.000000Z",
    "updated_at": "2025-09-06T12:00:00.000000Z"
  }
}
```

### Apply Modification

Applies a modification to a site page.

**Endpoint:** `POST /modifications/{id}/apply`

**Request Body:**

```json
{
  "page_id": 5
}
```

**Response:**

```json
{
  "success": true,
  "message": "Modification applied successfully.",
  "page": {
    "id": 5,
    "fk_site_id": 1,
    "section": "home",
    "title": "Home Page",
    "description": "<html><body><h1>Welcome to My Website</h1></body></html>"
  },
  "modification": {
    "id": 1,
    "page_id": 5,
    "is_published": true
  }
}
```

## Template Endpoints

### Get Templates

Retrieves all templates or templates by category.

**Endpoint:** `GET /templates?category=landing-page`

**Response:**

```json
{
  "success": true,
  "templates": [
    {
      "id": 1,
      "name": "Basic Landing Page",
      "category": "landing-page",
      "description": "A simple landing page template",
      "html_content": "<!DOCTYPE html><html>...</html>",
      "thumbnail_url": "https://example.com/thumbnails/landing-page.png",
      "is_active": true,
      "created_by": 1,
      "created_at": "2025-09-06T10:00:00.000000Z",
      "updated_at": "2025-09-06T10:00:00.000000Z"
    }
  ]
}
```

### Get Template

Retrieves a specific template.

**Endpoint:** `GET /templates/{id}`

**Response:**

```json
{
  "success": true,
  "template": {
    "id": 1,
    "name": "Basic Landing Page",
    "category": "landing-page",
    "description": "A simple landing page template",
    "html_content": "<!DOCTYPE html><html>...</html>",
    "thumbnail_url": "https://example.com/thumbnails/landing-page.png",
    "is_active": true,
    "created_by": 1,
    "created_at": "2025-09-06T10:00:00.000000Z",
    "updated_at": "2025-09-06T10:00:00.000000Z"
  }
}
```

### Create Template

Creates a new template.

**Endpoint:** `POST /templates`

**Request Body:**

```json
{
  "name": "Product Page",
  "category": "product",
  "description": "A template for product pages",
  "html_content": "<!DOCTYPE html><html>...</html>",
  "thumbnail_url": "https://example.com/thumbnails/product-page.png",
  "is_active": true
}
```

**Response:**

```json
{
  "success": true,
  "template": {
    "id": 2,
    "name": "Product Page",
    "category": "product",
    "description": "A template for product pages",
    "html_content": "<!DOCTYPE html><html>...</html>",
    "thumbnail_url": "https://example.com/thumbnails/product-page.png",
    "is_active": true,
    "created_by": 1,
    "created_at": "2025-09-06T14:00:00.000000Z",
    "updated_at": "2025-09-06T14:00:00.000000Z"
  }
}
```

### Update Template

Updates an existing template.

**Endpoint:** `PUT /templates/{id}`

**Request Body:**

```json
{
  "name": "Updated Product Page",
  "description": "An updated template for product pages",
  "is_active": true
}
```

**Response:**

```json
{
  "success": true,
  "template": {
    "id": 2,
    "name": "Updated Product Page",
    "category": "product",
    "description": "An updated template for product pages",
    "html_content": "<!DOCTYPE html><html>...</html>",
    "thumbnail_url": "https://example.com/thumbnails/product-page.png",
    "is_active": true,
    "created_by": 1,
    "created_at": "2025-09-06T14:00:00.000000Z",
    "updated_at": "2025-09-06T14:30:00.000000Z"
  }
}
```

### Delete Template

Deletes a template.

**Endpoint:** `DELETE /templates/{id}`

**Response:**

```json
{
  "success": true,
  "message": "Template deleted successfully."
}
```

## Component Endpoints

### Get Components

Retrieves components for a site.

**Endpoint:** `GET /components?site_id=1&type=header`

**Response:**

```json
{
  "success": true,
  "components": [
    {
      "id": 1,
      "name": "Main Header",
      "type": "header",
      "description": "Main header component with navigation",
      "html_content": "<header>...</header>",
      "css_content": "header { ... }",
      "js_content": "document.addEventListener('DOMContentLoaded', function() { ... });",
      "thumbnail_url": "https://example.com/thumbnails/header.png",
      "is_global": false,
      "site_id": 1,
      "created_by": 1,
      "created_at": "2025-09-06T15:00:00.000000Z",
      "updated_at": "2025-09-06T15:00:00.000000Z"
    }
  ]
}
```

### Get Component

Retrieves a specific component.

**Endpoint:** `GET /components/{id}`

**Response:**

```json
{
  "success": true,
  "component": {
    "id": 1,
    "name": "Main Header",
    "type": "header",
    "description": "Main header component with navigation",
    "html_content": "<header>...</header>",
    "css_content": "header { ... }",
    "js_content": "document.addEventListener('DOMContentLoaded', function() { ... });",
    "thumbnail_url": "https://example.com/thumbnails/header.png",
    "is_global": false,
    "site_id": 1,
    "created_by": 1,
    "created_at": "2025-09-06T15:00:00.000000Z",
    "updated_at": "2025-09-06T15:00:00.000000Z"
  },
  "full_html": "<style>header { ... }</style><header>...</header><script>document.addEventListener('DOMContentLoaded', function() { ... });</script>"
}
```

### Create Component

Creates a new component.

**Endpoint:** `POST /components`

**Request Body:**

```json
{
  "name": "Contact Form",
  "type": "form",
  "description": "Contact form component",
  "html_content": "<form>...</form>",
  "css_content": "form { ... }",
  "js_content": "document.querySelector('form').addEventListener('submit', function(e) { ... });",
  "thumbnail_url": "https://example.com/thumbnails/contact-form.png",
  "is_global": false,
  "site_id": 1
}
```

**Response:**

```json
{
  "success": true,
  "component": {
    "id": 2,
    "name": "Contact Form",
    "type": "form",
    "description": "Contact form component",
    "html_content": "<form>...</form>",
    "css_content": "form { ... }",
    "js_content": "document.querySelector('form').addEventListener('submit', function(e) { ... });",
    "thumbnail_url": "https://example.com/thumbnails/contact-form.png",
    "is_global": false,
    "site_id": 1,
    "created_by": 1,
    "created_at": "2025-09-06T16:00:00.000000Z",
    "updated_at": "2025-09-06T16:00:00.000000Z"
  },
  "full_html": "<style>form { ... }</style><form>...</form><script>document.querySelector('form').addEventListener('submit', function(e) { ... });</script>"
}
```

### Update Component

Updates an existing component.

**Endpoint:** `PUT /components/{id}`

**Request Body:**

```json
{
  "name": "Updated Contact Form",
  "description": "Updated contact form component",
  "html_content": "<form>...</form>"
}
```

**Response:**

```json
{
  "success": true,
  "component": {
    "id": 2,
    "name": "Updated Contact Form",
    "type": "form",
    "description": "Updated contact form component",
    "html_content": "<form>...</form>",
    "css_content": "form { ... }",
    "js_content": "document.querySelector('form').addEventListener('submit', function(e) { ... });",
    "thumbnail_url": "https://example.com/thumbnails/contact-form.png",
    "is_global": false,
    "site_id": 1,
    "created_by": 1,
    "created_at": "2025-09-06T16:00:00.000000Z",
    "updated_at": "2025-09-06T16:30:00.000000Z"
  },
  "full_html": "<style>form { ... }</style><form>...</form><script>document.querySelector('form').addEventListener('submit', function(e) { ... });</script>"
}
```

### Delete Component

Deletes a component.

**Endpoint:** `DELETE /components/{id}`

**Response:**

```json
{
  "success": true,
  "message": "Component deleted successfully."
}
```
