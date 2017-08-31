<?php

namespace Kapersoft\Sharefile\Test;

use org\bovigo\vfs\vfsStream;
use Kapersoft\Sharefile\Client;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStreamFile;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Kapersoft\Sharefile\Exceptions\BadRequest;

/**
 * Class TestClient.
 *
 * @category GitHub_Repositories
 *
 * @author   Jan Willem Kaper <kapersoft@gmail.com>
 * @license  MIT (see License.txt)
 *
 * @link     http://github.com/kapersoft/sharefile-api
 */
class TestShareFileApi extends TestCase
{
    /**
     * Mock Guzzle client.
     *
     * @var \Kapersoft\ShareFile\Client
     */
    protected $client;

    /**
     * Virtual FS root.
     *
     * @var \org\bovigo\vfs\vfsStream
     * */
    private $vfsRoot;

    /**
     * Setup Test.
     *
     * @return void
     */
    public function setUp()
    {
        $this->vfsRoot = vfsStream::setup('home');
    }

    /**
     * Test for it_can_be_instantiated.
     *
     * @test
     *
     * @return void
     */
    public function it_can_be_instantiated() // @codingStandardsIgnoreLine
    {
        $this->checkCredentials();

        $client = new Client(
            HOSTNAME,
            CLIENT_ID,
            CLIENT_SECRET,
            USERNAME,
            PASSWORD
        );

        $this->assertInstanceOf(Client::class, $client);
    }

    /**
     * Test for it_can_throw_exception_using_wrong_hostname.
     *
     * @test
     *
     * @return void
     */
    public function it_can_throw_exception_using_wrong_hostname() // @codingStandardsIgnoreLine
    {
        $this->checkCredentials();

        $this->expectException(ConnectException::class);

        $client = new Client(
            'error_hostname',
            'error_client_id',
            'error_secret',
            'error_username',
            'error_password'
        );
    }

    /**
     * Test for it_can_throw_exception_using_wrong_api_details.
     *
     * @test
     *
     * @return void
     */
    public function it_can_throw_exception_using_wrong_api_details() // @codingStandardsIgnoreLine
    {
        $this->checkCredentials();

        $this->expectException(ClientException::class);

        $client = new Client(
            HOSTNAME,
            CLIENT_ID,
            '',
            USERNAME,
            PASSWORD
        );
    }

    /**
     * Test for it_can_throw_exception_using_wrong_password.
     *
     * @test
     *
     * @return void
     */
    public function it_can_throw_exception_using_wrong_password() // @codingStandardsIgnoreLine
    {
        $this->checkCredentials();

        $this->expectException(ClientException::class);

        $client = new Client(
            HOSTNAME,
            CLIENT_ID,
            CLIENT_SECRET,
            USERNAME,
            ''
        );
    }

    /**
     * Test for it_can_throw_a_badrequest_exception.
     *
     * @test
     *
     * @return void
     */
    public function it_can_throw_a_badrequest_exception() // @codingStandardsIgnoreLine
    {
        $this->expectException(BadRequest::class);

        $this->getClient()->getItemById('error');
    }

    /**
     * Test for it_can_get_user.
     *
     * @test
     *
     * @return void
     */
    public function it_can_get_user() // @codingStandardsIgnoreLine
    {
        $response = $this->getClient()->getUser();
        // print_r($response);

        $this->assertEquals('ShareFile.Api.Models.AccountUser', $response['odata.type']);
        $this->assertArrayHasKey('Email', $response);
    }

    /**
     * Test for it_can_get_item_without_children.
     *
     * @test
     *
     * @return void
     */
    public function it_can_get_item_without_children() // @codingStandardsIgnoreLine
    {
        $itemId = '';
        if ($this->mEmpty($itemId)) {
            $this->markTestSkipped('Fill in $itemId to complete this test.');
        }

        $response = $this->getClient()->getItemById($itemId, false);
        // print_r($response);

        $this->assertEquals('ShareFile.Api.Models.Folder', $response['odata.type']);
        $this->assertEquals($itemId, $response['Id']);
        $this->assertArrayNotHasKey('Children', $response);
    }

