<?php
/**
 * Simulación Ronda 6 — 30 personas conocimiento aleatorio.
 * Uso: php tests/bob_qa/sim_ronda6_sample.php
 */
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\HybridChatbotService;

$personasPath = __DIR__ . '/personas_ronda6.json';
if (!is_file($personasPath)) {
    fwrite(STDERR, "Falta personas_ronda6.json. Corre: python generate_ronda6_personas.py\n");
    exit(1);
}

$data = json_decode(file_get_contents($personasPath), true);
$personas = $data['personas'] ?? [];
$svc = app(HybridChatbotService::class);
$turnosMuestra = [1, 2, 5, 7, 10, 11, 14, 16];
$maxPersonas = (int) (getenv('SIM_PERSONAS') ?: 30);

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
    if (preg_match('/borr[eé] el contexto/u', $r)) {
        return ['FALLA', 'dijo_que_borro_contexto'];
    }
    if (preg_match('/\[object object\]/u', $r)) {
        return ['FALLA', 'chip_object_object'];
    }
    if (mb_strlen(trim($resp)) < 8) {
        return ['FALLA', 'respuesta_vacia'];
    }
    if (preg_match('/\b(necesito algo de|solitud de)\b/u', $qf)) {
        if (preg_match('/paid_ai_integrated/u', $method) && !preg_match('/te refieres|entiendo|orient/u', $r)) {
            return ['FALLA', 'vago_a_pdf_sin_confirmar'];
        }
        if (preg_match('/vague|clarify|unpublished|refieres|entiendo|cierre|pago/u', $method . ' ' . $r)) {
            return ['OK', 'vago_aclara'];
        }
    }
    if (preg_match('/\blista(r|do)? los? directores?\b/u', $qf) || preg_match('/\bdirectores\b/u', $qf) && preg_match('/\blista/u', $qf)) {
        if (preg_match('/directory_/u', $method) || preg_match('/director/u', $r)) {
            return ['OK', 'directores_bd'];
        }
        if (preg_match('/paid_ai/u', $method)) {
            return ['FALLA', 'directores_a_ia'];
        }
    }
    if (preg_match('/\bcompara\b/u', $qf)) {
        if (preg_match('/compare_procedures/u', $method) || preg_match('/uno a la vez|detect[eé]|dos procedimientos/u', $r)) {
            return ['OK', 'comparar_ok'];
        }
        if (preg_match('/paid_ai/u', $method) && preg_match('/cambiando a/u', $r)) {
            return ['FALLA', 'comparar_a_un_pdf'];
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
    if (preg_match('/\b(volvamos|me perd|ese no es)\b/u', $qf)) {
        if (preg_match('/topic_recovery|ok|suelto|retom|dime/u', $method . ' ' . $r)) {
            return ['OK', 'recupera_hilo'];
        }
    }
    if (preg_match('/fuera_de_tema|directory_|catalog_|conversation_|unpublished|compare/u', $method)) {
        return ['OK', 'ruta_estructurada'];
    }
    if (preg_match('/paid_ai_integrated/u', $method) && mb_strlen($r) > 40) {
        return ['OK', 'doc_ai'];
    }
    if (mb_strlen($r) >= 40) {
        return ['OK', 'respuesta_util'];
    }
    return ['FALLA', 'no_clasificado'];
}

$results = [];
$byGap = [];
$byNivel = [];
$byMethod = [];
$flags = [];
$ok = $falla = $corr = 0;
$latencias = [];

$personasRun = array_slice($personas, 0, $maxPersonas);
echo 'Simulando ' . count($personasRun) . ' personas x ' . count($turnosMuestra) . " turnos...\n";

foreach ($personasRun as $p) {
    $sid = $p['session_id'];
    $nivel = $p['nivel'] ?? 'basico';
    if (!isset($byNivel[$nivel])) {
        $byNivel[$nivel] = ['ok' => 0, 'falla' => 0, 'corr' => 0, 'n' => 0];
    }
    foreach ($turnosMuestra as $t) {
        if (!isset($p['preguntas'][$t - 1])) {
            continue;
        }
        $q = $p['preguntas'][$t - 1];
        $qUser = trim(preg_replace('/\s*\[u\d+\|[^\]]+\]#\d+\s*$/u', '', $q) ?? $q);
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
        if (preg_match('/cambiando a /ui', $resp)) {
            $flags['cambio_doc'] = ($flags['cambio_doc'] ?? 0) + 1;
        }
        if (str_contains($method, 'vague_topic')) {
            $flags['ok_vague'] = ($flags['ok_vague'] ?? 0) + 1;
        }
        if (str_contains($method, 'compare_procedures')) {
            $flags['ok_compare'] = ($flags['ok_compare'] ?? 0) + 1;
        }
        if (str_contains($method, 'directory_company_directors')) {
            $flags['ok_directores'] = ($flags['ok_directores'] ?? 0) + 1;
        }
        if (str_contains($method, 'topic_recovery')) {
            $flags['ok_recovery'] = ($flags['ok_recovery'] ?? 0) + 1;
        }
        if ($ms > 10000) {
            $flags['lento_10s'] = ($flags['lento_10s'] ?? 0) + 1;
        }
        $results[] = [
            'persona_id' => $p['persona_id'],
            'nivel' => $nivel,
            'turno' => $t,
            'pregunta' => $qUser,
            'resultado' => $verdict,
            'gap' => $gap,
            'method' => $method,
            'ms' => $ms,
            'respuesta_snip' => mb_substr(preg_replace('/\s+/u', ' ', $resp) ?? '', 0, 160),
        ];
        echo sprintf("%s %s T%02d %s [%s] %dms | %s\n", $p['persona_id'], $nivel, $t, $verdict, $gap, $ms, mb_substr($qUser, 0, 40));
    }
}

sort($latencias);
$n = max(1, count($latencias));
$p50 = $latencias[(int) floor(($n - 1) * 0.5)];
$p95 = $latencias[(int) floor(($n - 1) * 0.95)];
$avg = (int) round(array_sum($latencias) / $n);
arsort($byGap);
arsort($byMethod);
$total = count($results);

$r5Path = __DIR__ . '/metricas_ronda5.json';
$r5 = is_file($r5Path) ? json_decode(file_get_contents($r5Path), true) : null;

$report = [
    'fecha' => date('c'),
    'ronda' => 6,
    'modo' => '30_conocimiento_aleatorio',
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
    'flags' => $flags,
    'vs_r5' => $r5 ? [
        'r5_pct_ok' => $r5['pct_ok'] ?? null,
        'r5_avg_ms' => $r5['latencia_ms']['avg'] ?? null,
        'r6_pct_ok' => round(100 * $ok / max(1, $total), 1),
        'r6_avg_ms' => $avg,
    ] : null,
    'casos' => $results,
];
file_put_contents(__DIR__ . '/metricas_ronda6.json', json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo "\n=== METRICAS R6 ===\n";
echo "Interacciones: {$total} | OK:{$ok} FALLA:{$falla} CORR:{$corr} ({$report['pct_ok']}%)\n";
echo "Latencia avg/p50/p95/max: {$avg}/{$p50}/{$p95}/" . max($latencias ?: [0]) . " ms\n";
foreach ($byNivel as $nv => $st) {
    $pct = $st['n'] ? round(100 * $st['ok'] / $st['n'], 1) : 0;
    echo "  {$nv}: {$st['ok']}/{$st['n']} ({$pct}%)\n";
}
echo "Flags: " . json_encode($flags, JSON_UNESCAPED_UNICODE) . "\n";
echo "Report: tests/bob_qa/metricas_ronda6.json\n";
