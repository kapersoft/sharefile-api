<?php

namespace Kapersoft\ShareFile;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Kapersoft\Sharefile\Exceptions\BadRequest;

/**
 * Class Client.
 *
 * @category GitHub_Repositories
 *
 * @author   Jan Willem Kaper <kapersoft@gmail.com>
 * @license  MIT (see License.txt)
 *
 * @link     http://github.com/kapersoft/sharefile-api
 */
class Client
{
    /**
     * ShareFile token.
     *
     * @var string
     */
    public $token;

    /**
     * Guzzle Client.
     *
     * @var \GuzzleHttp\Client
     */
    public $client;

    /**
     * Thumbnail size.
     */
    const THUMBNAIL_SIZE_M = 75;
    const THUMBNAIL_SIZE_L = 600;

    /*
     * ShareFile Folder
     */
    const FOLDER_TOP = 'top';
    const FOLDER_HOME = 'home';
    const FOLDER_FAVORITES = 'favorites';
    const FOLDER_ALLSHARED = 'allshared';

    /**
     * Client constructor.
     *
     * @param string $hostname      ShareFile hostname
     * @param string $client_id     OAuth2 client_id
     * @param string $client_secret OAuth2 client_secret
     * @param string $username      ShareFile username
     * @param string $password      ShareFile password
     * @param null   $handler       Guzzle Handler
     *
     * @throws Exception
     */
    public function __construct(string $hostname, string $client_id, string $client_secret, string $username, string $password, $handler = null)
    {
        $response = $this->authenticate($hostname, $client_id, $client_secret, $username, $password, $handler);

        if (! isset($response['access_token']) || ! isset($response['subdomain'])) {
            throw new Exception("Incorrect response from Authentication: 'access_token' or 'subdomain' is missing.");
        }

        $this->token = $response;
        $this->client = new GuzzleClient(
            [
                'handler' => $handler,
                'headers' => [
                    'Authorization' => "Bearer {$this->token['access_token']}",
                ],
            ]
        );
    }

    /**
     * ShareFile authentication using username/password.
     *
     * @param string $hostname      ShareFile hostname
     * @param string $client_id     OAuth2 client_id
     * @param string $client_secret OAuth2 client_secret
     * @param string $username      ShareFile username
     * @param string $password      ShareFile password
     * @param null   $handler       Guzzle Handler
     *
     * @throws Exception
     *
     * @return array
     */
    protected function authenticate(string $hostname, string $client_id, string $client_secret, string $username, string $password, $handler = null):array
    {
        $uri = "https://{$hostname}/oauth/token";

        $parameters = [
            'grant_type'    => 'password',
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'username'      => $username,
            'password'      => $password,
        ];

        try {
            $client = new GuzzleClient(['handler' => $handler]);
            $response = $client->post(
                $uri,
                ['form_params' => $parameters]
            );
        } catch (ClientException $exception) {
            throw $exception;
        }

        if ($response->getStatusCode() == '200') {
            return json_decode($response->getBody(), true);
        } else {
            throw new Exception('Authentication error', $response->getStatusCode());
        }
    }

    /**
     * Get user details.
     *
     * @param string $userId ShareFile user id (optional)
     *
     * @return array
     */
    public function getUser(string $userId = ''):array
    {
        return $this->get("Users({$userId})");
    }

    /**
     * Create a folder.
     *
     * @param string $parentId    Id of the parent folder
     * @param string $name        Name
     * @param string $description Description
     * @param bool   $overwrite   Overwrite folder
     *
     * @return array
     */
    public function createFolder(string $parentId, string $name, string $description = '', bool $overwrite = false):array
    {
        $parameters = $this->buildHttpQuery(
            [
                'overwrite'   => $overwrite,
                'passthrough' => false,
            ]
        );

        $data = [
            'name'        => $name,
            'description' => $description,
        ];

        return $this->post("Items({$parentId})/Folder?{$parameters}", $data);
    }

    /**
     * Get Folder/File using Id.
     *
     * @param string $itemId      Item id
     * @param bool   $getChildren Include children
     *
     * @return array
     */
    public function getItemById(string $itemId, bool $getChildren = false):array
    {
        $parameters = $getChildren == true ? '$expand=Children' : '';

        return $this->get("Items({$itemId})?{$parameters}");
    }

    /**
     * Get Folder/File using path.
     *
     * @param string $path   Path
     * @param string $itemId Id of the root folder (optional)
     *
     * @return array
     */
    public function getItemByPath(string $path, string $itemId = ''):array
    {
        if (empty($itemId)) {
            return $this->get("Items/ByPath?Path={$path}");
        } else {
            return $this->get("Items($itemId)/ByPath?Path={$path}");
        }
    }

    /**
     * Get breadcrumps of an item.
     *
     * @param string $itemId Item Id
     *
     * @return array
     */
    public function getItemBreadcrumps(string $itemId):array
    {
        return $this->get("Items({$itemId})/Breadcrumbs");
    }

