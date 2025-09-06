<?php

namespace Prasso\BedrockHtmlEditor\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Site;
use App\Models\SitePages;

class HtmlModification extends BedrockHtmlEditorModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bhe_html_modifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'site_id',
        'page_id',
        'title',
        'prompt',
        'original_html',
        'modified_html',
        'storage_path',
        'session_id',
        'metadata',
        'is_published',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'is_published' => 'boolean',
    ];

    /**
     * Get the user that created the modification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the site that the modification belongs to.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the page that the modification belongs to.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(SitePages::class, 'page_id');
    }

    /**
     * Scope a query to only include published modifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope a query to only include modifications for a specific site.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $siteId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForSite($query, $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    /**
     * Scope a query to only include modifications for a specific page.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $pageId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPage($query, $pageId)
    {
        return $query->where('page_id', $pageId);
    }

    /**
     * Get the storage URL for the HTML file.
     *
     * @return string|null
     */
    public function getStorageUrlAttribute()
    {
        if (!$this->storage_path) {
            return null;
        }
        
        $disk = config('bedrock-html-editor.storage.disk');
        return asset('storage/' . $this->storage_path);
    }
}
