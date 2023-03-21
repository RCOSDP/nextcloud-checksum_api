<?php

declare(strict_types=1);

namespace OCA\ChecksumAPI\Db;

require __DIR__ . '../../../../../lib/composer/autoload.php';

use OCP\AppFramework\Db\Entity;

class Hash extends Entity {

    protected $fileid;
    protected $revision;
    protected $type;
    protected $hash;

    public function __constrct() {
        $this->addType('fileid', 'integer');
        $this->addType('revision', 'integer');
    }
}