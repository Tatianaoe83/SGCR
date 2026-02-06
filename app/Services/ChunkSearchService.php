<?php

namespace App\Services;

use App\Models\DocumentChunk;
use Illuminate\Support\Collection;

class ChunkSearchService
{
    public function search(string $query, int $limit = 6): Collection
    {
        $queryUpper = mb_strtoupper($query);

        return DocumentChunk::query()
            ->where(function ($q) use ($queryUpper) {
                $q->whereRaw('UPPER(content) LIKE ?', ["%{$queryUpper}%"])
                  ->orWhereRaw('UPPER(section_title) LIKE ?', ["%{$queryUpper}%"]);
            })
            ->orderByRaw("
                CASE chunk_type
                    WHEN 'definitions' THEN 1
                    WHEN 'responsibles' THEN 2
                    ELSE 3
                END
            ")
            ->limit($limit)
            ->get();
    }
}
