<?php

namespace App\Services;

use App\Models\DocumentChunk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB; // Importante agregar esto

class ChunkSearchService
{

    public function search(string $query, int $limit = 4): Collection
    {
        $queryUpper = mb_strtoupper(trim($query));
        
        // Si la query es muy corta, no buscamos para evitar basura
        if (mb_strlen($queryUpper) < 3) {
            return collect([]);
        }

        return DocumentChunk::query()
            ->select('*')
            // Si la palabra está en el TÍTULO vale más (3 puntos) que en el contenido (1 punto)
            ->selectRaw("
                (CASE WHEN UPPER(section_title) LIKE ? THEN 3 ELSE 0 END + 
                 CASE WHEN UPPER(content) LIKE ? THEN 1 ELSE 0 END) as relevance_score
            ", ["%{$queryUpper}%", "%{$queryUpper}%"])
            
            ->where(function ($q) use ($queryUpper) {
                $q->whereRaw('UPPER(content) LIKE ?', ["%{$queryUpper}%"])
                  ->orWhereRaw('UPPER(section_title) LIKE ?', ["%{$queryUpper}%"]);
            })
            // Ordenamos por relevancia primero, luego por tipo
            ->orderByDesc('relevance_score')
            ->limit($limit)
            ->get();
    }
}