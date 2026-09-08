<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotLexicon extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REJECTED = 'rejected';

    public const CAT_GREETING = 'greeting';
    public const CAT_COLLOQUIAL = 'colloquial_phrase';
    public const CAT_SYNONYM = 'synonym';
    public const CAT_TECHNICAL = 'technical_term';
    public const CAT_MATRIX_ROLE = 'matrix_role';
    public const CAT_GENERIC_ROLE = 'generic_role';
    public const CAT_EMAIL_STOPWORD = 'email_stopword';
    public const CAT_SEARCH_STOPWORD = 'search_stopword';
    public const CAT_FOLLOWUP_ASPECT = 'followup_aspect';
    public const CAT_FOLLOWUP_VERB = 'followup_verb';
    public const CAT_CHITCHAT = 'chitchat';
    public const CAT_DIRECTORY_PREAMBLE = 'directory_preamble';
    public const CAT_NO_RESULT_HINT = 'no_result_hint';

    protected $table = 'chatbot_lexicon';

    protected $fillable = [
        'category',
        'term',
        'mapped_to',
        'label',
        'meta',
        'hits',
        'confidence',
        'status',
        'source',
        'last_analytics_id',
        'last_seen_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'hits' => 'integer',
        'confidence' => 'float',
        'last_seen_at' => 'datetime',
    ];

    public function lastAnalytics()
    {
        return $this->belongsTo(ChatbotAnalytics::class, 'last_analytics_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
