<?php
/**
 * Simulación Ronda 5 — muestra live (básicos + expertos).
 * Misma session_id por persona. Compara red flags vs R4.
 *
 * Uso: php tests/bob_qa/sim_ronda5_sample.php
 * Env: SIM_PERSONAS=100 (default)
 */
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\HybridChatbotService;

$personasPath = __DIR__ . '/personas_ronda5.json';
if (!is_file($personasPath)) {
    fwrite(STDERR, "Falta personas_ronda5.json. Corre: python generate_ronda5_personas.py\n");
    exit(1);
}

$data = json_decode(file_get_contents($personasPath), true);
$personas = $data['personas'] ?? [];
$svc = app(HybridChatbotService::class);

// Turnos 1-based del arco de 20
$turnosMuestra = [1, 2, 5, 6, 7, 10, 11, 16];
$maxPersonas = (int) (getenv('SIM_PERSONAS') ?: 100);

function fold(string $s): string
{
    $s = mb_strtolower($s);
    return strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
}

function classify(string $q, string $resp, string $method, ?Throwable $err): array
{
    if ($err) {
        return ['CORROMPE_SERVICIO', 'excepcion'];
    }
    $r = fold($resp);
    $qf = fold($q);

    if (preg_match('/borr[eé] el contexto|borr[eé] la conversaci[oó]n/u', $r)) {
        return ['FALLA', 'dijo_que_borro_contexto'];
    }
    if (preg_match('/\*\*coincidencias:\*\*|coincidencias:/u', $r) && mb_strlen($r) > 400) {
        return ['FALLA', 'lista_coincidencias_larga'];
    }
    if (preg_match('/o4-mini|gpt-4|api key|system prompt/u', $r)) {
        return ['FALLA', 'filtro_sistema'];
    }
    if (mb_strlen(trim($resp)) < 8) {
        return ['FALLA', 'respuesta_vacia'];
    }

    if (preg_match('/\b(hola|holi|buenas|hey)\b/u', $qf)) {
        if (preg_match('/hola|bob|ayud|procedimiento|directorio/u', $r)) {
            return ['OK', 'saludo'];
        }
    }
    if (preg_match('/\bunidades?\b/u', $qf) || preg_match('/\bareas?\b/u', $qf) || preg_match('/organizada/u', $qf)) {
        if (preg_match('/unidad|agregados|construcci|corporativo|organiza|directorio/u', $r)) {
            return ['OK', 'unidades_bd'];
        }
        if (preg_match('/coincidencias/u', $r)) {
            return ['FALLA', 'areas_a_coincidencias'];
        }
    }
    if (preg_match('/\b(mi jefe|a quien reporto|quien me puede ayudar|qui[eé]n me)\b/u', $qf)) {
        if (preg_match('/jefe|puesto|directorio|no (tengo|model)|report|unidad/u', $r)) {
            return ['OK', 'mi_jefe_honesto'];
        }
        if (preg_match('/coincidencias/u', $r)) {
            return ['FALLA', 'mi_jefe_a_coincidencias'];
        }
    }
    if (preg_match('/\b(necesito algo de|solitud de)\b/u', $qf)) {
        if (preg_match('/conversation_vague|unpublished/u', $method)
            || preg_match('/entiendo|orient|proveedor|cobro|publicado|alternativ|programar|ejecutar/u', $r)) {
            return ['OK', 'vago_aclara'];
        }
    }
    if (preg_match('/\b(volvamos|me perd|ese no es|ese no era)\b/u', $qf)) {
        if (preg_match('/topic_recovery/u', $method)
            || preg_match('/ok|suelto|retom|tema|dime|necesitas|procedimiento|directorio/u', $r)) {
            return ['OK', 'recupera_hilo'];
        }
        if (preg_match('/1\.\s*\*\*mis procedimientos\*\*/u', $r)
            && preg_match('/2\.\s*\*\*directorio\*\*/u', $r)) {
            return ['FALLA', 'menu_123_tras_recovery'];
        }
    }
    if (preg_match('/\b(en bullets|mas corto|m[aá]s corto|formal)\b/u', $qf)) {
        if (preg_match('/cambiando a /u', $r)) {
            return ['FALLA', 'bullets_cambio_doc'];
        }
        if (mb_strlen($r) > 30) {
            return ['OK', 'formato_followup'];
        }
    }
    if (preg_match('/\b(factura|telcel)\b/u', $qf)) {
        if (preg_match('/gasto|cobro|proveedor|cliente|cuenta|pagar|orient/u', $r)) {
            return ['OK', 'factura_aclara'];
        }
    }
    if (preg_match('/\b(mis procedimientos|los mios)\b/u', $qf)) {
        if (preg_match('/procedimiento|puesto|logueado|sesi[oó]n|directorio|lista|no tengo/u', $r)) {
            return ['OK', 'mis_procs'];
        }
    }
    if (preg_match('/fuera_de_tema|directory_|catalog_|talk_|conversation_|chit|unpublished/u', $method)) {
        return ['OK', 'ruta_estructurada'];
    }
    if (preg_match('/paid_ai_integrated/u', $method) && mb_strlen($r) > 40) {
        if (!preg_match('/coincidencias:/u', $r)) {
            return ['OK', 'doc_ai'];
        }
        return ['FALLA', 'doc_coincidencias'];
    }
    if (preg_match('/context_clarify_keep_thread/u', $method)) {
        if (preg_match('/no borr/u', $r)) {
            return ['OK', 'mantiene_hilo_aclara'];
        }
    }
    if (mb_strlen($r) >= 40) {
        return ['OK', 'respuesta_util'];
    }
    return ['FALLA', 'no_clasificado'];
}

