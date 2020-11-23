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
            $mtime = substr($path, $delim_pos + 2);
            $this->logger->info('base_path: ' . $base_path . ', mtime: ' . $mtime);

            $owner = '';
            $userManager = \OC::$server->getUserManager();
            $backends = $userManager->getBackends();
            foreach ($backends as $backend) {
                $users = $backend->getUsers();
                foreach ($users as $user) {
                    $tmpPath = '/' . $user . '/files_versions';
                    $view = new View($tmpPath);
                    if ($view->file_exists($path)) {
                        $owner = $user;
                    }
                }
            }

            $userView = new View('/' . $owner . '/files');
            $info = $userView->getFileInfo($base_path);
            if ($info === false) {
                return;
            }

            $fileid = $info->getId();
            $this->logger->info('id: ' . $fileid);
            $entities = $this->mapper->findAll($fileid);
            if (!empty($entities)) {
                foreach ($entities as $entity) {
                    $this->logger->info('revision: ' . $entity->getRevision());
                    if ($mtime === $entity->getRevision()) {
                        $this->logger->info('delete');
                        $this->mapper->delete($entity);
                    }
                }
            }
        }
    }
}
