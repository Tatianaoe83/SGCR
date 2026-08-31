<?php
/**
 * Simulación Ronda 4 — muestra por persona (usuarios básicos).
 * Corre turnos clave de cada chat con la MISMA session_id (mantiene hilo).
 *
 * Uso: php tests/bob_qa/sim_ronda4_sample.php
 */
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\HybridChatbotService;

$personasPath = __DIR__ . '/personas_ronda4.json';
if (!is_file($personasPath)) {
    fwrite(STDERR, "Falta personas_ronda4.json. Corre: python generate_ronda4_personas.py\n");
    exit(1);
}

$data = json_decode(file_get_contents($personasPath), true);
$personas = $data['personas'] ?? [];
$svc = app(HybridChatbotService::class);

// Turnos 1-based representativos del arco básico
$turnosMuestra = [1, 4, 10, 15, 20, 29, 35, 42];
$maxPersonas = (int) (getenv('SIM_PERSONAS') ?: 50);

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

    // Señales de falla graves
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

    // Éxitos por tipo de pregunta
    if (preg_match('/\b(hola|holi|buenas|hey)\b/u', $qf)) {
        if (preg_match('/hola|bob|ayud|procedimiento|directorio/u', $r)) {
            return ['OK', 'saludo'];
        }
    }
    if (preg_match('/\bunidades?\b/u', $qf)) {
        if (preg_match('/unidad|agregados|construcci|corporativo/u', $r)) {
            return ['OK', 'unidades_bd'];
        }
    }
    if (preg_match('/\b(mi jefe|a quien reporto|qui[eé]n me)\b/u', $qf)) {
        if (preg_match('/jefe|puesto|directorio|no (tengo|model)|report/u', $r)) {
            return ['OK', 'mi_jefe_honesto'];
        }
        if (preg_match('/coincidencias/u', $r)) {
            return ['FALLA', 'mi_jefe_a_coincidencias'];
        }
    }
    if (preg_match('/\b(factura|telcel)\b/u', $qf)) {
        if (preg_match('/gasto|cobro|proveedor|cliente|cuenta|pagar|orient/u', $r)) {
            return ['OK', 'factura_aclara'];
        }
    }
    if (preg_match('/\b(ese no es|no eso|me perd)/u', $qf)) {
        if (preg_match('/ok|suelto|retom|hilo|dime|necesitas|procedimiento|directorio/u', $r)) {
            return ['OK', 'recupera_hilo'];
        }
    }
    if (preg_match('/\b(mis procedimientos|los mios)\b/u', $qf)) {
        if (preg_match('/procedimiento|puesto|logueado|sesi[oó]n|directorio|lista|no tengo/u', $r)) {
            return ['OK', 'mis_procs'];
        }
    }
    if (preg_match('/fuera_de_tema|directory_|catalog_|talk_|conversation_|chit/u', $method)) {
        return ['OK', 'ruta_estructurada'];
    }
    if (preg_match('/paid_ai_integrated/u', $method) && mb_strlen($r) > 40) {
        // Doc path: OK si no es coincidencias basura
        if (!preg_match('/coincidencias:/u', $r)) {
            return ['OK', 'doc_ai'];
        }
        return ['FALLA', 'doc_coincidencias'];
    }
    if (preg_match('/context_clarify_keep_thread/u', $method)) {
        // Mantener hilo es bueno, pero si el PDF es ajeno al tema es falla suave
        if (preg_match('/no borr/u', $r)) {
            return ['OK', 'mantiene_hilo_aclara'];
        }
    }

    // Default: respuesta útil sustancial
    if (mb_strlen($r) >= 40) {
        return ['OK', 'respuesta_util'];
    }
    return ['FALLA', 'no_clasificado'];
}

$results = [];
$byGap = [];
$ok = $falla = $corr = 0;
$latencias = [];

$personasRun = array_slice($personas, 0, $maxPersonas);
echo "Simulando " . count($personasRun) . " personas x " . count($turnosMuestra) . " turnos...\n";

foreach ($personasRun as $p) {
    $sid = $p['session_id'];
    $preguntas = $p['preguntas'];
    foreach ($turnosMuestra as $t) {
        if (!isset($preguntas[$t - 1])) {
            continue;
        }
        $q = $preguntas[$t - 1];
        // Quitar tag técnico largo para simular usuario real un poco más limpio
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
        if ($verdict === 'OK') {
            $ok++;
        } elseif ($verdict === 'CORROMPE_SERVICIO') {
            $corr++;
        } else {
            $falla++;
        }

        $row = [
            'persona_id' => $p['persona_id'],
            'perfil' => $p['perfil'],
            'turno' => $t,
            'pregunta' => $qUser,
            'resultado' => $verdict,
            'gap' => $gap,
            'method' => $method,
            'ms' => $ms,
            'respuesta_snip' => mb_substr(preg_replace('/\s+/u', ' ', $resp) ?? '', 0, 160),
        ];
        $results[] = $row;
        echo sprintf(
            "%s T%02d %s [%s] %dms | %s\n",
            $p['persona_id'],
            $t,
            $verdict,
            $gap,
            $ms,
            mb_substr($qUser, 0, 40)
        );
    }
}

sort($latencias);
$n = max(1, count($latencias));
$p50 = $latencias[(int) floor(($n - 1) * 0.5)];
$p95 = $latencias[(int) floor(($n - 1) * 0.95)];
$avg = (int) round(array_sum($latencias) / $n);

arsort($byGap);
$total = count($results);
$report = [
    'fecha' => date('c'),
    'ronda' => 4,
    'modo' => '50_personas_basicas_muestra',
    'personas_simuladas' => count($personasRun),
    'turnos_por_persona' => $turnosMuestra,
    'total_interacciones' => $total,
    'ok' => $ok,
    'falla' => $falla,
    'corrompe' => $corr,
    'pct_ok' => round(100 * $ok / max(1, $total), 1),
    'latencia_ms' => ['avg' => $avg, 'p50' => $p50, 'p95' => $p95, 'max' => max($latencias)],
    'gaps' => $byGap,
    'dataset_completo' => [
        'personas' => 50,
        'preguntas_por_persona' => 50,
        'total_preguntas_generadas' => 2500,
        'nota' => 'La métrica live usa muestra de turnos clave; el dataset completo está en preguntas_bob_qa.json',
    ],
    'casos' => $results,
];

$outPath = __DIR__ . '/metricas_ronda4.json';
file_put_contents($outPath, json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo "\n=== METRICAS R4 ===\n";
echo "Interacciones: {$total} | OK:{$ok} FALLA:{$falla} CORR:{$corr} ({$report['pct_ok']}%)\n";
echo "Latencia avg/p50/p95/max: {$avg}/{$p50}/{$p95}/" . max($latencias) . " ms\n";
echo "Gaps top:\n";
foreach (array_slice($byGap, 0, 12, true) as $g => $c) {
    echo "  - {$g}: {$c}\n";
}
echo "Report: {$outPath}\n";
