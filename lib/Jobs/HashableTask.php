<?php

declare(strict_types=1);

namespace OCA\ChecksumAPI\Jobs;

require __DIR__ . '../../../../../lib/composer/autoload.php';

use Amp\Parallel\Worker\Environment;
use Amp\Parallel\Worker\Task;
use OCA\ChecksumAPI\Db\Hash;

class HashableTask implements Task {
    /**
     * @var callable
     */
    private $function;

    private $args;

    public function __construct($function, $args) {
        $this->function = $function;
        $this->args = $args;
    }

    /**
     * {@inheritdoc}
     */
    public function run(Environment $environment) {
        $function = $this->function;
        return $this->$function($this->args);
    }

    public function hashCalculator($params) {
        $hash = hash_file($params["hashType"], $params["info"]);
        $entity = new Hash();
        $entity->setFileid($params["fileid"]);
        $entity->setRevision($params["revision"]);
        $entity->setType($params["hashType"]);
        $entity->setHash($hash);
        return $entity;
    }
}