function redFlags(string $q, string $resp, string $method, int $ms): array
{
    $s = mb_strtolower($resp);
    $qf = mb_strtolower($q);
    $flags = [];
    if (str_contains($s, 'coincidencias')) {
        $flags[] = 'coincidencias';
    }
    if (preg_match('/1\.\s*\*\*mis procedimientos\*\*.*2\.\s*\*\*directorio\*\*/us', $s)) {
        $flags[] = 'menu_123';
    }
    if ($ms > 10000) {
        $flags[] = 'lento_10s';
    }
    if (str_contains($s, 'cambiando a ')) {
        $flags[] = 'cambio_doc_inesperado';
    }
    if (preg_match('/\b(volvamos|me perd)/u', $qf) && str_contains($method, 'offer_clarify')) {
        $flags[] = 'recovery_a_menu';
    }
    if (preg_match('/\b(necesito algo de|solitud de)\b/u', $qf)
        && str_contains($method, 'paid_ai')) {
        $flags[] = 'vago_a_pdf';
    }
    if (preg_match('/\b(areas|organizada|quien me puede ayudar)\b/u', $qf)
        && str_contains($s, 'coincidencias')) {
        $flags[] = 'dir_a_coincidencias';
    }
    if (str_contains($method, 'conversation_topic_recovery')) {
        $flags[] = 'ok_topic_recovery';
    }
    if (str_contains($method, 'conversation_vague_topic_clarify')) {
        $flags[] = 'ok_vague_clarify';
    }
    if (str_contains($method, 'unpublished_topic')) {
        $flags[] = 'ok_unpublished';
    }
    if (str_contains($method, 'directory_who_can_help') || str_contains($method, 'directory_company_units')) {
        $flags[] = 'ok_directory_route';
    }
    return $flags;
}

$results = [];
$byGap = [];
$byNivel = ['basico' => ['ok' => 0, 'falla' => 0, 'corr' => 0, 'n' => 0], 'experto' => ['ok' => 0, 'falla' => 0, 'corr' => 0, 'n' => 0]];
$byMethod = [];
$flagsCount = [];
$ok = $falla = $corr = 0;
$latencias = [];

$personasRun = array_slice($personas, 0, $maxPersonas);
echo 'Simulando ' . count($personasRun) . ' personas x ' . count($turnosMuestra) . " turnos...\n";

