<?php

namespace Kapersoft\ShareFile\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

/**
 * Class BadRequest.
 *
 * @author   Jan Willem Kaper <kapersoft@gmail.com>
 * @license  MIT (see License.txt)
 *
 * @link     http://github.com/kapersoft/sharefile-api
 */
class BadRequest extends Exception
{
    /**
     * The http error code supplied in the response.
     *
     * @var int|null
     */
    public $httpCode;

    /**
     * The error code supplied in the response.
     *
     * @var string|null
     */
    public $code;

    /**
     * The error message supplied in the response.
     *
     * @var string|null
     */
    public $message;

    /**
     * BadRequest constructor.
     *
     * @param ResponseInterface $response Guzzle response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->httpCode = $response->getStatusCode();

        $body = json_decode($response->getBody(), true);

        if (isset($body['error'])) {
            $this->message = $body['error'];
        }

        if (isset($body['error_description'])) {
            $this->message = $body['error_description'];
        }

        if (isset($body['message']['value'])) {
            $this->message = $body['message']['value'];
        }

        if (isset($body['code'])) {
            $this->code = $body['code'];
        }

        parent::__construct('['.$this->code.'] '.$this->message, $this->httpCode);
    }
}
