<?php

namespace App\Services;

use App\Models\DocumentChunk;
use App\Models\Elemento;
use App\Models\PuestoTrabajo;
use App\Models\WordDocument;
use Illuminate\Support\Facades\Log;

/**
 * Estructura real de los procedimientos del SGC (Word).
 * Bob y el chunker leen las mismas secciones: no inventar títulos que el PDF no usa.
 */
class SgcProcedureStructureService
{
    public const SECTION_TYPES = [
        'objetivo' => 'objective',
        'alcance' => 'alcance',
        'definiciones' => 'definitions',
        'normas' => 'norms',
        'normas generales' => 'norms',
        'desarrollo' => 'development',
        'evidencias' => 'evidences',
        'riesgos' => 'risks',
        'riesgos y descripcion' => 'risks',
        'responsable del elemento' => 'responsibles',
        'responsable de elemento' => 'responsibles',
        'responsable del procedimiento' => 'responsibles',
        'responsable de procedimiento' => 'responsibles',
        'documentos de referencia' => 'references',
        'documentos de referencia y anexos' => 'references',
    ];

    public function foldAccents(string $text): string
    {
        $text = mb_strtolower(trim($text));

        return strtr($text, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        ]);
    }

    /**
     * Detecta el título canónico de una sección SGC en el inicio de un bloque.
     */
    public function detectCanonicalSection(string $text): ?array
    {
        $head = mb_substr(trim($text), 0, 420);
        $head = preg_replace('/\s+/u', ' ', $head) ?? $head;
        $folded = $this->foldAccents($head);

        $needles = array_keys(self::SECTION_TYPES);
        usort($needles, static fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($needles as $needle) {
            if (preg_match('/(?:^|\d+\.\s*)' . preg_quote($needle, '/') . '\b/u', $folded)) {
                return [
                    'title' => mb_strtoupper($needle),
                    'type' => self::SECTION_TYPES[$needle],
                ];
            }
        }

        return null;
    }

    /**
     * @return array{heading:string,puestos:array<int,string>}
     */
    public function extractResponsableSection(?string $text): array
    {
        $empty = ['heading' => '', 'puestos' => []];
        if ($text === null || trim($text) === '') {
            return $empty;
        }

        $flat = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        $needles = [
            'RESPONSABLE DEL ELEMENTO',
            'RESPONSABLE DE ELEMENTO',
            'RESPONSABLE DEL PROCEDIMIENTO',
            'RESPONSABLE DE PROCEDIMIENTO',
        ];
        $pos = false;
        foreach ($needles as $needle) {
            $p = mb_stripos($flat, $needle);
            if ($p !== false && ($pos === false || $p < $pos)) {
                $pos = $p;
            }
        }
        if ($pos === false) {
            return $empty;
        }

        $win = mb_substr($flat, $pos, 900);
        $heading = 'RESPONSABLE DEL ELEMENTO';
        if (preg_match('/((?:\d+\.)?\s*RESPONSABLE\s+DE(?:L)?\s+(?:ELEMENTO|PROCEDIMIENTO))/iu', $win, $h)) {
            $heading = trim(preg_replace('/\s+/u', ' ', $h[1]));
        }

        $puestos = [];
        $cargo = $this->cargoPattern();

        if (preg_match(
            '/RESPONSABLE\s+DE(?:L)?\s+(?:ELEMENTO|PROCEDIMIENTO).{0,320}?\|[-:\s\|]+\|\s*\|\s*([^|]{4,80})\s*\|/iu',
            $win,
            $row
        )) {
            $cell = trim(preg_replace('/\s+/u', ' ', $row[1]) ?? '');
            $cell = $this->cleanCargoNombre($cell);
            if ($cell !== null) {
                $puestos[$this->foldAccents($cell)] = $cell;
            }
        }

        if (empty($puestos) && preg_match(
            '/RESPONSABLE\s+DE(?:L)?\s+(?:ELEMENTO|PROCEDIMIENTO)\s*:?\s*(?:\d+\.\d+\.?\s*)?'
            . '(' . $cargo . '(?:\s+(?:de|del|y|la|el|los|las|[\p{L}\.]+)){0,8})'
            . '\s*(?=(?:\d+\.\d+|P\s*A\s*R\s*T|PARTICIP|ELABOR|REVIS|AUTORIZ|$))/iu',
            $win,
            $m
        )) {
            $name = $this->cleanCargoNombre($m[1] ?? '');
            if ($name !== null) {
                $puestos[$this->foldAccents($name)] = $name;
            }
        }

        if (empty($puestos) && preg_match(
            '/(?:\d+\.\d+\.?\s*)?(' . $cargo . '(?:\s+[\p{L}\.]+){0,6})/iu',
            $win,
            $found
        )) {
            $name = $this->cleanCargoNombre($found[1] ?? '');
            if ($name !== null) {
                $puestos[$this->foldAccents($name)] = $name;
            }
        }

        return ['heading' => $heading, 'puestos' => array_values($puestos)];
    }

    public function extractResponsableNombre(?string $text): ?string
    {
        $sec = $this->extractResponsableSection($text);
        if (empty($sec['puestos'])) {
            return null;
        }

        return implode(', ', $sec['puestos']);
    }

    /**
     * Tabla de Desarrollo: Responsable | Actividad (no un título "Actividades").
     *
     * @return array<int, array{responsable:string,actividad:string}>
     */
    public function extractActividadesTable(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        $rows = $this->parseMarkdownActividadTable($text);
        if (!empty($rows)) {
            return $rows;
        }

        return $this->parseFlattenedActividadRows($text);
    }

    public function collectElementoText(Elemento $elemento): string
    {
        $parts = [];
        $wd = $elemento->wordDocument;
        if ($wd && trim((string) ($wd->contenido_texto ?? '')) !== '') {
            $parts[] = (string) $wd->contenido_texto;
        }

        $wordIds = WordDocument::query()
            ->where('elemento_id', $elemento->getKey())
            ->pluck('id')
            ->all();
        if (!empty($wordIds)) {
            $chunkText = DocumentChunk::query()
                ->whereIn('word_document_id', $wordIds)
                ->where(function ($q) {
                    $q->where('content', 'like', '%RESPONSABLE DEL%')
                        ->orWhere('content', 'like', '%RESPONSABLE DE %')
                        ->orWhere('content', 'like', '%| Responsable |%')
                        ->orWhere('chunk_type', 'development')
                        ->orWhere('chunk_type', 'responsibles');
                })
                ->orderBy('id')
                ->limit(16)
                ->pluck('content')
                ->implode("\n");
            if (trim($chunkText) !== '') {
                $parts[] = $chunkText;
            }
        }

        return implode("\n", $parts);
    }

    public function resolvePuestoIdByNombre(string $nombre): ?int
    {
        $q = $this->foldAccents($nombre);
        $q = trim($q, " \t.:;-|");
        if ($q === '' || mb_strlen($q) < 4) {
            return null;
        }

        $puestos = PuestoTrabajo::query()
            ->select('id_puesto_trabajo', 'nombre')
            ->orderByRaw('CHAR_LENGTH(nombre) DESC')
            ->get();

        $exact = $puestos->first(fn ($p) => $this->foldAccents((string) $p->nombre) === $q);
        if ($exact) {
            return (int) $exact->id_puesto_trabajo;
        }

        $contained = $puestos->filter(function ($p) use ($q) {
            $name = $this->foldAccents((string) $p->nombre);

            return mb_strlen($name) >= 8
                && (str_contains($q, $name) || str_contains($name, $q));
        });
        if ($contained->count() === 1) {
            return (int) $contained->first()->id_puesto_trabajo;
        }
        if ($contained->isNotEmpty()) {
            $best = $contained->sortByDesc(
                fn ($p) => mb_strlen($this->foldAccents((string) $p->nombre))
            )->first();

            return $best ? (int) $best->id_puesto_trabajo : null;
        }

        return null;
    }

    /**
     * Rellena puesto_responsable_id desde §9/10 del Word. No pisa un valor existente
     * salvo que $force sea true.
     *
     * @return array{updated:bool,puesto_id:?int,nombre:?string,reason:string}
     */
    public function syncElementoResponsable(Elemento $elemento, bool $force = false): array
    {
        $elemento->loadMissing(['wordDocument:id,elemento_id,contenido_texto']);
        $current = (int) ($elemento->puesto_responsable_id ?? 0);
        if ($current > 0 && !$force) {
            return [
                'updated' => false,
                'puesto_id' => $current,
                'nombre' => optional($elemento->puestoResponsable)->nombre,
                'reason' => 'ya_asignado',
            ];
        }

        $text = $this->collectElementoText($elemento);
        $sec = $this->extractResponsableSection($text);
        $nombre = $sec['puestos'][0] ?? null;
        if ($nombre === null) {
            return [
                'updated' => false,
                'puesto_id' => $current ?: null,
                'nombre' => null,
                'reason' => 'no_encontrado_en_word',
            ];
        }

        $puestoId = $this->resolvePuestoIdByNombre($nombre);
        if ($puestoId === null) {
            Log::info('[SGC] Responsable leído del Word pero no hay puesto en catálogo', [
                'elemento_id' => $elemento->getKey(),
                'folio' => $elemento->folio_elemento ?? null,
                'nombre' => $nombre,
            ]);

            return [
                'updated' => false,
                'puesto_id' => null,
                'nombre' => $nombre,
                'reason' => 'puesto_no_catalogado',
            ];
        }

        if ($current === $puestoId) {
            return [
                'updated' => false,
                'puesto_id' => $puestoId,
                'nombre' => $nombre,
                'reason' => 'igual',
            ];
        }

        $elemento->puesto_responsable_id = $puestoId;
        $elemento->save();

        return [
            'updated' => true,
            'puesto_id' => $puestoId,
            'nombre' => $nombre,
            'reason' => 'sincronizado',
        ];
    }

    private function cargoPattern(): string
    {
        return '(?:Gerente|Director(?:a)?|Jefe|Jefa|Coordinador(?:a)?|Analista|Auxiliar|'
            . 'Residente|Encargad[oa]|Superintendente)';
    }

    private function cleanCargoNombre(string $name): ?string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name, " \t.:;-|");
        $name = preg_replace(
            '/\s+(?:R\s*E\s*S\s*P\s*O\s*N\s*S\s*A\s*B\s*L\s*E|P\s*A\s*R\s*T\s*I\s*C\s*I\s*P(?:\s*A\s*N\s*T\s*E\s*S?)?|'
            . 'A\s*U\s*T\s*O\s*R\s*I\s*Z[OÓ]|R\s*E\s*V\s*I\s*S[OÓ]|E\s*L\s*A\s*B\s*O\s*R[OÓ])\b.*$/iu',
            '',
            $name
        ) ?? $name;
        $name = trim($name, " \t.:;-|");

        if (
            mb_strlen($name) < 5
            || mb_strlen($name) > 80
            || preg_match('/^(inicio de procedimiento|\d+|persona designada)$/iu', $name)
            || preg_match('/\b(responsable|periodicidad|[aá]rea|elemento)\b/iu', $name)
        ) {
            return null;
        }

        return $name;
    }

    /**
     * @return array<int, array{responsable:string,actividad:string}>
     */
    private function parseMarkdownActividadTable(string $text): array
    {
        if (!preg_match(
            '/\|\s*Responsable\s*\|\s*Actividad[^\n]*\n\|[-:\s\|]+\n((?:\|[^\n]+\n?)+)/iu',
            $text,
            $m
        )) {
            return [];
        }

        $rows = [];
        foreach (preg_split('/\R/u', trim($m[1])) ?: [] as $line) {
            $cells = array_values(array_filter(
                array_map('trim', explode('|', $line)),
                static fn ($c) => $c !== '' && !preg_match('/^[-:]+$/', $c)
            ));
            if (count($cells) < 2) {
                continue;
            }
            $responsable = trim($cells[0]);
            $actividad = trim($cells[1]);
            if (
                $responsable === ''
                || $actividad === ''
                || preg_match('/^(responsable|actividad|descripci[oó]n)$/iu', $responsable)
            ) {
                continue;
            }
            $rows[] = [
                'responsable' => $responsable,
                'actividad' => $actividad,
            ];
        }

        return $rows;
    }

    /**
     * OCR a veces aplana la tabla: "Jefe de X 1. Hacer Y".
     *
     * @return array<int, array{responsable:string,actividad:string}>
     */
    private function parseFlattenedActividadRows(string $text): array
    {
        $pos = mb_stripos($text, 'DESARROLLO');
        if ($pos === false) {
            return [];
        }

        $win = mb_substr($text, $pos, 8000);
        $cargo = $this->cargoPattern();
        if (!preg_match_all(
            '/(' . $cargo . '(?:\s+(?:de|del|y|la|el|los|las|[\p{L}\.]+)){0,8})\s+'
            . '((?:\d+\.)\s+[^|]{12,400}?)(?=\s+(?:' . $cargo . ')\s+\d+\.|\s+\d+\.\s+[A-ZÁÉÍÓÚ]{4,}|\s*$)/iu',
            preg_replace('/\s+/u', ' ', $win) ?? $win,
            $matches,
            PREG_SET_ORDER
        )) {
            return [];
        }

        $rows = [];
        foreach ($matches as $m) {
            $responsable = trim(preg_replace('/\s+/u', ' ', $m[1]) ?? '', " \t.:;-|");
            $actividad = trim(preg_replace('/\s+/u', ' ', $m[2]) ?? '');
            if (mb_strlen($responsable) < 5 || mb_strlen($actividad) < 8) {
                continue;
            }
            $rows[] = [
                'responsable' => $responsable,
                'actividad' => $actividad,
            ];
        }

        return $rows;
    }
}
