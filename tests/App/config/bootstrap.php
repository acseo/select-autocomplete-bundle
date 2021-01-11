<?php

declare(strict_types=1);

require dirname(__DIR__).'/../../vendor/autoload.php';

$dotEnv = new Symfony\Component\Dotenv\Dotenv();

if (method_exists($dotEnv, 'usePutenv')) {
    $dotEnv->usePutenv(true);
}

$dotEnv->loadEnv(sprintf('%s/.env', dirname(__DIR__)));
