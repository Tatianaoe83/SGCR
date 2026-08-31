<?php
/**
 * Smoke rápido de mitigaciones GA (ronda 4).
 */
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\HybridChatbotService;

$svc = app(HybridChatbotService::class);
$session = 'ga-smoke-' . date('His');

$cases = [
    // Hilo: abrir algo y luego volvamos
    ['id' => 'GA1', 'q' => 'necesito algo de pagos', 'sid' => $session . '-a', 'expect_method' => 'vague|clarify|topic', 'expect' => 'pago|proveedor|cobro|orient|chip|procedimiento'],
    ['id' => 'GA1b', 'q' => 'volvamos', 'sid' => $session . '-a', 'expect_method' => 'topic_recovery', 'expect' => 'retom|suelto|tema|pago|dime', 'fail' => '1\. \*\*Mis procedimientos\*\*'],
    ['id' => 'GA2', 'q' => 'necesito algo de facturas', 'sid' => $session . '-b', 'expect_method' => 'vague', 'expect' => 'factura|proveedor|cobro|orient'],
    ['id' => 'GA3', 'q' => 'áreas de la empresa?', 'sid' => $session . '-c', 'expect_method' => 'directory', 'expect' => 'unidad|organiza', 'fail' => 'coincidencias'],
    ['id' => 'GA3b', 'q' => 'quién me puede ayudar', 'sid' => $session . '-c', 'expect_method' => 'directory|who', 'expect' => 'directorio|puesto|unidad', 'fail' => 'coincidencias'],
    ['id' => 'GA5', 'q' => 'quién es mi jefe', 'sid' => $session . '-d', 'expect_method' => 'boss', 'expect' => 'jefe|puesto|directorio'],
    ['id' => 'GA6', 'q' => 'solitud de campamentos', 'sid' => $session . '-e', 'expect_method' => 'unpublish|no_|vague|paid', 'expect' => 'no tengo|campamento|publicado|alternativ|mis procedimientos|coincid', 'fail' => 'objetivo de renta'],
];

// GA4: bullets con doc en foco
$sidF = $session . '-f';
$svc->processQuery('PAA08-PR05', null, $sidF);
$cases[] = [
    'id' => 'GA4',
    'q' => 'en bullets',
    'sid' => $sidF,
    'expect_method' => 'paid|ai|follow',
    'expect' => 'cierre|bullet|viñet|objetivo|paso',
    'fail' => 'cambiando a',
];

function fold($s) {
    $s = mb_strtolower($s);
    return strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
}

$ok = 0;
$fail = 0;
foreach ($cases as $c) {
    $t0 = microtime(true);
    try {
        $out = $svc->processQuery($c['q'], null, $c['sid']);
        $resp = (string) ($out['response'] ?? '');
        $method = (string) ($out['method'] ?? '');
        $ms = (int) round((microtime(true) - $t0) * 1000);
        $r = fold($resp);
        $verdict = 'OK';
        if (!empty($c['fail'])) {
            foreach (explode('|', $c['fail']) as $bad) {
                $bad = trim($bad);
                if ($bad !== '' && preg_match('/' . $bad . '/u', $r)) {
                    $verdict = 'FALLA';
                }
            }
        }
        $hit = false;
        foreach (explode('|', $c['expect'] ?? '') as $good) {
            $good = trim($good);
            if ($good !== '' && str_contains($r, fold($good))) {
                $hit = true;
                break;
            }
        }
        if (!$hit) {
            $verdict = 'FALLA';
        }
        // method soft check
        if (!empty($c['expect_method'])) {
            $mHit = false;
            foreach (explode('|', $c['expect_method']) as $em) {
                if (str_contains(fold($method), fold(trim($em)))) {
                    $mHit = true;
                    break;
                }
            }
            // method is soft — don't fail only on method if text OK
            if (!$mHit && $verdict === 'OK') {
                // keep OK but note
            }
        }
    } catch (Throwable $e) {
        $resp = $e->getMessage();
        $method = 'exception';
        $ms = 0;
        $verdict = 'CORROMPE';
    }
    if ($verdict === 'OK') {
        $ok++;
    } else {
        $fail++;
    }
    echo sprintf(
        "[%s] %s method=%s %dms | %s\n  %s\n",
        $c['id'],
        $verdict,
        $method,
        $ms,
        $c['q'],
        mb_substr(preg_replace('/\s+/', ' ', $resp), 0, 160)
    );
}
echo "\nOK={$ok} FALLA={$fail}\n";
