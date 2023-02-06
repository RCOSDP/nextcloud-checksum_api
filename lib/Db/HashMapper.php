<?php

declare(strict_types=1);

namespace OCA\ChecksumAPI\Db;

require __DIR__ . '../../../vendor/autoload.php';

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class HashMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'checksum_api', Hash::class);
    }

    public function find(int $fileid, int $revision, string $type) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->tableName)
            ->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileid, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('revision', $qb->createNamedParameter($revision, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('type', $qb->createNamedParameter($type, IQueryBuilder::PARAM_STR)));

        try {
            $result = $this->findEntity($qb);
        } catch (DoesNotExistException $e) {
            return null;
        }

        return $result;
    }

    public function findAll(int $fileid) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->tableName)
            ->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileid, IQueryBuilder::PARAM_INT)));

        return $this->findEntities($qb);
    }
}
