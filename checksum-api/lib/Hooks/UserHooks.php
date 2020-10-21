<?php

declare(strict_types=1);

namespace OCA\ChecksumAPI\Hooks;

use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\ILogger;
use OC\Files\Filesystem;
use OC\Files\Node\Node;

use OCA\ChecksumAPI\Db\HashMapper;

class UserHooks {

    private $logger;
    private $rootFolder;
    private $mapper;

    public function __construct(ILogger $logger,
                                IRootFolder $rootFolder,
                                IDBConnection $databaseConnection) {
        $this->logger = $logger;
        $this->rootFolder = $rootFolder;
        $this->mapper = new HashMapper($databaseConnection);
    }

    public function register() {
        $callback = function(Node $node) {
            $fileid = $node->getId();
            $mtime = $node->getMTime();
            $this->logger->info('fileid: ' . strval($fileid) . ' mtime: ' . strval($mtime));
            $entities = $this->mapper->findAll($fileid);
            if (!empty($entities)) {
                foreach ($entities as $entity) {
                    $this->mapper->delete($entity);
                }
                $this->logger->info('delete all records related to the file.');
            }
        };
        $this->rootFolder->listen('\OC\Files', 'preDelete', $callback);
        \OCP\Util::connectHook('\OCP\Versions', 'preDelete', $this, 'deleteRecordOnVersionDelete');
    }

    public function deleteRecordOnVersionDelete(array $params) {
        $path = $params['path'];
        $reason = $params['trigger'];
        $this->logger->info('path: ' . $path . ', reason: ' . strval($reason));
        if ($reason !== \OCA\Files_Versions\Storage::DELETE_TRIGGER_MASTER_REMOVED) {
            $delim_pos = strrpos($path, '.v', 0);
            $base_path = substr($path, 0, $delim_pos);
            $mtime_part = substr($path, $delim_pos + 2);
            $mtime = intval($mtime_part);
            $this->logger->info('base_path: ' . $base_path . ', mtime: ' . strval($mtime));

            $userFolder = \OC::$server->getUserFolder();
            $node = $userFolder->get($base_path);
            $fileid = $node->getId();
            $latest_mtime = $node->getMTime();
            $entities = $this->mapper->findAll($fileid);
            if (!empty($entities)) {
                foreach ($entities as $entity) {
                    if ($mtime === $entity->getRevision()) {
                        $this->mapper->delete($entity);
                    }
                }
            }
        }
    }
}
