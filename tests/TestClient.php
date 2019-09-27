<?php

namespace Kapersoft\Sharefile\Test;

use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use org\bovigo\vfs\vfsStream;
use Kapersoft\ShareFile\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Exception\ClientException;
use org\bovigo\vfs\content\LargeFileContent;
use PHPUnit\Framework\TestCase;

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
class TestClient extends TestCase
{
    /**
     * MockClient history container.
     *
     * @var array
     */
    protected $container;

    /**
     * Virtual FS root.
     *
     * @var \org\bovigo\vfs\vfsStream
     * */
    protected $vfsRoot;

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
        $mockHandler = new MockHandler(
            [new Response(200, [], json_encode(['access_token' => 'my_access_code', 'subdomain' => 'subdomain', 'expires' => time() + 60]))]
        );

        $client = new Client(
            'hostname',
            'client_id',
            'secret',
            'username',
            'password',
            $mockHandler
        );

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals('my_access_code', $client->getAccessToken()->getToken());
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
        $expectedResponse = ['odata.type' => 'ShareFile.Api.Models.AccountUser', 'Email' => 'user@company.com'];
        $mockClient = $this->getMockClient($expectedResponse);

        $response = $mockClient->getUser();

        $this->assertSame('GET', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://subdomain.sf-api.com/sf/v3/Users()', (string) $this->getLastRequest()->getUri());
        $this->assertSame($response, $expectedResponse);
    }

