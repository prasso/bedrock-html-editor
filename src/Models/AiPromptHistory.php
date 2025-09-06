<?php

namespace Prasso\BedrockHtmlEditor\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class AiPromptHistory extends BedrockHtmlEditorModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bhe_ai_prompt_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'modification_id',
        'prompt',
        'response',
        'session_id',
        'metadata',
        'success',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'success' => 'boolean',
    ];

    /**
     * Get the user that created the prompt.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the HTML modification associated with this prompt.
     */
    public function modification(): BelongsTo
    {
        return $this->belongsTo(HtmlModification::class, 'modification_id');
    }

    /**
     * Scope a query to only include successful prompts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope a query to only include failed prompts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope a query to only include prompts for a specific session.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $sessionId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Get the truncated prompt for display.
     *
     * @param  int  $length
     * @return string
     */
    public function getTruncatedPromptAttribute($length = 100)
    {
        return strlen($this->prompt) > $length
            ? substr($this->prompt, 0, $length) . '...'
            : $this->prompt;
    }

    /**
     * Get the truncated response for display.
     *
     * @param  int  $length
     * @return string|null
     */
    public function getTruncatedResponseAttribute($length = 100)
    {
        if (!$this->response) {
            return null;
        }

        return strlen($this->response) > $length
            ? substr($this->response, 0, $length) . '...'
            : $this->response;
    }
}
