<?php

declare(strict_types=1);

namespace OCA\ChecksumAPI\AppInfo;

use \OCP\AppFramework\App;
use \OCA\ChecksumAPI\Hooks\UserHooks;

class Application extends App {
    public const APP_ID = 'checksum_api';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
        $container = $this->getContainer();

        /**
         * Controllers
         */
        $container->registerService('UserHooks', function($c) {
            return new UserHooks(
                $c->get('ServerContainer')->getLogger(),
                $c->get('ServerContainer')->getRootFolder(),
                $c->get('ServerContainer')->getDatabaseConnection(),
            );
        });
    }

    public function register() {
        $this->registerHooks();
    }

    public function registerHooks() {
        $container = $this->getContainer();
        $container->get('UserHooks')->register();
    }
}