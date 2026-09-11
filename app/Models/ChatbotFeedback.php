<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotFeedback extends Model
{
    protected $fillable = [
        'analytics_id', 'helpful', 'score', 'session_id', 'user_id', 'comment', 'improvement_suggestion'
    ];

    protected $casts = [
        'helpful' => 'boolean',
        'score' => 'integer',
    ];

    public function analytics()
    {
        return $this->belongsTo(ChatbotAnalytics::class, 'analytics_id');
    }
}
