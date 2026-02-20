<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentChunk extends Model
{
    protected $fillable = [
        'word_document_id',
        'section_title',
        'chunk_type',
        'content',
        'char_count',
    ];

    // 🔗 Relación con el documento padre
    public function wordDocument()
    {
        return $this->belongsTo(WordDocument::class, 'word_document_id');
    }
}
