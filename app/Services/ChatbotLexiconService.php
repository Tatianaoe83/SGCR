<?php

namespace App\Services;

use App\Models\ChatbotLexicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Léxico de Bob: mapas fijos + términos aprendidos de chatbot_analytics.
 * HybridChatbotService consulta aquí saludos, coloquialismos, sinónimos, roles y stopwords.
 */
class ChatbotLexiconService
{
    public const CACHE_KEY = 'bob_lexicon_active_v2';
    public const CACHE_TTL = 600;

    /** Destinos canónicos permitidos para auto-mapear frases/sinónimos. */
    public const CANONICAL_TARGETS = [
        'objetivo', 'alcance', 'responsable', 'riesgos', 'definiciones',
        'listado', 'empleados', 'procedimientos', 'directorio', 'correo',
        'consulta', 'explica', 'áreas', 'unidades de negocio', 'puestos relacionados',
        'tecnologia informacion', 'calidad', 'evidencias', 'actividades', 'registros', 'controles',
    ];

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function phraseMap(): array
    {
        $learned = [];
        foreach ($this->activeByCategory(ChatbotLexicon::CAT_COLLOQUIAL) as $row) {
            $from = mb_strtolower(trim((string) $row->term));
            $to = trim((string) $row->mapped_to);
            if ($from === '' || $to === '' || !$this->isSafeColloquialInjection($from, $to)) {
                continue;
            }
            $learned[$from] = $to;
        }

        return $this->mergeMaps($this->defaultPhraseMap(), $learned);
    }

    public function wordMap(): array
    {
        $learned = [];
        foreach ([ChatbotLexicon::CAT_SYNONYM, ChatbotLexicon::CAT_TECHNICAL] as $cat) {
            foreach ($this->activeByCategory($cat) as $row) {
                $from = $this->fold((string) $row->term);
                $to = trim((string) $row->mapped_to);
                if ($from === '' || $to === '') {
                    continue;
                }
                // Nunca pluralizar tipos de documento: "el procedimiento X" debe
                // seguir siendo singular o Bob lo trata como listado de área.
                if ($this->isUnsafeDocumentTypePluralization($from, $to)) {
                    continue;
                }
                $learned[$from] = $to;
            }
        }

        return $this->mergeMaps($this->defaultWordMap(), $learned);
    }

    /**
     * Evita sinónimos aprendidos tipo procedimiento→procedimientos (rompen "explícame el procedimiento …").
     */
    public function isUnsafeDocumentTypePluralization(string $from, string $to): bool
    {
        $fromFold = $this->fold($from);
        $toFold = $this->fold($to);
        $pairs = [
            'procedimiento' => 'procedimientos',
            'documento' => 'documentos',
            'proceso' => 'procesos',
            'politica' => 'politicas',
            'lineamiento' => 'lineamientos',
            'instructivo' => 'instructivos',
            'formato' => 'formatos',
        ];

        return isset($pairs[$fromFold]) && $pairs[$fromFold] === $toFold;
    }

    public function greetingPhrases(): array
    {
        $words = $this->defaultGreetingWords();
        foreach ($this->activeByCategory(ChatbotLexicon::CAT_GREETING) as $row) {
            $term = mb_strtolower(trim((string) $row->term));
            if ($term !== '') {
                $words[] = $term;
            }
        }

        return array_values(array_unique(array_filter($words)));
    }