    /**
     * Test for it_can_get_item_with_children.
     *
     * @test
     *
     * @return void
     */
    public function it_can_get_item_with_children() // @codingStandardsIgnoreLine
    {
        $itemId = '';
        if ($this->mEmpty($itemId)) {
            $this->markTestSkipped('Fill in $itemId to complete this test.');
        }

        $response = $this->getClient()->getItemById($itemId, true);
        // print_r($response);

        $this->assertEquals('ShareFile.Api.Models.Folder', $response['odata.type']);
        $this->assertEquals($itemId, $response['Id']);
        $this->assertArrayHasKey('Children', $response);
    }

    /**
     * Test for it_can_get_item_breadcrumps.
     *
     * @test
     *
     * @return void
     */
    public function it_can_get_item_breadcrumps() // @codingStandardsIgnoreLineomposccomcfccfffffffffffffffffffdfdfdffdffddf
    {
        $itemId = '';
        if ($this->mEmpty($itemId)) {
            $this->markTestSkipped('Fill in $itemId to complete this test.');
        }

        $response = $this->getClient()->getItemBreadCrumps($itemId);
        // print_r($response);

        $this->assertGreaterThan(0, $response['odata.count']);
        $this->assertArrayHasKey('value', $response);
    }

    /**
     * Test for it_can_copy_an_item.
     *
     * @test
     *
     * @return void
     */
    public function it_can_copy_an_item() // @codingStandardsIgnoreLine
    {
        $filePath = '';
        $targetFolderPath = '';
        if ($this->mEmpty($filePath, $targetFolderPath)) {
            $this->markTestSkipped('Fill in $filePath and $targetFolderPath to complete this test.');
        }

        $file = $this->getClient()->getItemByPath('');
        $targetFolder = $this->getClient()->getItemByPath('');
        $response = $this->getClient()->copyItem($targetFolder['Id'], $file['Id']);

        $this->assertEquals($file['Hash'], $response['Hash']);
        $this->assertEquals($file['FileName'], $response['FileName']);
        $this->assertEquals($file['FileSizeBytes'], $response['FileSizeBytes']);
        $this->assertEquals($file['odata.type'], $response['odata.type']);
    }

    /**
     * Test for it_can_get_item_by_path.
     *
     * @test
     *
     * @return void
     */
    public function it_can_get_item_by_path() // @codingStandardsIgnoreLine
    {
        $itemPath = '';
        if ($this->mEmpty($itemPath)) {
            $this->markTestSkipped('Fill in $itemPath to complete this test.');
        }

        $response = $this->getClient()->getItemByPath($itemPath);
        // print_r($response);

        $this->assertEquals('ShareFile.Api.Models.File', $response['odata.type']);
    }

    /**
     * Test for it_can_create_folder.
     *
     * @test
     *
     * @return void
     */
    public function it_can_create_folder() // @codingStandardsIgnoreLine
    {
        $parentId = '';
        $name = '';
        $description = '';
        if ($this->mEmpty($parentId, $name, $description)) {
            $this->markTestSkipped('Fill in $parentId, $name and $description to complete this test.');
        }

        $response = $this->getClient()->createFolder($parentId, $name, $description);
        // print_r($response);

        $this->assertEquals('ShareFile.Api.Models.Folder', $response['odata.type']);
        $this->assertEquals($name, $response['FileName']);
        $this->assertEquals($description, $response['Description']);
    }