    /**
     * Test for it_can_create_folder_and_overwrite.
     *
     * @test
     *
     * @return void
     */
    public function it_can_create_folder_and_overwrite() // @codingStandardsIgnoreLine
    {
        $expectedResponse = ['odata.type' => 'ShareFile.Api.Models.Folder', 'FileName' => 'My Folder', 'Description' => 'My Description'];
        $mockClient = $this->getMockClient($expectedResponse);

        $response = $mockClient->createFolder(Client::FOLDER_HOME, 'My Folder', 'My Description', true);

        $this->assertSame('POST', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://subdomain.sf-api.com/sf/v3/Items(home)/Folder?overwrite=true&passthrough=false', (string) $this->getLastRequest()->getUri());
        $this->assertSame(json_encode(['name' => 'My Folder', 'description' => 'My Description']), (string) $this->getLastRequest()->getBody());
        $this->assertSame($response, $expectedResponse);
    }

    /**
     * Test for it_can_create_folder_not_overwrite.
     *
     * @test
     *
     * @return void
     */
    public function it_can_create_folder_not_overwrite() // @codingStandardsIgnoreLine
    {
        $expectedResponse = ['odata.type' => 'ShareFile.Api.Models.Folder', 'FileName' => 'My Folder', 'Description' => 'My Description'];
        $mockClient = $this->getMockClient($expectedResponse);

        $response = $mockClient->createFolder(Client::FOLDER_HOME, 'My Folder', 'My Description', false);

        $this->assertSame('POST', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://subdomain.sf-api.com/sf/v3/Items(home)/Folder?overwrite=false&passthrough=false', (string) $this->getLastRequest()->getUri());
        $this->assertSame(json_encode(['name' => 'My Folder', 'description' => 'My Description']), (string) $this->getLastRequest()->getBody());
        $this->assertSame($response, $expectedResponse);
    }

    /**
     * Test for it_can_get_an_item_without_children.
     *
     * @test
     *
     * @return void
     */
    public function it_can_get_an_item_without_children() // @codingStandardsIgnoreLine
    {
        $expectedResponse = ['odata.type' => 'ShareFile.Api.Models.Folder', 'Id' => 'top'];
        $mockClient = $this->getMockClient($expectedResponse);

        $response = $mockClient->getItemById(Client::FOLDER_TOP, false);

        $this->assertSame('GET', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://subdomain.sf-api.com/sf/v3/Items(top)', (string) $this->getLastRequest()->getUri());
        $this->assertSame($response, $expectedResponse);
    }

    /**
     * Test for it_can_get_an_item_with_children.
     *
     * @test
     *
     * @return void
     */
    public function it_can_get_an_item_with_children() // @codingStandardsIgnoreLine
    {
        $expectedResponse = ['odata.type' => 'odata.metadata', 'odata.count' => '2', 'value' => []];
        $mockClient = $this->getMockClient($expectedResponse);

        $response = $mockClient->getItemBreadcrumbs(Client::FOLDER_HOME);

        $this->assertSame('GET', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://subdomain.sf-api.com/sf/v3/Items(home)/Breadcrumbs', (string) $this->getLastRequest()->getUri());
        $this->assertSame($response, $expectedResponse);
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
        $expectedResponse = ['odata.type' => 'ShareFile.Api.Models.File', 'FileName' => 'picture.jpg'];
        $mockClient = $this->getMockClient($expectedResponse);

        $response = $mockClient->getItemByPath('/Personal Folders/picture.jpg');

        $this->assertSame('GET', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://subdomain.sf-api.com/sf/v3/Items/ByPath?Path=/Personal%20Folders/picture.jpg', (string) $this->getLastRequest()->getUri());
        $this->assertSame($response, $expectedResponse);
    }

    /**
     * Test for it_can_get_item_breadcrumbs.
     *
     * @test
     *
     * @return void
     */
    public function it_can_get_item_breadcrumbs() // @codingStandardsIgnoreLine
    {
        $expectedResponse = ['odata.type' => 'ShareFile.Api.Models.Folder', 'Id' => 'top', 'Children' => ''];
        $mockClient = $this->getMockClient($expectedResponse);

        $response = $mockClient->getItemById(Client::FOLDER_TOP, true);

        $this->assertSame('GET', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://subdomain.sf-api.com/sf/v3/Items(top)?$expand=Children', (string) $this->getLastRequest()->getUri());
        $this->assertSame($response, $expectedResponse);
    }

    /**
     * Test for it_can_copy_and_not_overwrite.
     *
     * @test
     *
     * @return void
     */
    public function it_can_copy_and_not_overwrite() // @codingStandardsIgnoreLine
    {
        $expectedResponse = ['odata.type' => 'ShareFile.Api.Models.File', 'Id' => 'file_id'];
        $mockClient = $this->getMockClient($expectedResponse);

        $response = $mockClient->copyItem('target_id', 'file_id');

        $this->assertSame('POST', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://subdomain.sf-api.com/sf/v3/Items(file_id)/Copy?targetid=target_id&overwrite=false', (string) $this->getLastRequest()->getUri());
        $this->assertSame($response, $expectedResponse);
    }

    /**
     * Test for it_can_copy_and_overwrite.
     *
     * @test
     *
     * @return void
     */
    public function it_can_copy_and_overwrite() // @codingStandardsIgnoreLine
    {
        $expectedResponse = ['odata.type' => 'ShareFile.Api.Models.File', 'Id' => 'file_id'];
        $mockClient = $this->getMockClient($expectedResponse);

        $response = $mockClient->copyItem('target_id', 'file_id', true);

        $this->assertSame('POST', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://subdomain.sf-api.com/sf/v3/Items(file_id)/Copy?targetid=target_id&overwrite=true', (string) $this->getLastRequest()->getUri());
        $this->assertSame($response, $expectedResponse);
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
        $expectedResponse = ['Name' => 'picture.jpg', 'Description' => 'Best selfie ever!', 'FileName' => 'picture.jpg'];
        $mockClient = $this->getMockClient($expectedResponse);

        $data = ['Name' => 'picture.jpg', 'Description' => 'Best selfie ever!', 'FileName' => 'picture.jpg'];
        $response = $mockClient->updateItem('item_id', $data);

        $this->assertSame('PATCH', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://subdomain.sf-api.com/sf/v3/Items(item_id)?forceSync=true&notify=true', (string) $this->getLastRequest()->getUri());
        $this->assertSame($expectedResponse, $response);
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
        $expectedResponse = '';
        $mockClient = $this->getMockClient($expectedResponse);

        $response = $mockClient->deleteItem('folder_id');

        $this->assertSame('DELETE', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://subdomain.sf-api.com/sf/v3/Items(folder_id)?singleversion=false&forceSync=false', (string) $this->getLastRequest()->getUri());
        $this->assertSame($response, $expectedResponse);
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
        $expectedResponse = ['DownloadUrl' => 'https://storage-eu-208.sharefile.com/download.ashx?dt=my_download_key'];
        $mockClient = $this->getMockClient($expectedResponse);

        $response = $mockClient->getItemDownloadUrl('file_id');

        $this->assertSame('GET', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://subdomain.sf-api.com/sf/v3/Items(file_id)/Download?includeallversions=false&redirect=false', (string) $this->getLastRequest()->getUri());
        $this->assertSame($expectedResponse, $response);
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
        $expectedResponse = 'My Item Contents';
        $mockClient = $this->getMockClient($expectedResponse);

        $response = $mockClient->getItemContents('file_id');

        $this->assertSame('GET', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://subdomain.sf-api.com/sf/v3/Items(file_id)/Download?includeallversions=false&redirect=true', (string) $this->getLastRequest()->getUri());
        $this->assertSame($expectedResponse, $response);
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
        $vfsFile = $this->createMockFile('textfile.txt', 'The contents of the file');
        $expectedResponse = ['ChunkUri' => 'https://storage-eu-202.sharefile.com/upload.aspx?uploadid=my_upload_id'];
        $mockClient = $this->getMockClient($expectedResponse);

        $response = $mockClient->getChunkUri('standard', $vfsFile->url(), 'folder_id');

        $this->assertSame('POST', (string) $this->getLastRequest()->getMethod());
        $expectedUrl = 'https://subdomain.sf-api.com/sf/v3/Items(folder_id)/Upload?method=standard&raw=false&fileName=textfile.txt&fileSize=24&canResume=false&startOver=false&unzip=false&tool=apiv3&overwrite=true&title=textfile.txt&isSend=false&responseFormat=json&notify=true&clientCreatedDateUTC='.filectime($vfsFile->url()).'&clientModifiedDateUTC='.filemtime($vfsFile->url());
        $this->assertSame($expectedUrl, (string) $this->getLastRequest()->getUri());
        $this->assertSame($expectedResponse, $response);
    }

    /**
     * Test for it_can_upload_an_item_using_http_post.
     *
     * @test
     *
     * @return void
     */
    public function it_can_upload_an_item_using_single_http_post() // @codingStandardsIgnoreLine
    {
        $vfsFile = $this->createMockFile('textfile.txt', 'The contents of the file');

        // Create response
        $expectedResponse = 'OK';
        $mockResponse = [
            new Response(200, [], json_encode(['access_token' => 'access_code', 'subdomain' => 'subdomain', 'expires' => time() + 60])),
            new Response(200, [], json_encode(['ChunkUri' => 'https://storage-eu-202.sharefile.com/upload.aspx?uploadid=my_upload_id'])),
            new Response(200, [], $expectedResponse),
        ];

        // Create mockHandler
        $this->container = [];
        $history = Middleware::history($this->container);
        $mockHandler = HandlerStack::create(new MockHandler($mockResponse));
        $mockHandler->push($history);

        // Create Client with mockHandler
        $mockClient = new Client(
            'hostname',
            'client_id',
            'secret',
            'username',
            'password',
            $mockHandler
        );

        $response = $mockClient->uploadFileStandard($vfsFile->url(), 'folder_id');

        $this->assertSame('POST', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://storage-eu-202.sharefile.com/upload.aspx?uploadid=my_upload_id', (string) $this->getLastRequest()->getUri());
        $this->assertSame($expectedResponse, $response);
    }

    /**
     * Test for it_can_upload_an_item_using_http_post.
     *
     * @test
     *
     * @return void
     */
    public function it_can_upload_an_item_using_multiple_http_posts() // @codingStandardsIgnoreLine
    {
        $vfsFile = $this->createMockFile('textfile.txt', LargeFileContent::withKilobytes(10));

        // Create response
        $expectedResponse = 'fo66e8f5-3aa3-405b-8129-f9a749dd4e99';
        $mockResponse = [
            new Response(200, [], json_encode(['access_token' => 'access_code', 'subdomain' => 'subdomain', 'expires' => time() + 60])),
            new Response(200, [], json_encode(['ChunkUri' => 'https://storage-eu-202.sharefile.com/upload.aspx?uploadid=my_upload_id'])),
            new Response(200, [], 'true'),
            new Response(200, [], $expectedResponse),
        ];

        // Create mockHandler
        $this->container = [];
        $history = Middleware::history($this->container);
        $mockHandler = HandlerStack::create(new MockHandler($mockResponse));
        $mockHandler->push($history);

        // Create Client with mockHandler
        $mockClient = new Client(
            'hostname',
            'client_id',
            'secret',
            'username',
            'password',
            $mockHandler
        );

        $handle = fopen($vfsFile->url(), 'r');
        $response = $mockClient->uploadFileStreamed($handle, 'folder_id', 'large_file.txt', false, true, true, 8192);

        $this->assertSame('POST', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://storage-eu-202.sharefile.com/upload.aspx?uploadid=my_upload_id&index=1&byteOffset=8192&hash=9598acee9824e6a39d1eda8024fd0846&filehash=5795fa7c504e4b99a01644a300e74c66&finish=true', (string) $this->getLastRequest()->getUri());
        $this->assertSame($expectedResponse, $response);
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
        $expectedResponse = ['Uri' => 'https://thumbnail-eu.sharefile.com/thumbnail.aspx?encparams=my_encparams'];
        $mockClient = $this->getMockClient($expectedResponse);

        $response = $mockClient->getThumbnailUrl('item_id');

        $this->assertSame('GET', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://subdomain.sf-api.com/sf/v3/Items(item_id)/Thumbnail?size=75&redirect=false', (string) $this->getLastRequest()->getUri());
        $this->assertSame($expectedResponse, $response);
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
        $expectedResponse = ['Uri' => ' https://subdomain.sharefile.com?encparams=my_encparams'];
        $mockClient = $this->getMockClient($expectedResponse);

        $response = $mockClient->getWebAppLink('item_id');

        $this->assertSame('POST', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://subdomain.sf-api.com/sf/v3/Items(item_id)/WebAppLink', (string) $this->getLastRequest()->getUri());
        $this->assertSame($expectedResponse, $response);
    }

    /**
     * Test for it_can_create_a_share.
     *
     * @test
     *
     * @return void
     */
    public function it_can_create_a_share() // @codingStandardsIgnoreLine
    {
        $expectedResponse = ['Uri' => 'https://subdomain.sharefile.com/d-shareId'];
        $mockClient = $this->getMockClient($expectedResponse);

        $options = ['ShareType' => 'Send', 'Title' => 'My Title', 'Items' => [['Id' => 'item_id']]];
        $response = $mockClient->createShare($options);

        $this->assertSame('POST', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://subdomain.sf-api.com/sf/v3/Shares?notify=false&direct=true', (string) $this->getLastRequest()->getUri());
        $this->assertSame($expectedResponse, $response);
    }

    /**
     * Test for it_can_get_access_control_with_user_id.
     *
     * @test
     *
     * @return void
     */
    public function it_can_get_access_control_with_user_id() // @codingStandardsIgnoreLine
    {
        $expectedResponse = ['odata.type' => 'ShareFile.Api.Models.File', 'Id' => 'principalid=userId,itemid=itemId'];

        $mockClient = $this->getMockClient($expectedResponse);

        $response = $mockClient->getItemAccessControls('itemId', 'userId');

        $this->assertSame('GET', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://subdomain.sf-api.com/sf/v3/AccessControls(principalid=userId,itemid=itemId)', (string) $this->getLastRequest()->getUri());
        $this->assertSame($expectedResponse, $response);
    }

    /**
     * Test for it_can_get_access_control_with_user_id.
     *
     * @test
     *
     * @return void
     */
    public function it_can_get_access_control_without_user_id() // @codingStandardsIgnoreLine
    {
        $expectedResponse = [
            'odata.cound' => 1,
            'value' => [
                [
                    'Id' => 'principalid=userId,itemid=itemId',
                ],
            ],
        ];

        $mockClient = $this->getMockClient($expectedResponse);

        $response = $mockClient->getItemAccessControls('itemId');

        $this->assertSame('GET', (string) $this->getLastRequest()->getMethod());
        $this->assertSame('https://subdomain.sf-api.com/sf/v3/Items(itemId)/AccessControls', (string) $this->getLastRequest()->getUri());
        $this->assertSame($expectedResponse, $response);
    }

    /**
     * Create a mock file.
     *
     * @param string $filename Filename
     * @param mixed  $contents Contents (optional)
     *
     * @return \org\bovigo\vfs\vfsStreamFile
     */
    private function createMockFile(string $filename, $contents = '')
    {
        return vfsStream::newFile($filename)->at($this->vfsRoot)->withContent($contents);
    }

    /**
     * Get mock Guzzle client.
     *
     * @param $responseBody
     * @param array $responseHeaders
     *
     * @return Client
     */
    private function getMockClient($responseBody, array $responseHeaders = []): Client
    {
        // Create mockResponse
        if (is_array($responseBody)) {
            $responseBody = json_encode($responseBody);
        }

        $mockResponse = [
            new Response(200, [], json_encode([
                'access_token' => 'access_code',
                'subdomain' => 'subdomain',
                'expires' => time() + 60,
                'refresh_token' => 'refresh_code',
                'token_type' => 'bearer',
                'appcp' => 'sharefile.com'
            ])),
            new Response(200, $responseHeaders, $responseBody),
        ];

        // Create mockHandler with history container
        $this->container = [];
        $history = Middleware::history($this->container);
        $mockHandler = HandlerStack::create(new MockHandler($mockResponse));
        $mockHandler->push($history);

        // Return Client with mockHandler
        return new Client(
            'hostname',
            'client_id',
            'secret',
            'username',
            'password',
            $mockHandler
        );
    }

    /**
     * Get Last request from mock Guzzle client.
     *
     * @return Request
     */
    private function getLastRequest(): Request
    {
        return end($this->container)['request'];
    }
}
