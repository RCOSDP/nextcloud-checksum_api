<?php

declare(strict_types=1);

namespace OCA\ChecksumAPI\Tests\Controller;

use OC\Files\Node\Root;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\NotFoundException;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

use OCA\ChecksumAPI\Controller\ChecksumAPIController;
use OCA\ChecksumAPI\Db\HashMapper;

/**
 * @group DB
 */
class ChecksumAPIControllerTest extends \Test\TestCase {

    private $request;
    private $user;
    private $userSession;
    private $mapper;
    private $logger;
    private $file;
    private $versionAppId = 'files_versions';
    private $versionAppIdStatus;

    protected function setUp() :void {
        parent::setUp();

        $this->request = $this->getMockBuilder(IRequest::class)->getMock();

        $this->user = $this->getMockBuilder(IUser::class)->getMock();
        $this->user->method('getUID')->willReturn('userid');

        $this->userSession = $this->getMockBuilder(IUserSession::class)->disableOriginalConstructor()->getMock();
        $this->userSession->method('getUser')->willReturn($this->user);

        $this->mapper = $this->getMockBuilder(HashMapper::class)->disableOriginalConstructor()->getMock();

        $this->logger = $this->getMockBuilder(ILogger::class)->getMock();

        $this->file = $this->getMockBuilder('OCP\Files\File')->disableOriginalConstructor()->getMock();
        $this->file->method('getId')->willReturn(1);
        $this->file->method('getMTime')->willReturn(222222222);
        $this->file->method('getInternalPath')->willReturn('/data/test.txt');
    }

    protected function tearDown() :void {
        parent::tearDown();
    }

    public function testChecksumArgumentPathIsNull() :void {
	$expected_stauts = Http::STATUS_BAD_REQUEST;
        $expected_data = 'query parameter path is missing';

        $rootFolder = $this->getMockBuilder('OCP\Files\IRootFolder')->getMock();

        $controller = new ChecksumAPIController('checksum-api',
            $this->request,
            $rootFolder,
            $this->userSession,
            $this->mapper,
            $this->logger
        );

        $response = $controller->checksum(null, null);
        $this->assertEquals($expected_stauts, $response->getStatus());
        $this->assertEquals($expected_data, $response->getData());
    }

    public function testChecksumArgumentPathIsInvalid() :void {
        $path = '/aaa';
	$expected_stauts = Http::STATUS_NOT_FOUND;
        $expected_data = 'file not found at specified path: ' . $path;

        $userFolder = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder->method('get')->will($this->throwException(new NotFoundException()));

        $rootFolder = $this->getMockBuilder('OCP\Files\IRootFolder')->getMock();
        $rootFolder->method('getUserFolder')->with('userid')->willReturn($userFolder);

        $controller = new ChecksumAPIController('checksum-api',
            $this->request,
            $rootFolder,
            $this->userSession,
            $this->mapper,
            $this->logger
        );

        $response = $controller->checksum($path, null);
        $this->assertEquals($expected_stauts, $response->getStatus());
        $this->assertEquals($expected_data, $response->getData());
    }

    public function testChecksumArgumentRevisionIsInvalid() :void {
        $path = '/test';
        $revision = 'aaaa';
	$expected_stauts = Http::STATUS_BAD_REQUEST;
        $expected_data = 'invalid revision is specified';

        $userFolder = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder->method('get')->willReturn($this->file);

        $rootFolder = $this->getMockBuilder('OCP\Files\IRootFolder')->getMock();
        $rootFolder->method('getUserFolder')->with('userid')->willReturn($userFolder);

        $controller = new ChecksumAPIController('checksum-api',
            $this->request,
            $rootFolder,
            $this->userSession,
            $this->mapper,
            $this->logger
        );

        $response = $controller->checksum($path, $revision);
        $this->assertEquals($expected_stauts, $response->getStatus());
        $this->assertEquals($expected_data, $response->getData());
    }