    /**
     * Test for it_can_create_and_overwrite_a_folder.
     *
     * @test
     *
     * @return void
     */
    public function it_can_create_and_overwrite_a_folder() // @codingStandardsIgnoreLine
    {
        $parentId = '';
        $name = '';
        $description = '';
        if ($this->mEmpty($parentId, $name, $description)) {
            $this->markTestSkipped('Fill in $parentId, $name and $description to complete this test.');
        }

        $response = $this->getClient()->createFolder($parentId, 'My Folder', 'My Description', true);
        // print_r($response);

        $this->assertEquals('ShareFile.Api.Models.Folder', $response['odata.type']);
        $this->assertEquals($name, $response['FileName']);
        $this->assertEquals($description, $response['Description']);
    }

    /**
     * Test for it_can_delete_an_item.
     *
     * @test
     *
     * @return void
     */
    public function it_can_delete_an_item() // @codingStandardsIgnoreLine
    {
        $itemId = '';
        if ($this->mEmpty($itemId)) {
            $this->markTestSkipped('Fill in $itemId to complete this test.');
        }

        $response = $this->getClient()->deleteItem($itemId);
        // print_r($response);

        $this->assertEquals([], $response);
    }

    /**
     * Test for it_can_get_download_url_of_an_item.
     *
     * @test
     *
     * @return void
     */
    public function it_can_get_download_url_of_an_item() // @codingStandardsIgnoreLine
    {
        $itemId = '';
        if ($this->mEmpty($itemId)) {
            $this->markTestSkipped('Fill in $itemId to complete this test.');
        }

        $response = $this->getClient()->getItemDownloadUrl($itemId);
        // print_r($response);

        $this->assertEquals('ShareFile.Api.Models.DownloadSpecification', $response['odata.type']);
        $this->assertTrue(
            filter_var($response['DownloadUrl'], FILTER_VALIDATE_URL) !== false,
            'Response is not a valid URL.'
        );
    }

    /**
     * Test for it_can_get_contents_of_an_item.
     *
     * @test
     *
     * @return void
     */
    public function it_can_get_contents_of_an_item() // @codingStandardsIgnoreLine
    {
        $itemId = '';
        if ($this->mEmpty($itemId)) {
            $this->markTestSkipped('Fill in $itemId to complete this test.');
        }

        $response = $this->getClient()->getItemContents($itemId);
        // print_r($response);

        $this->assertNotEmpty($response);
    }

    /**
     * Test for it_can_get_chunk_uri.
     *
     * @test
     *
     * @return void
     */
    public function it_can_get_chunk_uri() // @codingStandardsIgnoreLine
    {
        $method = '';
        $filename = '';
        $folderId = '';
        if ($this->mEmpty($method, $filename, $folderId)) {
            $this->markTestSkipped('Fill in $method, $filename and $folderId to complete this test.');
        }

        $response = $this->getClient()->getChunkUri($method, $filename, $folderId);
        // print_r($response);

        $this->assertEquals('ShareFile.Api.Models.UploadSpecification', $response['odata.type']);
        $this->assertEquals(strtolower($method), strtolower($response['Method']));
        $this->assertTrue(
            filter_var($response['ChunkUri'], FILTER_VALIDATE_URL) !== false,
            'Response is not a valid URL.'
        );
    }

    /**
     * Test for it_can_upload_an_item_using_http_post.
     *
     * @test
     *
     * @return void
     */
    public function it_can_upload_an_item_using_http_post() // @codingStandardsIgnoreLine
    {
        $filename = '';
        $folderId = '';
        if ($this->mEmpty($filename, $folderId)) {
            $this->markTestSkipped('Fill in $filename and $folderId to complete this test.');
        }

        $response = $this->getClient()->uploadFileStandard($filename, $folderId);
        // print_r($response);

        $this->assertEquals('OK', $response);
    }

