<?php

namespace Prasso\BedrockHtmlEditor\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class HtmlTemplate extends BedrockHtmlEditorModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'category',
        'description',
        'html_content',
        'thumbnail_url',
        'is_active',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that created the template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include active templates.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include templates in a specific category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get the thumbnail URL for the template.
     *
     * @return string
     */
    public function getThumbnailUrlAttribute($value)
    {
        if (!$value) {
            return asset('vendor/bedrock-html-editor/images/default-template-thumbnail.png');
        }
        
        return $value;
    }
}
