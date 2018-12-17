<?php
declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use App\Kernel;

$kernel = (new Kernel())->boot();
$kernel->handleRequest();