    /**
     * Test for it_can_update_an_item.
     *
     * @test
     *
     * @return void
     */
    public function it_can_update_an_item() // @codingStandardsIgnoreLine
    {
        $itemId = '';
        $newFileName = '';
        $newName = '';
        if ($this->mEmpty($itemId, $newFileName, $newName)) {
            $this->markTestSkipped('Fill in $itemId, $newFileName and $newName to complete this test.');
        }

        $data = [
            'FileName' => $newFileName,
            'Name'     => $newFileName,
        ];
        $response = $this->getCLient()->updateItem($itemId, $data);
        // print_r($response);

        $this->assertEquals($newName, $response['Name']);
        $this->assertEquals($newFileName, $response['FileName']);
    }

    /**
     * Test for it_can_download_a_thumbnail.
     *
     * @test
     *
     * @return void
     */
    public function it_can_download_a_thumbnail() // @codingStandardsIgnoreLine
    {
        $itemId = '';
        if ($this->mEmpty($itemId)) {
            $this->markTestSkipped('Fill in $itemId to complete this test.');
        }

        $response = $this->getCLient()->getThumbnailUrl($itemId);
        // print_r($response);

        $this->assertEquals('ShareFile.Api.Models.Redirection', $response['odata.type']);
        $this->assertTrue(
            filter_var($response['Uri'], FILTER_VALIDATE_URL) !== false,
            'Response is not a valid URL.'
        );
    }

    /**
     * Test for it_can_get_a_web_app_link.
     *
     * @test
     *
     * @return void
     */
    public function it_can_get_a_web_app_link() // @codingStandardsIgnoreLine
    {
        $itemId = '';
        if ($this->mEmpty($itemId)) {
            $this->markTestSkipped('Fill in $itemId to complete this test.');
        }

        $response = $this->getCLient()->getWebAppLink($itemId);
        // print_r($response);

        $this->assertEquals('ShareFile.Api.Models.Redirection', $response['odata.type']);
        $this->assertTrue(
            filter_var($response['Uri'], FILTER_VALIDATE_URL) !== false,
            'Response is not a valid URL.'
        );
    }

    /**
     * Test for it_can_create_shares.
     *
     * @test
     *
     * @return void
     */
    public function it_can_create_shares() // @codingStandardsIgnoreLine
    {
        $shareType = '';
        $title = '';
        $itemId = '';
        if ($this->mEmpty($itemId, $title, $itemId)) {
            $this->markTestSkipped('Fill in $shareType, $title and $itemId to complete this test.');
        }

        $options = [
            'ShareType' => $shareType,
            'Title'     => $title,
            'Items'     => [
                [
                    'Id' => $itemId,
                ],
            ],
        ];
        $response = $this->getClient()->createShare($options, false);
        // print_r($response);

        $this->assertEquals('ShareFile.Api.Models.Share', $response['odata.type']);
        $this->assertEquals($title, $response['Title']);
        $this->assertEquals($shareType, $response['ShareType']);
        $this->assertTrue(
            filter_var($response['Uri'], FILTER_VALIDATE_URL) !== false,
            'Response is not a valid URL.'
        );
    }