    public function testChecksumVersionAppIsDisabled() :void {
        $path = '/test';
        $revision = '1000000000';
	$expected_stauts = Http::STATUS_NOT_IMPLEMENTED;
        $expected_data = 'version function is not enabled';

        $userFolder = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder->method('get')->willReturn($this->file);

        $rootFolder = $this->getMockBuilder('OCP\Files\IRootFolder')->getMock();
        $rootFolder->method('getUserFolder')->with('userid')->willReturn($userFolder);

        $controller = new ChecksumAPIController('checksum-api',
            $this->request,
            $rootFolder,
            $this->userSession,
            $this->mapper,
            $this->logger
        );

        $status = \OCP\App::isEnabled($this->versionAppId);
        if ($status) {
            \OC::$server->getAppManager()->disableApp($this->versionAppId);
        }
        $response = $controller->checksum($path, $revision);
        $this->assertEquals($expected_stauts, $response->getStatus());
        $this->assertEquals($expected_data, $response->getData());
        if ($status) {
            \OC::$server->getAppManager()->enableApp($this->versionAppId);
        }
    }

    public function testChecksumMatchesNoVersion() :void {
        $path = '/test';
        $revision = '1000000000';
	$expected_stauts = Http::STATUS_NOT_FOUND;
        $expected_data = 'specified revision is not found';

        $userFolder = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder->method('get')->willReturn($this->file);

        $rootFolder = $this->getMockBuilder('OCP\Files\IRootFolder')->getMock();
        $rootFolder->method('getUserFolder')->with('userid')->willReturn($userFolder);

        $controller = new ChecksumAPIController('checksum-api',
            $this->request,
            $rootFolder,
            $this->userSession,
            $this->mapper,
            $this->logger
        );

        $status = \OCP\App::isEnabled($this->versionAppId);
        if (!$status) {
            \OC::$server->getAppManager()->disableApp($this->versionAppId);
        }
        $response = $controller->checksum($path, $revision);
        $this->assertEquals($expected_stauts, $response->getStatus());
        $this->assertEquals($expected_data, $response->getData());
        if (!$status) {
            \OC::$server->getAppManager()->enableApp($this->versionAppId);
        }
    }

    public function testChecksumSucceedWithoutRevision() :void {
        $path = '/test.txt';
        $revision = null;
	$expected_stauts = Http::STATUS_OK;
        $expected_data = ['hash' => '44bd27c4fe929be2c4749aadb803c1103eb5b693571d6d73dbc4056d8e18309f88c617c4f5b0f625bfd1d91929cac19bab90c0afbe4042c81132afec6d8b5fa8'];

        $storage = $this->getMockBuilder('OCP\Files\Storage')->disableOriginalConstructor()->getMock();
        $storage->method('getLocalFile')->willReturn(__DIR__ . '/../data/test.txt');

        $userFolder = $this->getMockBuilder('OCP\Files\Folder')->getMock();
        $userFolder->method('get')->willReturn($this->file);
        $userFolder->method('getStorage')->willReturn($storage);

        $rootFolder = $this->getMockBuilder('OCP\Files\IRootFolder')->getMock();
        $rootFolder->method('getUserFolder')->with('userid')->willReturn($userFolder);

        $queryBuilder = $this->getMockBuilder(IQueryBuilder::class)->getMock();

        $controller = new ChecksumAPIController('checksum-api',
            $this->request,
            $rootFolder,
            $this->userSession,
            $this->mapper,
            $this->logger
        );

        $status = \OCP\App::isEnabled($this->versionAppId);
        if (!$status) {
            \OC::$server->getAppManager()->disableApp($this->versionAppId);
        }
        $response = $controller->checksum($path, $revision);
        $this->assertEquals($expected_stauts, $response->getStatus());
        $this->assertEquals($expected_data, $response->getData());
        if (!$status) {
            \OC::$server->getAppManager()->enableApp($this->versionAppId);
        }
    }
}