    /**
     * Copy an item.
     *
     * @param string $targetId  Id of the target folder
     * @param string $itemId    Id of the copied item
     * @param bool   $overwrite Indicates whether items with the same name will be overwritten or not (optional)
     *
     * @return array
     */
    public function copyItem(string $targetId, string $itemId, bool $overwrite = false):array
    {
        $parameters = $this->buildHttpQuery(
            [
                'targetid'  => $targetId,
                'overwrite' => $overwrite,
            ]
        );

        return $this->post("Items({$itemId})/Copy?{$parameters}");
    }

    /**
     * Update an item.
     *
     * @param string $itemId    Id of the item
     * @param array  $data      New data
     * @param bool   $forceSync Indicates whether operation is to be executed synchronously (optional)
     * @param bool   $notify    Indicates whether an email should be sent to users subscribed to Upload Notifications (optional)
     *
     * @return array
     */
    public function updateItem(string $itemId, array $data, bool $forceSync = true, bool $notify = true):array
    {
        $parameters = $this->buildHttpQuery(
            [
                'forceSync' => $forceSync,
                'notify'    => $notify,
            ]
        );

        return $this->patch("Items({$itemId})?{$parameters}", $data);
    }

    /**
     * Delete an item.
     *
     * @param string $itemId        Item id
     * @param bool   $singleversion True it will delete only the specified version rather than all sibling files with the same filename (optional)
     * @param bool   $forceSync     True will block the operation from taking place asynchronously (optional)
     *
     * @return array
     */
    public function deleteItem(string $itemId, bool $singleversion = false, bool $forceSync = false):array
    {
        $parameters = $this->buildHttpQuery(
            [
                'singleversion' => $singleversion,
                'forceSync'     => $forceSync,
            ]
        );

        return $this->delete("Items({$itemId})?{$parameters}");
    }

    /**
     * Get temporary download URL for an item.
     *
     * @param string $itemId             Item id
     * @param bool   $includeallversions For folder downloads only, includes old versions of files in the folder in the zip when true, current versions only when false (default)
     *
     * @return array
     */
    public function getItemDownloadUrl(string $itemId, bool $includeallversions = false):array
    {
        $parameters = $this->buildHttpQuery(
            [
                'includeallversions' => $includeallversions,
                'redirect'           => false,
            ]
        );

        return $this->get("Items({$itemId})/Download?{$parameters}");
    }

    /**
     * Get contents of and item.
     *
     * @param string $itemId             Item id
     * @param bool   $includeallversions $includeallversions For folder downloads only, includes old versions of files in the folder in the zip when true, current versions only when false (default)
     *
     * @return mixed|string
     */
    public function getItemContents(string $itemId, bool $includeallversions = false)
    {
        $parameters = $this->buildHttpQuery(
            [
                'includeallversions' => $includeallversions,
                'redirect'           => 'true',
            ]
        );

        return $this->get("Items({$itemId})/Download?{$parameters}");
    }

    /**
     * Get the Chunk Uri to start a file-upload.
     *
     * @param string $method    Upload method (Standard or Streamed)
     * @param string $filename  Name of file
     * @param string $folderId  Id of the parent folder
     * @param bool   $unzip     Inidicates that the upload is a Zip file, and contents must be extracted at the end of upload. The resulting files and directories will be placed in the target folder. If set to false, the ZIP file is uploaded as a single file. Default is false (optional)
     * @param bool   $overwrite Indicates whether items with the same name will be overwritten or not (optional)
     * @param bool   $notify    Indicates whether users will be notified of this upload - based on folder preferences (optional)
     *
     * @return array
     */
    public function getChunkUri(string $method, string $filename, string $folderId, bool $unzip = false, $overwrite = true, bool $notify = true):array
    {
        $parameters = $this->buildHttpQuery(
            [
                'method'                => $method,
                'raw'                   => false,
                'fileName'              => basename($filename),
                'fileSize'              => filesize($filename),
                'canResume'             => false,
                'startOver'             => false,
                'unzip'                 => $unzip,
                'tool'                  => 'apiv3',
                'overwrite'             => $overwrite,
                'title'                 => basename($filename),
                'isSend'                => false,
                'responseFormat'        => 'json',
                'notify'                => $notify,
                'clientCreatedDateUTC'  => filectime($filename),
                'clientModifiedDateUTC' => filemtime($filename),
            ]
        );

        return $this->post("Items({$folderId})/Upload?{$parameters}");
    }