    /**
     * Test for it_can_create_read_update_and_delete.
     *
     * @test
     *
     * @return void
     */
    public function it_can_create_read_update_and_delete() // @codingStandardsIgnoreLine
    {
        $rootFolderId = '';
        if ($this->mEmpty($rootFolderId)) {
            $this->markTestSkipped('Fill in $rootFolderId to complete this test.');
        }

        // Get RootFolder
        $rootFolder = $this->getClient()->getItemById($rootFolderId);

        // Get RootFolder Breadcrumps
        $rootBreadcrumps = $this->getClient()->getItemBreadcrumps($rootFolderId);

        // Calculate root path
        $rootPath = $rootBreadcrumps['value'];
        $rootPath = array_slice($rootPath, 1, count($rootPath) - 1, true);
        $rootPath = array_map(
            function ($item) {
                return $item['FileName'];
            },
            $rootPath
        );
        $rootPath = '/'.implode('/', $rootPath).'/'.$rootFolder['FileName'];

        // Create Folder
        $newFolder = $this->getClient()->createFolder($rootFolderId, 'ShareFileApiTest', 'Folder for testing ShareFile Api', true);

        // Create File in Folder
        $vfsFile = $this->createMockImage('MyPicture.jpg');
        $this->getClient()->uploadFileStandard($vfsFile->url(), $newFolder['Id'], false, true, true);

        // Get File Details using Path
        $newFile = $this->getClient()->getItemByPath($rootPath.'/'.$newFolder['FileName'].'/MyPicture.jpg');

        // Create subfolder
        $subfolder = $this->getClient()->createFolder($newFolder['Id'], 'Subfolder', 'Subfolder for testing ShareFile Api', true);

        // Copy File to subfolder
        $copiedItem = $this->getClient()->copyItem($subfolder['Id'], $newFile['Id'], true);

        // Rename file
        $itemData = [
           'Name'     => 'MyOtherPicture.jpg',
           'FileName' => 'MyOtherPicture.jpg',
        ];
        $renamedFile = $this->getClient()->updateItem($copiedItem['Id'], $itemData, true, true);

        // Move file to $newFolder
        $itemData = [
           'Parent' => [
               'Id' => $newFolder['Id'],
           ],
        ];
        $movedFile = $this->getClient()->updateItem($renamedFile['Id'], $itemData, true, true);

        // Get File Contents
        $movedFileDownloadUrl = $this->getClient()->getItemDownloadUrl($movedFile['Id'], false);

        // Get File Contents
        $movedFileContents = $this->getClient()->getItemContents($copiedItem['Id'], false);

        // Get Item Thumbnail
        $movedFileThumbnail = $this->getClient()->getThumbnailUrl($movedFile['Id']);

        // Delete all test files and folders
        $this->getClient()->deleteItem($newFolder['Id']);
    }

    /**
     * Check ShareFile credentials in config file.
     *
     * @return void
     */
    protected function checkCredentials()
    {
        if ($this->mEmpty(HOSTNAME, CLIENT_ID, CLIENT_SECRET, USERNAME, PASSWORD)) {
            $this->markTestSkipped('No ShareFile credentials are found. Fill in your ShareFile credentials under section <PHP> in the file phpunit.xml.dist in the project root folder.');
        }
    }

    /**
     * Get Client.
     *
     * @return Client
     */
    protected function getClient():Client
    {
        $this->checkCredentials();

        if ($this->client == null) {
            $this->client = new Client(
                HOSTNAME,
                CLIENT_ID,
                CLIENT_SECRET,
                USERNAME,
                PASSWORD
            );
        }

        return $this->client;
    }

    /**
     * Check if one ore more variables is empty.
     *
     * @param array ...$args
     *
     * @return bool
     */
    protected function mEmpty(...$args): bool
    {
        $arguments = func_get_args();
        foreach ($arguments as $argument) {
            if (empty($argument)) {
                return true;
            } else {
                continue;
            }
        }

        return false;
    }

    /**
     * @param string $filename Filename
     * @param string $contents Contents
     *
     * @return vfsStreamFile
     */
    protected function createMockFile(string $filename, string $contents = ''):vfsStreamFile
    {
        return vfsStream::newFile($filename)->at($this->vfsRoot)->withContent($contents);
    }

    /**
     * @param string $filename Filename
     *
     * @return vfsStreamFile
     */
    protected function createMockImage(string $filename):vfsStreamFile
    {
        // Create mock image
        $mockImage = imagecreatetruecolor(300, 100);
        $text_color = imagecolorallocate($mockImage, 233, 14, 91);
        imagestring($mockImage, 16, 50, 20, 'ShareFile Api Test', $text_color);

        // Create mock file and save image
        $vfsFile = $this->createMockFile($filename);
        imagejpeg($mockImage, $vfsFile->url());
        imagedestroy($mockImage);

        return $vfsFile;
    }
}
