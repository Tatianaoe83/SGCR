<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmartIndex extends Model
{
    protected $table = 'smart_indexes';
    protected $fillable = [
        'original_query', 'normalized_query', 'keywords', 'entities',
        'response', 'usage_count', 'confidence_score', 'similar_queries',
        'auto_generated', 'verified', 'last_used_at'
    ];

    protected $casts = [
        'keywords' => 'array',
        'entities' => 'array',
        'similar_queries' => 'array',
        'auto_generated' => 'boolean',
        'verified' => 'boolean',
        'last_used_at' => 'datetime'
    ];

    public function incrementUsage()
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    public function updateConfidence($userFeedback)
    {
        $currentScore = $this->confidence_score;
        $newScore = $userFeedback ? 
            min(1.0, $currentScore + 0.1) : 
            max(0.0, $currentScore - 0.1);
        
        $this->update(['confidence_score' => $newScore]);
    }

    // Scope para consultas de alta confianza
    public function scopeHighConfidence($query)
    {
        return $query->where('confidence_score', '>=', 0.7)
                    ->where('verified', true);
    }
}
