<?php
declare(strict_types=1);

namespace Kapersoft\ShareFile;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Exception\ClientException;
use Kapersoft\ShareFile\Exceptions\BadRequest;
use Slacker775\OAuth2\Client\Provider\ShareFile as AuthProvider;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use OAuth\Client\TokenStorage\TokenStorageInterface;
use OAuth\Client\Exception\TokenNotFoundException;

/**
 * Class Client.
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
     * @var array
     */
    public $token;

    /**
     *
     * @var AbstractProvider
     */
    protected $authProvider;

    /**
     *
     * @var AccessToken
     */
    protected $accessToken;

    /**
     *
     * @var TokenStorageInterface
     */
    protected $tokenRepository;

    /**
     *
     * @var array
     */
    protected $options;

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

    /*
    * Default Chunk Size for uploading files
    */
    const DEFAULT_CHUNK_SIZE = 8 * 1024 * 1024;

    // 8 megabytes

    /**
     * Client constructor.
     *
     * @param string $hostname
     *            ShareFile hostname
     * @param string $client_id
     *            OAuth2 client_id
     * @param string $client_secret
     *            OAuth2 client_secret
     * @param string $username
     *            ShareFile username
     * @param string $password
     *            ShareFile password
     * @param MockHandler|HandlerStack $handler
     *            Guzzle Handler
     *
     * @throws Exception
     */
    public function __construct(string $hostname, string $client_id, string $client_secret, string $username, string $password, $handler = null, TokenStorageInterface $tokenRepository = null)
    {
        $this->tokenRepository = $tokenRepository;

        $client = new HttpClient([
            'handler' => $handler
        ]);

        $this->authProvider = new AuthProvider([
            'clientId' => $client_id,
            'clientSecret' => $client_secret
        ], [
            'httpClient' => $client
        ]);

        $this->options = [
            'username' => $username,
            'password' => $password,
            'baseUrl' => $hostname
        ];
    }

    /**
     * Get user details.
     *
     * @param string $userId ShareFile user id (optional)
     *
     * @return array
     */
    public function getUser(string $userId = '') : array
    {
        return $this->get("Users({$userId})");
    }

    public function updateUser(string $userId, array $data) : array
    {
        return $this->patch("Users({$userId})", $data);
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
    public function createFolder(string $parentId, string $name, string $description = '', bool $overwrite = false) : array
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
    public function getItemById(string $itemId, bool $getChildren = false) : array
    {
        $parameters = $getChildren === true ? '$expand=Children' : '';

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
    public function getItemByPath(string $path, string $itemId = '') : array
    {
        if (empty($itemId)) {
            return $this->get("Items/ByPath?Path={$path}");
        } else {
            return $this->get("Items({$itemId})/ByPath?Path={$path}");
        }
    }

    /**
     * Get breadcrumbs of an item.
     *
     * @param string $itemId Item Id
     *
     * @return array
     */
    public function getItemBreadcrumbs(string $itemId) : array
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
    public function copyItem(string $targetId, string $itemId, bool $overwrite = false) : array
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
    public function updateItem(string $itemId, array $data, bool $forceSync = true, bool $notify = true) : array
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
     * @return string
     */
    public function deleteItem(string $itemId, bool $singleversion = false, bool $forceSync = false) : string
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
     * @return mixed
     */
    public function getItemContents(string $itemId, bool $includeallversions = false)
    {
        $parameters = $this->buildHttpQuery(
            [
                'includeallversions' => $includeallversions,
                'redirect'           => true,
            ]
        );

        return $this->get("Items({$itemId})/Download?{$parameters}");
    }

    /**
     * Get the Chunk Uri to start a file-upload.
     *
     * @param string   $method    Upload method (Standard or Streamed)
     * @param string   $filename  Name of file
     * @param string   $folderId  Id of the parent folder
     * @param bool     $unzip     Indicates that the upload is a Zip file, and contents must be extracted at the end of upload. The resulting files and directories will be placed in the target folder. If set to false, the ZIP file is uploaded as a single file. Default is false (optional)
     * @param bool     $overwrite Indicates whether items with the same name will be overwritten or not (optional)
     * @param bool     $notify    Indicates whether users will be notified of this upload - based on folder preferences (optional)
     * @param bool     $raw       Send contents contents directly in the POST body (=true) or send contents in MIME format (=false) (optional)
     * @param resource $stream    Resource stream of the contents (optional)
     *
     * @return array
     */
    public function getChunkUri(string $method, string $filename, string $folderId, bool $unzip = false, $overwrite = true, bool $notify = true, bool $raw = false, $stream = null):array
    {
        $parameters = $this->buildHttpQuery(
            [
                'method'                => $method,
                'raw'                   => $raw,
                'fileName'              => basename($filename),
                'fileSize'              => $stream == null ? filesize($filename) : fstat($stream)['size'],
                'canResume'             => false,
                'startOver'             => false,
                'unzip'                 => $unzip,
                'tool'                  => 'apiv3',
                'overwrite'             => $overwrite,
                'title'                 => basename($filename),
                'isSend'                => false,
                'responseFormat'        => 'json',
                'notify'                => $notify,
                'clientCreatedDateUTC'  => $stream == null ? filectime($filename) : fstat($stream)['ctime'],
                'clientModifiedDateUTC' => $stream == null ? filemtime($filename) : fstat($stream)['mtime'],
            ]
        );

        return $this->post("Items({$folderId})/Upload?{$parameters}");
    }

    /**
     * Upload a file using a single HTTP POST.
     *
     * @param string $filename  Name of file
     * @param string $folderId  Id of the parent folder
     * @param bool   $unzip     Indicates that the upload is a Zip file, and contents must be extracted at the end of upload. The resulting files and directories will be placed in the target folder. If set to false, the ZIP file is uploaded as a single file. Default is false (optional)
     * @param bool   $overwrite Indicates whether items with the same name will be overwritten or not (optional)
     * @param bool   $notify    Indicates whether users will be notified of this upload - based on folder preferences (optional)
     *
     * @return string
     */
    public function uploadFileStandard(string $filename, string $folderId, bool $unzip = false, bool $overwrite = true, bool $notify = true):string
    {
        $chunkUri = $this->getChunkUri('standard', $filename, $folderId, $unzip, $overwrite, $notify);

        $request = $this->authProvider->getAuthenticatedRequest(
            'POST',
            $chunkUri['ChunkUri'],
            $this->accessToken,
            [
                'multipart' => [
                    [
                        'name'     => 'File1',
                        'contents' => fopen($filename, 'r'),
                    ],
                ],
            ]
        );
        $response = $this->authProvider->getResponse($request);

        return (string) $response->getBody();
    }

    /**
     * Upload a file using multiple HTTP POSTs.
     *
     * @param mixed    $stream    Stream resource
     * @param string   $folderId  Id of the parent folder
     * @param string   $filename  Filename (optional)
     * @param bool     $unzip     Indicates that the upload is a Zip file, and contents must be extracted at the end of upload. The resulting files and directories will be placed in the target folder. If set to false, the ZIP file is uploaded as a single file. Default is false (optional)
     * @param bool     $overwrite Indicates whether items with the same name will be overwritten or not (optional)
     * @param bool     $notify    Indicates whether users will be notified of this upload - based on folder preferences (optional)
     * @param int      $chunkSize Maximum size of the individual HTTP posts in bytes
     *
     * @return string
     */
    public function uploadFileStreamed($stream, string $folderId, string $filename = null, bool $unzip = false, bool $overwrite = true, bool $notify = true, int $chunkSize = null):string
    {
        $filename = $filename ?? stream_get_meta_data($stream)['uri'];
        if (empty($filename)) {
            return 'Error: no filename';
        }

        $chunkUri = $this->getChunkUri('streamed', $filename, $folderId, $unzip, $overwrite, $notify, true, $stream);
        $chunkSize = $chunkSize ?? SELF::DEFAULT_CHUNK_SIZE;
        $index = 0;

        // First Chunk
        $data = $this->readChunk($stream, $chunkSize);
        while (! ((strlen($data) < $chunkSize) || feof($stream))) {
            $parameters = $this->buildHttpQuery(
                [
                    'index'      => $index,
                    'byteOffset' => $index * $chunkSize,
                    'hash'       => md5($data),
                ]
            );

            $response = $this->uploadChunk("{$chunkUri['ChunkUri']}&{$parameters}", $data);

            if ($response != 'true') {
                return $response;
            }

            // Next chunk
            $index++;
            $data = $this->readChunk($stream, $chunkSize);
        }

        // Final chunk
        $parameters = $this->buildHttpQuery(
            [
                'index'      => $index,
                'byteOffset' => $index * $chunkSize,
                'hash'       => md5($data),
                'filehash'   => \GuzzleHttp\Psr7\hash(\GuzzleHttp\Psr7\stream_for($stream), 'md5'),
                'finish'    => true,
            ]
        );

        return $this->uploadChunk("{$chunkUri['ChunkUri']}&{$parameters}", $data);
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
     * Get AccessControl List for an item.
     *
     * @param string $itemId Id of an item
     * @param string $userId Id of an user
     *
     * @return array
     */
    public function getItemAccessControls(string $itemId, string $userId = ''):array
    {
        if (! empty($userId)) {
            return $this->get("AccessControls(principalid={$userId},itemid={$itemId})");
        } else {
            return $this->get("Items({$itemId})/AccessControls");
        }
    }

    public function getAccessToken(): AccessToken
    {
        $tokenId = sprintf('sf-%s', $this->options['username']);

        if ($this->accessToken === null) {
            if ($this->tokenRepository !== null) {
                try {
                $this->accessToken = $this->tokenRepository->loadToken($tokenId);
                } catch(TokenNotFoundException $e) {}
            }

            if ($this->accessToken === null) {
                $this->accessToken = $this->authProvider->getAccessToken('password', [
                    'username' => $this->options['username'],
                    'password' => $this->options['password'],
                    'baseUrl' => $this->options['baseUrl']
                ]);

                if ($this->tokenRepository !== null) {
                    $this->tokenRepository->storeToken($this->accessToken, $tokenId);
                }
            }
        }

        if ($this->accessToken->hasExpired() === true) {
            $this->accessToken = $this->authProvider->getAccessToken('refresh_token', [
                'refresh_token' => $this->accessToken->getRefreshToken()
            ]);
            if ($this->tokenRepository !== null) {
                $this->tokenRepository->storeAccessToken($tokenId, $this->accessToken);
            }
        }
        return $this->accessToken;
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
        return  "https://{$this->accessToken->getValues()['subdomain']}.sf-api.com/sf/v3/{$endpoint}";
    }

    /**
     * Make a request to the API.
     *
     * @param string             $method   HTTP Method
     * @param string             $endpoint API endpoint
     * @param mixed|string|array $json     POST body (optional)
     *
     * @throws Exception
     *
     * @return mixed
     */
    protected function request(string $method, string $endpoint, $json = null)
    {
        $accessToken = $this->getAccessToken();

        $uri = $this->buildUri($endpoint);
        $options = $json != null ? ['json' => $json] : [];

        try {
            $request = $this->authProvider->getAuthenticatedRequest($method, $uri, $accessToken, $options);
            $response = $this->authProvider->getResponse($request);
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
     * @return mixed
     */
    protected function get(string $endpoint)
    {
        return $this->request('GET', $endpoint);
    }

    /**
     * Shorthand for POST-request.
     *
     * @param string             $endpoint API endpoint
     * @param mixed|string|array $json     POST body (optional)
     *
     * @return mixed
     */
    protected function post(string $endpoint, $json = null)
    {
        return $this->request('POST', $endpoint, $json);
    }

    /**
     * Shorthand for PATCH-request.
     *
     * @param string             $endpoint API endpoint
     * @param mixed|string|array $json     POST body (optional)
     *
     * @return mixed
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
     * @return string|array
     */
    protected function delete(string $endpoint)
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Upload a chunk of data using HTTP POST body.
     *
     * @param string $uri  Upload URI
     * @param string $data Contents to upload
     *
     * @return string|array
     */
    protected function uploadChunk($uri, $data)
    {
        $request = $this->authProvider->getAuthenticatedRequest(
            'POST',
            $uri,
            $this->accessToken,
            [
                'headers' => [
                    'Content-Length' => strlen($data),
                    'Content-Type'   => 'application/octet-stream',
                ],
                'body' => $data,
            ]
        );
        $response = $this->authProvider->getResponse($request);

        return (string) $response->getBody();
    }

    /**
     * Sometimes fread() returns less than the request number of bytes (for example, when reading
     * from network streams).  This function repeatedly calls fread until the requested number of
     * bytes have been read or we've reached EOF.
     *
     * @param resource $stream
     * @param int      $chunkSize
     *
     * @throws Exception
     * @return string
     */
    protected function readChunk($stream, int $chunkSize)
    {
        $chunk = '';
        while (! feof($stream) && $chunkSize > 0) {
            $part = fread($stream, $chunkSize);
            if ($part === false) {
                throw new Exception('Error reading from $stream.');
            }
            $chunk .= $part;
            $chunkSize -= strlen($part);
        }

        return $chunk;
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
        if (in_array($exception->getResponse()->getStatusCode(), [400, 403, 404, 409])) {
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
     * @param mixed $data JSON variable
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
