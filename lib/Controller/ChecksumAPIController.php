<?php

declare(strict_types=1);

namespace OCA\ChecksumAPI\Controller;

use OC\Files\Filesystem;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUserSession;

use OCA\ChecksumAPI\Db\Hash;
use OCA\ChecksumAPI\Db\HashMapper;

class ChecksumAPIController extends OCSController {

    private $rootFolder;
    private $userSession;
    private $mapper;
    private $logger;
    private $hashType = 'sha512';
    private $versionAppId = 'files_versions';

    public function __construct($appName,
                                IRequest $request,
                                IRootFolder $rootFolder,
                                IUserSession $userSession,
                                HashMapper $mapper,
                                ILogger $logger) {
        parent::__construct($appName, $request);
        $this->rootFolder = $rootFolder;
        $this->userSession = $userSession;
        $this->mapper = $mapper;
        $this->logger = $logger;
    }

    private function getMatchedVersion(string $path, string $revision) {
        $user = $this->userSession->getUser();
        $versions = \OCA\Files_Versions\Storage::getVersions($user->getUID(), $path);
        foreach ($versions as $key => $value) {
            if ($revision === strval($value['version'])) {
                return $value;
            }
        }

        return null;
    }

    private function saveRecord(int $fileid, int $revision, string $hash) {
        $entity = new Hash();
        $entity->setFileid($fileid);
        $entity->setRevision($revision);
        $entity->setHash($hash);
        $this->mapper->insert($entity);
        $this->logger->info(sprintf('save a record(fileid: %d, revision: %d) to database', $fileid, $revision));

        return $entity;
    }

    /**
     * get hash value from database or original file
     * @NoAdminRequired
     * @param (string) $path - path to file
     * @param (string) $revision - revision of file
     */
    public function checksum($path, $revision) {
        $this->logger->info('path argument: ' . $path);
        $this->logger->info('revision argument: ' . $revision);

        if (is_null($path)) {
            $this->logger->error('query parameter path is missing.');
            return new DataResponse(
                'query parameter path is missing',
                Http::STATUS_BAD_REQUEST
            );
        }

        $userFolder = $this->rootFolder->getUserFolder($this->userSession->getUser()->getUID());
        try {
            $node = $userFolder->get($path);
        } catch (NotFoundException $e) {
            $this->logger->error('file not found at specified path: ' . $path);
            return new DataResponse(
                'file not found at specified path: ' . $path,
                Http::STATUS_NOT_FOUND
            );
        }
        $fileid = $node->getId();

        $latestRevision = $node->getMTime();
        $targetRevision = $latestRevision;
        if (!is_null($revision)) {
            if (!is_numeric($revision)) {
                $this->logger->error('invalid version is specified');
                return new DataResponse(
                    'invalid revision is specified',
                    Http::STATUS_BAD_REQUEST
                );
            }

            //check if a latest version matches
            if ($revision === strval($latestRevision)) {
                $this->logger->info('latest version matches');
            } else {
                // check if version function is enabled
                if (!\OCP\App::isEnabled($this->versionAppId)) {
                    $this->logger->error('version function is not enabled');
                    return new DataResponse(
                        'version function is not enabled',
                        Http::STATUS_NOT_IMPLEMENTED
                    );
                }
                $this->logger->info($this->versionAppId . ' is enabled');

                $version = $this->getMatchedVersion($path, $revision);
                if (is_null($version)) {
                    $this->logger->error('specified revision is not found');
                    return new DataResponse(
                        'specified revision is not found',
                        Http::STATUS_NOT_FOUND
                    );
                }
                $targetRevision = intval($version['version']);
                $this->logger->info('version ' . $version['version'] . ' matches');
            }
        }

        $entity = $this->mapper->find($fileid, $targetRevision);
        if (is_null($entity)) {
            if ($targetRevision === $latestRevision) {
                $storage = $userFolder->getStorage();
                $info = $storage->getLocalFile($node->getInternalPath());
            } else {
                $user = $this->userSession->getUser();
                $targetFile = $user->getUID() . '/files_versions' . $version['path'] . '.v' . $version['version'];
                $view = new \OC\Files\View('/');
                $info = $view->getLocalFile($targetFile);
            }
            $hash = hash_file($this->hashType, $info);
            $this->logger->info($hash);
            $entity = $this->saveRecord($fileid, $targetRevision, $hash);
        }

        $res = [];
        $res['hash'] = $entity->getHash();
        return new DataResponse(
            $res,
            Http::STATUS_OK
        );
    }
}
