<?php

namespace Prasso\BedrockHtmlEditor\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Site;

class HtmlComponent extends BedrockHtmlEditorModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'description',
        'html_content',
        'css_content',
        'js_content',
        'thumbnail_url',
        'is_global',
        'site_id',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_global' => 'boolean',
    ];

    /**
     * Get the user that created the component.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the site that the component belongs to.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Scope a query to only include global components.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    /**
     * Scope a query to only include components for a specific site.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $siteId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForSite($query, $siteId)
    {
        return $query->where(function($query) use ($siteId) {
            $query->where('site_id', $siteId)
                  ->orWhere('is_global', true);
        });
    }

    /**
     * Scope a query to only include components of a specific type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the thumbnail URL for the component.
     *
     * @return string
     */
    public function getThumbnailUrlAttribute($value)
    {
        if (!$value) {
            return asset('vendor/bedrock-html-editor/images/default-component-thumbnail.png');
        }
        
        return $value;
    }

    /**
     * Get the full HTML content including CSS and JS.
     *
     * @return string
     */
    public function getFullHtmlAttribute()
    {
        $html = $this->html_content;
        
        if ($this->css_content) {
            $html = "<style>\n{$this->css_content}\n</style>\n{$html}";
        }
        
        if ($this->js_content) {
            $html = "{$html}\n<script>\n{$this->js_content}\n</script>";
        }
        
        return $html;
    }
}