    public function greetingAlternation(): string
    {
        $words = $this->defaultGreetingWords();
        foreach ($this->activeByCategory(ChatbotLexicon::CAT_GREETING) as $row) {
            $term = trim((string) $row->term);
            if ($term !== '') {
                $words[] = $term;
            }
        }
        $words = array_values(array_unique(array_filter($words)));
        usort($words, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return implode('|', array_map(fn ($w) => preg_quote($w, '/'), $words));
    }

    public function courtesyAlternation(): string
    {
        $words = $this->defaultCourtesyWords();
        $meta = $this->activeByCategory(ChatbotLexicon::CAT_GREETING)
            ->pluck('meta')
            ->filter()
            ->all();
        foreach ($meta as $m) {
            if (!empty($m['courtesy'])) {
                $words[] = (string) $m['courtesy'];
            }
        }
        $words = array_values(array_unique(array_filter($words)));

        return implode('|', array_map(fn ($w) => preg_quote($w, '/'), $words));
    }

    public function matrixRolePattern(): string
    {
        $terms = $this->defaultMatrixRoleTerms();
        foreach ($this->activeByCategory(ChatbotLexicon::CAT_MATRIX_ROLE) as $row) {
            $term = $this->fold((string) $row->term);
            if ($term !== '') {
                $terms[] = $term;
            }
        }
        $terms = array_values(array_unique(array_filter($terms)));

        return '/\b(' . implode('|', array_map(fn ($t) => preg_quote($t, '/'), $terms)) . ')\b/u';
    }

    /**
     * @return array<string, array{0: string, 1: string}> raiz => [regex, etiqueta]
     */
    public function genericRoles(): array
    {
        $roles = $this->defaultGenericRoles();
        foreach ($this->activeByCategory(ChatbotLexicon::CAT_GENERIC_ROLE) as $row) {
            $raiz = $this->fold((string) ($row->mapped_to ?: $row->term));
            if ($raiz === '') {
                continue;
            }
            $pattern = $row->meta['pattern'] ?? ('/\b' . preg_quote($raiz, '/') . '\w*\b/u');
            $label = $row->label ?: ('los ' . $raiz . 'es');
            $roles[$raiz] = [$pattern, $label];
        }

        return $roles;
    }

    public function emailStopwords(): array
    {
        $stop = $this->defaultEmailStopwords();
        foreach ($this->activeByCategory(ChatbotLexicon::CAT_EMAIL_STOPWORD) as $row) {
            $term = $this->fold((string) $row->term);
            if ($term !== '') {
                $stop[] = $term;
            }
        }

        return array_values(array_unique($stop));
    }

    public function searchStopwords(): array
    {
        $stop = $this->defaultSearchStopwords();
        foreach ($this->activeByCategory(ChatbotLexicon::CAT_SEARCH_STOPWORD) as $row) {
            $term = $this->fold((string) $row->term);
            if ($term !== '' && mb_strlen($term) >= 2) {
                $stop[] = $term;
            }
        }

        return array_values(array_unique($stop));
    }

    /** Palabras extra para el regex de atributos de seguimiento (objetivo, alcance…). */
    public function followupAspectTerms(): array
    {
        $terms = [];
        foreach ($this->activeByCategory(ChatbotLexicon::CAT_FOLLOWUP_ASPECT) as $row) {
            $term = $this->fold((string) $row->term);
            if ($term !== '') {
                $terms[] = $term;
            }
        }

        return array_values(array_unique($terms));
    }

    /** aspect canónico => términos extra (detectQueryAspect). */
    public function extraAspectPairs(): array
    {
        $pairs = [];
        $allowed = ['riesgos', 'evidencias', 'objetivo', 'alcance', 'responsable', 'definiciones', 'actividades', 'registros', 'controles'];
        foreach ($this->activeByCategory(ChatbotLexicon::CAT_FOLLOWUP_ASPECT) as $row) {
            $aspect = $this->fold((string) ($row->mapped_to ?: ''));
            $term = $this->fold((string) $row->term);
            if ($term === '' || !in_array($aspect, $allowed, true)) {
                continue;
            }
            $pairs[$aspect][] = $term;
        }

        return $pairs;
    }

    public function followupVerbs(): array
    {
        $terms = $this->defaultFollowupVerbs();
        foreach ($this->activeByCategory(ChatbotLexicon::CAT_FOLLOWUP_VERB) as $row) {
            $term = $this->fold((string) $row->term);
            if ($term !== '') {
                $terms[] = $term;
            }
        }

        return array_values(array_unique($terms));
    }

    /** @return array<string, list<string>> categoria chitchat => frases */
    public function chitChatExtras(): array
    {
        $out = [];
        foreach ($this->activeByCategory(ChatbotLexicon::CAT_CHITCHAT) as $row) {
            $cat = (string) ($row->mapped_to ?: 'cortesia');
            $term = mb_strtolower(trim((string) $row->term));
            if ($term === '' || mb_strlen($term) > 48) {
                continue;
            }
            $out[$cat][] = $term;
        }

        return $out;
    }

    public function directoryPreambles(): array
    {
        $out = [];
        foreach ($this->activeByCategory(ChatbotLexicon::CAT_DIRECTORY_PREAMBLE) as $row) {
            $term = mb_strtolower(trim((string) $row->term));
            if ($term !== '' && mb_strlen($term) >= 6) {
                $out[] = $term;
            }
        }

        return array_values(array_unique($out));
    }

    public function noResultHints(): array
    {
        $out = [];
        foreach ($this->activeByCategory(ChatbotLexicon::CAT_NO_RESULT_HINT) as $row) {
            $term = trim((string) ($row->mapped_to ?: $row->term));
            if ($term !== '' && mb_strlen($term) >= 4 && mb_strlen($term) <= 40) {
                $out[] = $term;
            }
        }

        return array_slice(array_values(array_unique($out)), 0, 6);
    }

    public function defaultFollowupVerbs(): array
    {
        return [
            'explica', 'explicame', 'expliqueme', 'detalle', 'detalla',
            'profundiza', 'continua', 'sigue', 'mas', 'completo', 'a fondo',
            'como se hace', 'que hago',
        ];
    }

    public function defaultSearchStopwords(): array
    {
        return [
            'el', 'la', 'los', 'las', 'un', 'una', 'de', 'del', 'que', 'y', 'en', 'por', 'para', 'con', 'se', 'su', 'sus',
            'es', 'son', 'como', 'donde', 'cual', 'cuales', 'dime', 'sobre', 'dame', 'necesito',
            'a', 'no', 'te', 'lo', 'le', 'da', 'quien', 'quienes', 'cuando', 'cuanto', 'cuantos', 'cuanta', 'cuantas',
            'este', 'esta', 'estos', 'estas', 'ese', 'esa', 'esos', 'esas', 'hay', 'tiene', 'tienes', 'tengo',
            'muestra', 'busca', 'encuentra', 'quiero', 'puedes', 'puede',
            'archivo', 'archivos', 'documento', 'documentos', 'pdf', 'descargar', 'descarga', 'abrir',
            'link', 'enlace', 'ver', 'informacion', 'información', 'acerca',
        ];
    }

    public function knownTermsFlat(): array
    {
        $known = [];
        foreach (array_keys($this->defaultPhraseMap()) as $k) {
            $known[] = $this->fold($k);
        }
        foreach (array_keys($this->defaultWordMap()) as $k) {
            $known[] = $this->fold($k);
        }
        foreach ($this->defaultGreetingWords() as $k) {
            $known[] = $this->fold($k);
        }
        foreach ($this->defaultEmailStopwords() as $k) {
            $known[] = $this->fold($k);
        }
        foreach (array_keys($this->defaultGenericRoles()) as $k) {
            $known[] = $this->fold($k);
        }
        foreach ($this->defaultMatrixRoleTerms() as $k) {
            $known[] = $this->fold($k);
        }
        foreach ($this->defaultSearchStopwords() as $k) {
            $known[] = $this->fold($k);
        }
        foreach ($this->defaultFollowupVerbs() as $k) {
            $known[] = $this->fold($k);
        }

        try {
            if (Schema::hasTable('chatbot_lexicon')) {
                foreach (ChatbotLexicon::query()->pluck('term') as $term) {
                    $known[] = $this->fold((string) $term);
                }
            }
        } catch (\Throwable $e) {
            // tabla aún no migrada
        }

        return array_values(array_unique(array_filter($known)));
    }

    public function isSafeColloquialInjection(string $from, string $to): bool
    {
        $fromFold = $this->fold($from);
        $toFold = $this->fold($to);
        $words = preg_split('/\s+/u', $fromFold) ?: [];
        if (count($words) < 3 && mb_strlen($fromFold) < 12) {
            return false;
        }
        if ($toFold !== '' && str_contains($fromFold, $toFold)) {
            return true;
        }
        foreach (array_keys($this->defaultPhraseMap()) as $known) {
            similar_text($fromFold, $this->fold($known), $pct);
            if ($pct >= 88) {
                return true;
            }
        }

        return false;
    }

    public function fold(string $text): string
    {
        $text = mb_strtolower(trim($text));

        return strtr($text, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
    }

    private function activeByCategory(string $category)
    {
        $all = $this->activeRows();

        return $all->where('category', $category)->values();
    }

    private function activeRows()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            try {
                if (!Schema::hasTable('chatbot_lexicon')) {
                    return collect();
                }

                return ChatbotLexicon::query()->active()->get();
            } catch (\Throwable $e) {
                return collect();
            }
        });
    }

    private function mergeMaps(array $base, array $learned): array
    {
        foreach ($learned as $from => $to) {
            if (!isset($base[$from])) {
                $base[$from] = $to;
            }
        }
        uksort($base, fn ($a, $b) => mb_strlen((string) $b) <=> mb_strlen((string) $a));

        return $base;
    }

    public function defaultPhraseMap(): array
    {
        return [
            'pa que sirve' => 'objetivo',
            'para que sirve' => 'objetivo',
            'de que va' => 'objetivo',
            'de qué va' => 'objetivo',
            'a que va' => 'objetivo',
            'hasta donde aplica' => 'alcance',
            'hasta dónde aplica' => 'alcance',
            'donde aplica' => 'alcance',
            'quién lleva' => 'responsable',
            'quien lleva' => 'responsable',
            'quien es el encargado' => 'responsable',
            'quién es el encargado' => 'responsable',
            'quien esta a cargo' => 'responsable',
            'quién está a cargo' => 'responsable',
            'que unidades' => 'unidades de negocio',
            'qué unidades' => 'unidades de negocio',
            'a que unidades aplica' => 'unidades de negocio',
            'a qué unidades aplica' => 'unidades de negocio',
            'que areas' => 'áreas',
            'qué áreas' => 'áreas',
            'que puestos' => 'puestos relacionados',
            'qué puestos' => 'puestos relacionados',
            'quienes son los empleados' => 'empleados',
            'quiénes son los empleados' => 'empleados',
            'elemento padre' => 'elemento padre',
            'documentos relacionados' => 'elementos relacionados',
            'que puede salir mal' => 'riesgos',
            'qué puede salir mal' => 'riesgos',
            'dame el listado' => 'listado',
            'area de calidad' => 'calidad',
            'área de calidad' => 'calidad',
            'de ti' => 'tecnologia informacion',
            'de t.i.' => 'tecnologia informacion',
            'de t.i' => 'tecnologia informacion',
        ];
    }

    public function defaultWordMap(): array
    {
        return [
            'alcanze' => 'alcance',
            'objetibo' => 'objetivo',
            'objetvo' => 'objetivo',
            'responsavle' => 'responsable',
            'responsables' => 'responsables',
            'definis' => 'definiciones',
            'definicion' => 'definiciones',
            'definición' => 'definiciones',
            'riegos' => 'riesgos',
            'riesgo' => 'riesgos',
            'encargado' => 'responsable',
            'encargada' => 'responsable',
            'checa' => 'consulta',
            'chequea' => 'consulta',
            'mira' => 'consulta',
            'dime' => 'explica',
            'solitud' => 'solicitud',
            'campameto' => 'campamento',
            'cordinador' => 'coordinador',
            'cordinadora' => 'coordinadora',
            'gerent' => 'gerente',
            'presupesto' => 'presupuesto',
            'enumera' => 'lista',
            'enumerar' => 'lista',
            'listame' => 'lista',
            'enlista' => 'lista',
        ];
    }

    public function defaultGreetingWords(): array
    {
        return [
            'hola', 'holi', 'holis', 'buenos dias', 'buenos días', 'buenas tardes', 'buenas noches',
            'buenas', 'hi', 'hello', 'start', 'inicio', 'hey', 'qué tal', 'que tal', 'qué onda', 'que onda',
        ];
    }

    public function defaultCourtesyWords(): array
    {
        return [
            'guapo', 'guapa', 'amigo', 'amiga', 'bonito', 'bonita', 'lindo', 'linda',
            'hermoso', 'hermosa', 'crack', 'bb', 'bebe', 'querido', 'querida',
        ];
    }

    public function defaultMatrixRoleTerms(): array
    {
        return [
            'responsable', 'responsables', 'encargado', 'encargada', 'encargados', 'encargadas',
            'participa', 'participan', 'participantes', 'relacionado', 'relacionada',
            'relacionados', 'relacionadas', 'involucrado', 'involucrada', 'involucrados',
            'involucradas', 'matriz', 'responsabilidad', 'responsabilidades',
        ];
    }

    public function defaultGenericRoles(): array
    {
        return [
            'director' => ['/\bdirector(?:es|as|a)?\b/u', 'los directores'],
            'subdirector' => ['/\bsubdirector(?:es|as|a)?\b/u', 'los subdirectores'],
            'gerente' => ['/\bgerent(?:es|e|a)\b/u', 'los gerentes'],
            'coordinador' => ['/\bcoordinador(?:es|as|a)?\b/u', 'los coordinadores'],
            'jefe' => ['/\bjefes?\b|\bjefas?\b|\bjefaturas?\b/u', 'las jefaturas'],
            'analista' => ['/\banalistas?\b/u', 'los analistas'],
            'residente' => ['/\bresidentes?\b/u', 'los residentes'],
            'auxiliar' => ['/\bauxiliar(?:es)?\b/u', 'los auxiliares'],
        ];
    }

    public function defaultEmailStopwords(): array
    {
        return [
            'usuario', 'usuarios', 'empleado', 'empleados', 'empleada', 'empleadas', 'persona', 'personas',
            'senor', 'senora', 'trabajador', 'trabajadores', 'colaborador', 'colaboradores', 'registrado',
            'registrada', 'registrados', 'sistema', 'favor', 'para', 'por', 'con', 'que', 'tiene', 'tienen',
            'tienes', 'tengo', 'cuenta', 'posee', 'hay', 'existe', 'sabes', 'saber', 'conoces', 'area',
            'areas', 'unidad', 'unidades', 'puesto', 'puestos', 'del', 'las', 'los', 'una', 'uno', 'sus',
            'todos', 'todas', 'cual', 'cuales', 'dame', 'dime', 'quiero', 'necesito', 'algun', 'alguna',
            'directorio', 'contacto', 'contactar', 'escribir', 'lista', 'listado', 'mismo', 'misma',
            'duda', 'quien', 'quienes', 'ocupa', 'ocupan', 'llama', 'llaman', 'proser',
            'ninguno', 'ninguna', 'ningunos', 'ningunas', 'general', 'alguien',
            'llamado', 'llamada', 'llamaba', 'llamaban', 'nombres', 'nombre',
            'pero', 'solo', 'solamente', 'tambien', 'ademas',
            'ella', 'ellas', 'ellos', 'ese', 'esa', 'esos', 'esas', 'eso', 'esto', 'esta', 'estos', 'estas',
            'usted', 'ustedes', 'das', 'doy', 'dan', 'dar', 'puedes', 'puedo', 'podrias', 'podria',
            'pasame', 'pasarme', 'comparte', 'compartir', 'compartelo', 'compartemelo', 'muestrame',
            'muestra', 'indicame', 'indica', 'oye', 'porfa', 'porfavor', 'gracias', 'este',
            'ocupo', 'conocer', 'busco', 'busca', 'exacto', 'exactamente', 'encarga', 'obligaciones',
            'analista', 'auxiliar', 'coordinador', 'coordinadora', 'gerente', 'director', 'directora',
            'jefe', 'jefa', 'residente', 'programador', 'programacion', 'administrativo', 'administracion',
            'contador', 'nominas', 'nomina',
        ];
    }
}
