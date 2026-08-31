<?php
/**
 * Verifica decisiones de plática (catálogo vs sección vs insistencia).
 * Uso: php tests/bob_qa/_decision_smoke.php
 */
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\HybridChatbotService;
use Illuminate\Support\Facades\DB;

$svc = app(HybridChatbotService::class);
$ref = new ReflectionClass($svc);

$call = function (string $method, array $args) use ($svc, $ref) {
    $m = $ref->getMethod($method);
    $m->setAccessible(true);
    return $m->invokeArgs($svc, $args);
};

$pass = 0;
$fail = 0;
$check = function (string $name, $got, $want) use (&$pass, &$fail) {
    $ok = $got === $want;
    echo ($ok ? 'OK  ' : 'FAIL') . " {$name} => " . var_export($got, true)
        . ($ok ? '' : ' (esperaba ' . var_export($want, true) . ')') . PHP_EOL;
    if ($ok) {
        $pass++;
    } else {
        $fail++;
    }
};

$check(
    'catalog: coordinador de calidad procedimientos',
    $call('isCatalogBrowseQuery', ['el coordinador de calidad que procedimientos tiene']),
    true
);
$check(
    'NO catalog: evidencias de Auditorías',
    $call('isCatalogBrowseQuery', ['de Realizar Auditorías al Sistema de Gestión de Calidad cuales son sus evidencias?']),
    false
);
$check(
    'sección: evidencias nombradas',
    $call('isDocumentSectionQuery', ['de Realizar Auditorías al Sistema de Gestión de Calidad cuales son sus evidencias?']),
    true
);
$check(
    'NO catalog: riesgos de programar pagos',
    $call('isCatalogBrowseQuery', ['me puedes decir los riesgos de programar pagos?']),
    false
);
$check(
    'insistencia: si existen',
    $call('isUserInsistingContentExists', ['si existen']),
    true
);
$check(
    'insistencia: sí hay',
    $call('isUserInsistingContentExists', ['sí hay']),
    true
);
$check(
    'NO insistencia: hola',
    $call('isUserInsistingContentExists', ['hola']),
    false
);
$check(
    'aspecto: riesgos',
    $call('detectQueryAspect', ['me puedes decir los riesgos de programar pagos']),
    'riesgos'
);
$check(
    'aspecto: evidencias',
    $call('detectQueryAspect', ['cuales son sus evidencias']),
    'evidencias'
);
$check(
    'contacto: con quien me puedo comunicar',
    $call('isWhoToContactQuery', ['Ninguno de esos, no tienes nada de vacaciones entonces. Con quien me puedo comunicar?']),
    true
);
$check(
    'directorio: vacaciones + comunicar',
    $call('isPeopleOrOrgDirectoryQuery', ['Ninguno de esos, no tienes nada de vacaciones entonces. Con quien me puedo comunicar?']),
    true
);
$check(
    'rechazo: ninguno de esos',
    $call('isRejectingOfferedOptions', ['Ninguno de esos, no tienes nada de vacaciones entonces. Con quien me puedo comunicar?']),
    true
);
$check(
    'tema RH: vacaciones',
    $call('detectHrPersonalTopic', ['no tienes nada de vacaciones entonces']),
    'vacaciones'
);
$check(
    'hint no cuadra: vuelos vs vacaciones',
    $call('docHintFitsRecentTopic', [
        ['id' => 1, 'title' => 'Gestionar Vuelos'],
        'hint-test',
        null,
        'no tienes nada de vacaciones entonces',
    ]),
    false
);

$doc = DB::table('word_documents as w')
    ->join('elementos as e', 'e.id_elemento', '=', 'w.elemento_id')
    ->where('e.folio_elemento', 'like', 'PAA06-PR01%')
    ->orWhereRaw('LOWER(e.nombre_elemento) LIKE ?', ['%programar pagos%'])
    ->select('w.id', 'e.nombre_elemento', 'e.folio_elemento')
    ->first();

if ($doc) {
    $raw = (string) DB::table('word_documents')->where('id', $doc->id)->value('contenido_texto');
    $hasRiesgo = (bool) preg_match('/riesgo/iu', $raw);
    echo PHP_EOL . "Word {$doc->folio_elemento} {$doc->nombre_elemento} id={$doc->id}"
        . ' chars=' . mb_strlen($raw) . ' tiene_riesgo=' . ($hasRiesgo ? 'si' : 'no') . PHP_EOL;
    if ($hasRiesgo) {
        if (preg_match('/.{0,40}RIESGOS.{0,80}/iu', $raw, $m)) {
            echo 'snippet: ' . preg_replace('/\s+/', ' ', $m[0]) . PHP_EOL;
        }
        $snips = $call('extractKeywordSectionSnippets', [(int) $doc->id, 'riesgos de programar pagos']);
        echo 'snippets_keyword=' . count($snips) . PHP_EOL;
        if (!empty($snips[0])) {
            echo 'kw0: ' . mb_substr(preg_replace('/\s+/', ' ', $snips[0]), 0, 220) . PHP_EOL;
        }
    }
} else {
    echo PHP_EOL . "No encontré Word de Programar Pagos en BD." . PHP_EOL;
}

echo PHP_EOL . "Resultado: {$pass} ok, {$fail} fail" . PHP_EOL;
exit($fail > 0 ? 1 : 0);