foreach ($personasRun as $p) {
    $sid = $p['session_id'];
    $nivel = $p['nivel'] ?? 'basico';
    $preguntas = $p['preguntas'];
    foreach ($turnosMuestra as $t) {
        if (!isset($preguntas[$t - 1])) {
            continue;
        }
        $q = $preguntas[$t - 1];
        $qUser = preg_replace('/\s*\[u\d+\|[^\]]+\]#\d+\s*$/u', '', $q) ?? $q;
        $qUser = trim($qUser);

        $t0 = microtime(true);
        $err = null;
        $out = null;
        try {
            $out = $svc->processQuery($qUser, null, $sid);
        } catch (Throwable $e) {
            $err = $e;
        }
        $ms = (int) round((microtime(true) - $t0) * 1000);
        $latencias[] = $ms;
        $resp = (string) ($out['response'] ?? ($err ? $err->getMessage() : ''));
        $method = (string) ($out['method'] ?? ($err ? 'exception' : ''));

        [$verdict, $gap] = classify($qUser, $resp, $method, $err);
        $byGap[$gap] = ($byGap[$gap] ?? 0) + 1;
        $byMethod[$method] = ($byMethod[$method] ?? 0) + 1;
        $byNivel[$nivel]['n']++;
        if ($verdict === 'OK') {
            $ok++;
            $byNivel[$nivel]['ok']++;
        } elseif ($verdict === 'CORROMPE_SERVICIO') {
            $corr++;
            $byNivel[$nivel]['corr']++;
        } else {
            $falla++;
            $byNivel[$nivel]['falla']++;
        }

        foreach (redFlags($qUser, $resp, $method, $ms) as $f) {
            $flagsCount[$f] = ($flagsCount[$f] ?? 0) + 1;
        }

        $results[] = [
            'persona_id' => $p['persona_id'],
            'nivel' => $nivel,
            'perfil' => $p['perfil'],
            'turno' => $t,
            'pregunta' => $qUser,
            'resultado' => $verdict,
            'gap' => $gap,
            'method' => $method,
            'ms' => $ms,
            'respuesta_snip' => mb_substr(preg_replace('/\s+/u', ' ', $resp) ?? '', 0, 160),
        ];
        echo sprintf(
            "%s %s T%02d %s [%s] %dms | %s\n",
            $p['persona_id'],
            $nivel,
            $t,
            $verdict,
            $gap,
            $ms,
            mb_substr($qUser, 0, 36)
        );
    }
}

sort($latencias);
$n = max(1, count($latencias));
$p50 = $latencias[(int) floor(($n - 1) * 0.5)];
$p95 = $latencias[(int) floor(($n - 1) * 0.95)];
$avg = (int) round(array_sum($latencias) / $n);
arsort($byGap);
arsort($byMethod);
arsort($flagsCount);

// Cargar R4 para comparación si existe
$r4Path = __DIR__ . '/metricas_ronda4.json';
$r4 = is_file($r4Path) ? json_decode(file_get_contents($r4Path), true) : null;
$comparacion = null;
if ($r4) {
    $comparacion = [
        'r4' => [
            'interacciones' => $r4['total_interacciones'] ?? null,
            'pct_ok' => $r4['pct_ok'] ?? null,
            'latencia' => $r4['latencia_ms'] ?? null,
        ],
        'r5' => [
            'interacciones' => count($results),
            'pct_ok' => round(100 * $ok / max(1, count($results)), 1),
            'latencia' => ['avg' => $avg, 'p50' => $p50, 'p95' => $p95, 'max' => max($latencias)],
        ],
        'flags_r5' => $flagsCount,
        'nota' => 'R5 incluye expertos + mitigaciones GA; flags ok_* son señales positivas nuevas.',
    ];
}

$total = count($results);
$report = [
    'fecha' => date('c'),
    'ronda' => 5,
    'modo' => '100_personas_mixto_muestra',
    'personas_simuladas' => count($personasRun),
    'turnos_por_persona' => $turnosMuestra,
    'total_interacciones' => $total,
    'ok' => $ok,
    'falla' => $falla,
    'corrompe' => $corr,
    'pct_ok' => round(100 * $ok / max(1, $total), 1),
    'por_nivel' => $byNivel,
    'latencia_ms' => ['avg' => $avg, 'p50' => $p50, 'p95' => $p95, 'max' => max($latencias ?: [0])],
    'gaps' => $byGap,
    'methods' => $byMethod,
    'red_flags' => $flagsCount,
    'comparacion_r4' => $comparacion,
    'dataset_completo' => [
        'personas' => 100,
        'preguntas_por_persona' => 20,
        'total_preguntas_generadas' => 2000,
        'basicos' => 50,
        'expertos' => 50,
    ],
    'casos' => $results,
];

$outPath = __DIR__ . '/metricas_ronda5.json';
file_put_contents($outPath, json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo "\n=== METRICAS R5 ===\n";
echo "Interacciones: {$total} | OK:{$ok} FALLA:{$falla} CORR:{$corr} ({$report['pct_ok']}%)\n";
echo "Latencia avg/p50/p95/max: {$avg}/{$p50}/{$p95}/" . max($latencias ?: [0]) . " ms\n";
echo "Por nivel:\n";
foreach ($byNivel as $nv => $st) {
    $pct = $st['n'] ? round(100 * $st['ok'] / $st['n'], 1) : 0;
    echo "  - {$nv}: n={$st['n']} ok={$st['ok']} falla={$st['falla']} corr={$st['corr']} ({$pct}%)\n";
}
echo "Flags:\n";
foreach (array_slice($flagsCount, 0, 15, true) as $g => $c) {
    echo "  - {$g}: {$c}\n";
}
echo "Report: {$outPath}\n";