    /**
     * Upload a file using a single HTTP POST.
     *
     * @param string $filename  Name of file
     * @param string $folderId  Id of the parent folder
     * @param bool   $unzip     Inidicates that the upload is a Zip file, and contents must be extracted at the end of upload. The resulting files and directories will be placed in the target folder. If set to false, the ZIP file is uploaded as a single file. Default is false (optional)
     * @param bool   $overwrite Indicates whether items with the same name will be overwritten or not (optional)
     * @param bool   $notify    Indicates whether users will be notified of this upload - based on folder preferences (optional)
     *
     * @return string
     */
    public function uploadFileStandard(string $filename, string $folderId, bool $unzip = false, bool $overwrite = true, bool $notify = true):string
    {
        $chunkUri = $this->getChunkUri('standard', $filename, $folderId, $unzip, $overwrite, $notify);

        $response = $this->client->request(
            'POST',
            $chunkUri['ChunkUri'],
            [
                'multipart' => [
                    [
                        'name'     => 'File1',
                        'contents' => fopen($filename, 'r'),
                    ],
                ],
            ]
        );

        return (string) $response->getBody();
    }

    /**
     * Get Thumbnail of an item.
     *
     * @param string $itemId Item id
     * @param int    $size   Thumbnail size: THUMBNAIL_SIZE_M or THUMBNAIL_SIZE_L (optional)
     *
     * @return array
     */
    public function getThumbnailUrl(string $itemId, int $size = 75):array
    {
        $parameters = $this->buildHttpQuery(
            [
                'size'     => $size,
                'redirect' => false,
            ]
        );

        return $this->get("Items({$itemId})/Thumbnail?{$parameters}");
    }

    /**
     * Get browser link for an item.
     *
     * @param string $itemId Item id
     *
     * @return array
     */
    public function getWebAppLink(string $itemId):array
    {
        return $this->post("Items({$itemId})/WebAppLink");
    }

    /**
     * Share Share for external user.
     *
     * @param array $options Share options
     * @param bool  $notify  Indicates whether user will be notified if item is downloaded (optional)
     *
     * @return array
     */
    public function createShare(array $options, $notify = false):array
    {
        $parameters = $this->buildHttpQuery(
            [
                'notify' => $notify,
                'direct' => true,
            ]
        );

        return $this->post("Shares?{$parameters}", $options);
    }

    /**
     * Build API uri.
     *
     * @param string $endpoint API endpoint
     *
     * @return string
     */
    protected function buildUri(string $endpoint): string
    {
        return  "https://{$this->token['subdomain']}.sf-api.com/sf/v3/{$endpoint}";
    }

    /**
     * Make a request to the API.
     *
     * @param string $method   HTTP Method
     * @param string $endpoint API endpoint
     * @param null   $json     POST body (optional)
     *
     * @throws Exception
     *
     * @return mixed|string|array
     */
    protected function request(string $method, string $endpoint, $json = null)
    {
        $uri = $this->buildUri($endpoint);
        $options = $json != null ? ['json' => $json] : [];

        try {
            $response = $this->client->request($method, $uri, $options);
        } catch (ClientException $exception) {
            throw $this->determineException($exception);
        }

        $body = (string) $response->getBody();

        return $this->jsonValidator($body) ? json_decode($body, true) : $body;
    }

    /**
     * Shorthand for GET-request.
     *
     * @param string $endpoint API endpoint
     *
     * @return mixed|string|array
     */
    protected function get(string $endpoint)
    {
        return $this->request('GET', $endpoint);
    }

    /**
     * Shorthand for POST-request.
     *
     * @param string $endpoint API endpoint
     * @param null   $json     POST body (optional)
     *
     * @return mixed|string|array
     */
    protected function post(string $endpoint, $json = null)
    {
        return $this->request('POST', $endpoint, $json);
    }

    /**
     * Shorthand for PATCH-request.
     *
     * @param string $endpoint API endpoint
     * @param null   $json     POST body (optional)
     *
     * @return mixed|string|array
     */
    protected function patch(string $endpoint, $json = null)
    {
        return $this->request('PATCH', $endpoint, $json);
    }

    /**
     * Shorthand for DELETE-request.
     *
     * @param string $endpoint API endpoint
     *
     * @return mixed|string|array
     */
    protected function delete(string $endpoint)
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Handle ClientException.
     *
     * @param ClientException $exception ClientException
     *
     * @return Exception
     */
    protected function determineException(ClientException $exception): Exception
    {
        if (in_array($exception->getResponse()->getStatusCode(), [400, 409, 404])) {
            return new BadRequest($exception->getResponse());
        }

        return $exception;
    }

    /**
     * Build HTTP query.
     *
     * @param array $parameters Query parameters
     *
     * @return string
     */
    protected function buildHttpQuery(array $parameters):string
    {
        return http_build_query(
            array_map(
                function ($parameter) {
                    if (! is_bool($parameter)) {
                        return $parameter;
                    }

                    return $parameter ? 'true' : 'false';
                },
                $parameters
            )
        );
    }

    /**
     * Validate JSON.
     *
     * @param null $data JSON variable
     *
     * @return bool
     */
    protected function jsonValidator($data = null):bool
    {
        if (! empty($data)) {
            @json_decode($data);

            return json_last_error() === JSON_ERROR_NONE;
        }

        return false;
    }
}
