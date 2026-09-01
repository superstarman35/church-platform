<?php

declare(strict_types=1);

use App\Repositories\TrialManagementRepository;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$pdo = require dirname(__DIR__) . '/bootstrap/app.php';
$limit = isset($argv[1]) ? filter_var($argv[1], FILTER_VALIDATE_INT) : 100;
if ($limit === false || $limit < 1 || $limit > 500) {
    fwrite(STDERR, "Usage: php bin/expire-trials.php [1-500]\n");
    exit(2);
}

$expired = (new TrialManagementRepository($pdo))->expireDue((int) $limit);
echo 'Expired trials: ' . count($expired) . PHP_EOL;
if ($expired !== []) {
    echo 'Subscription IDs: ' . implode(',', $expired) . PHP_EOL;
}
