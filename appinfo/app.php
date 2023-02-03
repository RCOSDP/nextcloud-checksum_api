<?php

declare(strict_types=1);

use OCA\ChecksumAPI\AppInfo\Application;

$app = \OC::$server->get(Application::class);
$app->register();