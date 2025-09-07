# Models

The Bedrock HTML Editor package includes several models to manage HTML modifications, templates, components, and AI prompt history.

## Base Model

### BedrockHtmlEditorModel

This is an abstract base model that all other models in the package extend. It automatically handles table prefixing with `bhe_` to avoid table name collisions with the main application and other packages.

```php
namespace Prasso\BedrockHtmlEditor\Models;

use Illuminate\Database\Eloquent\Model;

abstract class BedrockHtmlEditorModel extends Model
{
    /**
     * Create a new BedrockHtmlEditorModel instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // If the table name doesn't already have the prefix, add it
        if (!str_starts_with($this->getTable(), 'bhe_')) {
            $this->setTable('bhe_' . $this->getTable());
        }
    }
}
```

## HTML Modification Model

### HtmlModification

This model represents an HTML modification made by a user. It tracks the original HTML, the modified HTML, the prompt used to generate the modification, and metadata about the modification.

#### Table Structure

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | Foreign key to users table |
| site_id | bigint | Foreign key to sites table |
| page_id | bigint | Foreign key to site_pages table |
| title | string | Title of the modification |
| prompt | text | The prompt used to generate the modification |
| original_html | text | The original HTML content |
| modified_html | text | The modified HTML content |
| storage_path | string | Path to the HTML file in storage |
| session_id | string | Session ID for the Bedrock agent |
| metadata | json | Additional metadata about the modification |
| is_published | boolean | Whether the modification has been published |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |

#### Relationships

- `user()`: Belongs to a User
- `site()`: Belongs to a Site
- `page()`: Belongs to a SitePage

#### Scopes

- `scopePublished()`: Only include published modifications
- `scopeForSite($siteId)`: Only include modifications for a specific site
- `scopeForPage($pageId)`: Only include modifications for a specific page

#### Accessors

- `getStorageUrlAttribute()`: Get the storage URL for the HTML file

## HTML Template Model

### HtmlTemplate

This model represents an HTML template that can be used as a starting point for creating new HTML pages.

#### Table Structure

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Name of the template |
| category | string | Category of the template |
| description | text | Description of the template |
| html_content | text | The HTML content of the template |
| thumbnail_url | text | URL to the template thumbnail |
| is_active | boolean | Whether the template is active |
| created_by | bigint | Foreign key to users table |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |

#### Relationships

- `creator()`: Belongs to a User

#### Scopes

- `scopeActive()`: Only include active templates
- `scopeInCategory($category)`: Only include templates in a specific category

#### Accessors

- `getThumbnailUrlAttribute($value)`: Get the thumbnail URL for the template

## HTML Component Model

### HtmlComponent

This model represents an HTML component that can be reused across different HTML pages.

#### Table Structure

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Name of the component |
| type | string | Type of the component |
| description | text | Description of the component |
| html_content | text | The HTML content of the component |
| css_content | text | The CSS content of the component |
| js_content | text | The JavaScript content of the component |
| thumbnail_url | text | URL to the component thumbnail |
| is_global | boolean | Whether the component is global |
| site_id | bigint | Foreign key to sites table |
| created_by | bigint | Foreign key to users table |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |

#### Relationships

- `creator()`: Belongs to a User
- `site()`: Belongs to a Site

#### Scopes

- `scopeGlobal()`: Only include global components
- `scopeForSite($siteId)`: Only include components for a specific site
- `scopeOfType($type)`: Only include components of a specific type

#### Accessors

- `getThumbnailUrlAttribute($value)`: Get the thumbnail URL for the component
- `getFullHtmlAttribute()`: Get the full HTML content including CSS and JS

## AI Prompt History Model

### AiPromptHistory

This model tracks the history of AI prompts and responses for debugging and improving the HTML generation process.

#### Table Structure

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | Foreign key to users table |
| modification_id | bigint | Foreign key to bhe_html_modifications table |
| prompt | text | The prompt sent to the AI |
| response | text | The response from the AI |
| session_id | string | Session ID for the Bedrock agent |
| metadata | json | Additional metadata about the prompt |
| success | boolean | Whether the prompt was successful |
| error_message | string | Error message if the prompt failed |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |

#### Relationships

- `user()`: Belongs to a User
- `modification()`: Belongs to an HtmlModification

#### Scopes

- `scopeSuccessful()`: Only include successful prompts
- `scopeFailed()`: Only include failed prompts
- `scopeForSession($sessionId)`: Only include prompts for a specific session

#### Accessors

- `getTruncatedPromptAttribute($length = 100)`: Get the truncated prompt for display
- `getTruncatedResponseAttribute($length = 100)`: Get the truncated response for display
