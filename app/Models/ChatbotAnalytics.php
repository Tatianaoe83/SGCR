<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotAnalytics extends Model
{
    protected $fillable = [
        'user_id', 'query', 'normalized_query', 'response_method',
        'response', 'response_time_ms', 'matched_keywords',
        'similarity_score', 'session_id'
    ];

    protected $casts = [
        'matched_keywords' => 'array'
    ];

    public function feedback()
    {
        return $this->hasOne(ChatbotFeedback::class, 'analytics_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
