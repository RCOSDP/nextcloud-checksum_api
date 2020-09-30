<?php

declare(strict_types=1);

namespace OCA\ChecksumAPI\Db;

use OCP\AppFramework\Db\Entity;

class Hash extends Entity {

    protected $fileid;
    protected $revision;
    protected $hash;

    public function __constrct() {
        $this->addType('fileid', 'integer');
        $this->addType('revision', 'integer');
    }
}