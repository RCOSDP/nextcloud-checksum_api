<?php

declare(strict_types=1);

namespace OCA\ChecksumAPI\AppInfo;

use \OCP\AppFramework\App;

class Application extends App {
    public const APP_ID = 'checksum-api';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }
}