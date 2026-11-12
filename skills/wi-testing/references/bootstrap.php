<?php
/**
 * Avvio dei test del sito senza bootstrap del framework: solo l'autoload di
 * Composer e gli helper puri del core (e(), e_br(), sanitize(), sanitizeEcho()). Niente DB.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
if (!is_file($root.'/vendor/autoload.php')) {
    fwrite(STDERR, "vendor/autoload.php non trovato: lancia composer install.\n");
    exit(1);
}
require $root.'/vendor/autoload.php';

$appFunctions = $root.'/vendor/wonder-image/app/app/function';
require_once $appFunctions.'/helper.php';
require_once $appFunctions.'/string/common.php';
require_once $appFunctions.'/string/sanitize.php';

$GLOBALS['__tests'] = 0;
$GLOBALS['__failures'] = 0;

function check(string $name, callable $fn): void
{
    $GLOBALS['__tests']++;
    try {
        $ok = $fn() !== false;
    } catch (\Throwable $e) {
        $ok = false;
        $name .= ' — '.$e->getMessage();
    }
    if (!$ok) {
        $GLOBALS['__failures']++;
    }
    echo ($ok ? '  ✓ ' : '  ✗ ').$name."\n";
}

function summary(): never
{
    echo "\n{$GLOBALS['__tests']} test, {$GLOBALS['__failures']} falliti\n";
    exit($GLOBALS['__failures'] > 0 ? 1 : 0);
}
