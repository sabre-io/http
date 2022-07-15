<?php

declare(strict_types=1);

namespace Sabre\HTTP;

use Sabre\Event\EventEmitter;
use Sabre\Uri;

/**
 * A rudimentary HTTP client.
 *
 * This object wraps PHP's curl extension and provides an easy way to send it a
 * Request object, and return a Response object.
 *
 * This is by no means intended as the next best HTTP client, but it does the
 * job and provides a simple integration with the rest of sabre/http.
 *
 * This client emits the following events:
 *   beforeRequest(RequestInterface $request)
 *   afterRequest(RequestInterface $request, ResponseInterface $response)
 *   error(RequestInterface $request, ResponseInterface $response, bool &$retry, int $retryCount)
 *   exception(RequestInterface $request, ClientException $e, bool &$retry, int $retryCount)
 *
 * The beforeRequest event allows you to do some last minute changes to the
 * request before it's done, such as adding authentication headers.
 *
 * The afterRequest event will be emitted after the request is completed
 * successfully.
 *
 * If a HTTP error is returned (status code higher than 399) the error event is
 * triggered. It's possible using this event to retry the request, by setting
 * retry to true.
 *
 * The amount of times a request has retried is passed as $retryCount, which
 * can be used to avoid retrying indefinitely. The first time the event is
 * called, this will be 0.
 *
 * It's also possible to intercept specific http errors, by subscribing to for
 * example 'error:401'.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Client extends EventEmitter
{
    const STATUS_SUCCESS = 0;
    const STATUS_CURLERROR = 1;
    const STATUS_HTTPERROR = 2;

    /**
     * List of curl settings.
     *
     * @var array
     */
    protected $curlSettings = [
        CURLOPT_NOBODY => false,
        CURLOPT_USERAGENT => 'sabre-http/'.Version::VERSION.' (http://sabre.io/)',
    ];

    /**
     * Whether or not exceptions should be thrown when a HTTP error is returned.
     *
     * @var bool
     */
    protected $throwExceptions = false;

    /**
     * The maximum number of times we'll follow a redirect.
     *
     * @var int
     */
    protected $maxRedirects = 5;

    /**
     * The maximum size of in-memory cache per request. If this value is exceeded, the cache is transferred to a
     * temporary file.
     *
     * @var int
     */
    protected $maxMemorySize = 2 * 1024 * 1024;

    protected $headerLinesMap = [];

    protected $responseResourcesMap = [];

    /**
     * Cached curl handle.
     *
     * By keeping this resource around for the lifetime of this object, things
     * like persistent connections are possible.
     *
     * @var resource|\CurlHandle
     */
    private $curlHandle;

    /**
     * Handler for curl_multi requests.
     *
     * The first time sendAsync is used, this will be created.
     *
     * @var resource|\CurlMultiHandle
     */
    private $curlMultiHandle;

    /**
     * Has a list of curl handles, as well as their associated success and
     * error callbacks.
     *
     * @var array
     */
    private $curlMultiMap = [];

    /**
     * Reserved for backward compatibility.
     */
    public function __construct()
    {
    }

    protected function receiveCurlHeader($curlHandle, $headerLine): int
    {
        $this->headerLinesMap[(int) $curlHandle][] = $headerLine;

        return strlen($headerLine);
    }

    /**
     * Sends a request to a HTTP server, and returns a response.
     */
    public function send(RequestInterface $request): ResponseInterface
    {
        $this->emit('beforeRequest', [$request]);

        $retryCount = 0;
        $redirects = 0;

        do {
            $doRedirect = false;
            $retry = false;

            try {
                $response = $this->doRequest($request);

                $code = $response->getStatus();

                // We are doing in-PHP redirects, because curl's
                // FOLLOW_LOCATION throws errors when PHP is configured with
                // open_basedir.
                //
                // https://github.com/fruux/sabre-http/issues/12
                if ($redirects < $this->maxRedirects && in_array($code, [301, 302, 307, 308])) {
                    $oldLocation = $request->getUrl();

                    // Creating a new instance of the request object.
                    $request = clone $request;

                    // Setting the new location
                    $request->setUrl(Uri\resolve(
                        $oldLocation,
                        $response->getHeader('Location')
                    ));

                    $doRedirect = true;
                    ++$redirects;
                }

                // This was a HTTP error
                if ($code >= 400) {
                    $this->emit('error', [$request, $response, &$retry, $retryCount]);
                    $this->emit('error:'.$code, [$request, $response, &$retry, $retryCount]);
                }
            } catch (ClientException $e) {
                $this->emit('exception', [$request, $e, &$retry, $retryCount]);

                // If retry was still set to false, it means no event handler
                // dealt with the problem. In this case we just re-throw the
                // exception.
                if (!$retry) {
                    throw $e;
                }
            }

            if ($retry) {
                ++$retryCount;
            }
        } while ($retry || $doRedirect);

        $this->emit('afterRequest', [$request, $response]);

        if ($this->throwExceptions && $code >= 400) {
            throw new ClientHttpException($response);
        }

        return $response;
    }

    /**
     * Sends a HTTP request asynchronously.
     *
     * Due to the nature of PHP, you must from time to time poll to see if any
     * new responses came in.
     *
     * After calling sendAsync, you must therefore occasionally call the poll()
     * method, or wait().
     */
    public function sendAsync(RequestInterface $request, callable $success = null, callable $error = null)
    {
        $this->emit('beforeRequest', [$request]);
        $this->sendAsyncInternal($request, $success, $error);
        $this->poll();
    }

    /**
     * This method checks if any http requests have gotten results, and if so,
     * call the appropriate success or error handlers.
     *
     * This method will return true if there are still requests waiting to
     * return, and false if all the work is done.
     */
    public function poll(): bool
    {
        // nothing to do?
        if (!$this->curlMultiMap) {
            return false;
        }

        do {
            $r = curl_multi_exec($this->curlMultiHandle, $stillRunning);
        } while (CURLM_CALL_MULTI_PERFORM === $r);

        $messagesInQueue = 0;
        do {
            $status = curl_multi_info_read($this->curlMultiHandle, $messagesInQueue);

            if (false !== $status && CURLMSG_DONE === $status['msg']) {
                $curlHandle = $status['handle'];
                $handleId = (int) $curlHandle;
                [$request, $successCallback, $errorCallback, $retryCount] = $this->curlMultiMap[$handleId];

                $curlResult = $this->parseCurlResource($curlHandle);

                // Cleanup
                curl_multi_remove_handle($this->curlMultiHandle, $curlHandle);
                curl_close($curlHandle);
                unset(
                    $this->curlMultiMap[$handleId],
                    $this->responseResourcesMap[$handleId],
                    $this->headerLinesMap[$handleId]
                );

                $retry = false;

                if (self::STATUS_CURLERROR === $curlResult['status']) {
                    $e = new ClientException($curlResult['curl_errmsg'], $curlResult['curl_errno']);
                    $this->emit('exception', [$request, $e, &$retry, $retryCount]);

                    if ($retry) {
                        ++$retryCount;
                        $this->sendAsyncInternal($request, $successCallback, $errorCallback, $retryCount);
                        continue;
                    }

                    $curlResult['request'] = $request;

                    if ($errorCallback) {
                        $errorCallback($curlResult);
                    }
                } elseif (self::STATUS_HTTPERROR === $curlResult['status']) {
                    $this->emit('error', [$request, $curlResult['response'], &$retry, $retryCount]);
                    $this->emit('error:'.$curlResult['http_code'], [$request, $curlResult['response'], &$retry, $retryCount]);

                    if ($retry) {
                        ++$retryCount;
                        $this->sendAsyncInternal($request, $successCallback, $errorCallback, $retryCount);
                        continue;
                    }

                    $curlResult['request'] = $request;

                    if ($errorCallback) {
                        $errorCallback($curlResult);
                    }
                } else {
                    $this->emit('afterRequest', [$request, $curlResult['response']]);

                    if ($successCallback) {
                        $successCallback($curlResult['response']);
                    }
                }
            }
        } while ($messagesInQueue > 0);

        return count($this->curlMultiMap) > 0;
    }

    /**
     * Processes every HTTP request in the queue, and waits till they are all
     * completed.
     */
    public function wait()
    {
        do {
            curl_multi_select($this->curlMultiHandle);
            $stillRunning = $this->poll();
        } while ($stillRunning);
    }

    /**
     * If this is set to true, the Client will automatically throw exceptions
     * upon HTTP errors.
     *
     * This means that if a response came back with a status code greater than
     * or equal to 400, we will throw a ClientHttpException.
     *
     * This only works for the send() method. Throwing exceptions for
     * sendAsync() is not supported.
     */
    public function setThrowExceptions(bool $throwExceptions)
    {
        $this->throwExceptions = $throwExceptions;
    }

    /**
     * Adds a CURL setting.
     *
     * These settings will be included in every HTTP request.
     *
     * @param mixed $value
     */
    public function addCurlSetting(int $name, $value)
    {
        $this->curlSettings[$name] = $value;
    }

    /**
     * This method is responsible for performing a single request.
     */
    protected function doRequest(RequestInterface $request): ResponseInterface
    {
        if (!$this->curlHandle) {
            $this->curlHandle = curl_init();
        } else {
            curl_reset($this->curlHandle);
        }

        $this->prepareCurl($request, $this->curlHandle);
        $this->curlExecInternal($this->curlHandle);
        $response = $this->parseCurlResource($this->curlHandle);
        if (self::STATUS_CURLERROR === $response['status']) {
            throw new ClientException($response['curl_errmsg'], $response['curl_errno']);
        }

        return $response['response'];
    }

    /**
     * Turns a RequestInterface object into an array with settings that can be
     * fed to curl_setopt.
     */
    protected function createCurlSettingsArray(RequestInterface $request): array
    {
        $settings = $this->curlSettings;

        switch ($request->getMethod()) {
            case 'HEAD':
                $settings[CURLOPT_NOBODY] = true;
                $settings[CURLOPT_CUSTOMREQUEST] = 'HEAD';
                break;
            case 'GET':
                $settings[CURLOPT_CUSTOMREQUEST] = 'GET';
                break;
            default:
                $body = $request->getBody();
                if (is_resource($body)) {
                    $bodyStat = fstat($body);

                    // This needs to be set to PUT, regardless of the actual
                    // method used. Without it, INFILE will be ignored for some
                    // reason.
                    $settings[CURLOPT_PUT] = true;
                    $settings[CURLOPT_INFILE] = $body;
                    if (false !== $bodyStat && array_key_exists('size', $bodyStat)) {
                        $settings[CURLOPT_INFILESIZE] = $bodyStat['size'];
                    }
                } else {
                    // For security we cast this to a string. If somehow an array could
                    // be passed here, it would be possible for an attacker to use @ to
                    // post local files.
                    $settings[CURLOPT_POSTFIELDS] = (string) $body;
                }
                $settings[CURLOPT_CUSTOMREQUEST] = $request->getMethod();
                break;
        }

        $nHeaders = [];
        foreach ($request->getHeaders() as $key => $values) {
            foreach ($values as $value) {
                $nHeaders[] = $key.': '.$value;
            }
        }
        $settings[CURLOPT_HTTPHEADER] = $nHeaders;
        $settings[CURLOPT_URL] = $request->getUrl();
        // FIXME: CURLOPT_PROTOCOLS is currently unsupported by HHVM
        if (defined('CURLOPT_PROTOCOLS')) {
            $settings[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }
        // FIXME: CURLOPT_REDIR_PROTOCOLS is currently unsupported by HHVM
        if (defined('CURLOPT_REDIR_PROTOCOLS')) {
            $settings[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        return $settings;
    }

    /**
     * Parses the result of a curl call in a format that's a bit more
     * convenient to work with.
     *
     * The method returns an array with the following elements:
     *   * status - one of the 3 STATUS constants.
     *   * curl_errno - A curl error number. Only set if status is
     *                  STATUS_CURLERROR.
     *   * curl_errmsg - A current error message. Only set if status is
     *                   STATUS_CURLERROR.
     *   * response - Response object. Only set if status is STATUS_SUCCESS, or
     *                STATUS_HTTPERROR.
     *   * http_code - HTTP status code, as an int. Only set if Only set if
     *                 status is STATUS_SUCCESS, or STATUS_HTTPERROR
     *
     * @param resource|\CurlHandle $curlHandle
     */
    protected function parseCurlResource($curlHandle): array
    {
        [$curlInfo, $curlErrNo, $curlErrMsg] = $this->curlStuff($curlHandle);

        if ($curlErrNo) {
            return [
                'status' => self::STATUS_CURLERROR,
                'curl_errno' => $curlErrNo,
                'curl_errmsg' => $curlErrMsg,
            ];
        }
        $handleId = (int) $curlHandle;

        $response = new Response();
        $response->setStatus($curlInfo['http_code']);

        if (isset($this->responseResourcesMap[$handleId])) {
            rewind($this->responseResourcesMap[$handleId]);
            $response->setBody($this->responseResourcesMap[$handleId]);
        } else {
            $response->setBody('');
        }

        $headerLines = $this->headerLinesMap[$handleId] ?? [];
        foreach ($headerLines as $header) {
            $parts = explode(':', $header, 2);
            if (2 === count($parts)) {
                $response->addHeader(trim($parts[0]), trim($parts[1]));
            }
        }

        $httpCode = $response->getStatus();

        return [
            'status' => $httpCode >= 400 ? self::STATUS_HTTPERROR : self::STATUS_SUCCESS,
            'response' => $response,
            'http_code' => $httpCode,
        ];
    }

    /**
     * Parses the result of a curl call in a format that's a bit more
     * convenient to work with.
     *
     * The method returns an array with the following elements:
     *   * status - one of the 3 STATUS constants.
     *   * curl_errno - A curl error number. Only set if status is
     *                  STATUS_CURLERROR.
     *   * curl_errmsg - A current error message. Only set if status is
     *                   STATUS_CURLERROR.
     *   * response - Response object. Only set if status is STATUS_SUCCESS, or
     *                STATUS_HTTPERROR.
     *   * http_code - HTTP status code, as an int. Only set if Only set if
     *                 status is STATUS_SUCCESS, or STATUS_HTTPERROR
     *
     * @deprecated Use parseCurlResource instead
     *
     * @param resource|\CurlHandle $curlHandle
     */
    protected function parseCurlResponse(array $headerLines, string $body, $curlHandle): array
    {
        [$curlInfo, $curlErrNo, $curlErrMsg] = $this->curlStuff($curlHandle);

        if ($curlErrNo) {
            return [
                'status' => self::STATUS_CURLERROR,
                'curl_errno' => $curlErrNo,
                'curl_errmsg' => $curlErrMsg,
            ];
        }

        $response = new Response();
        $response->setStatus($curlInfo['http_code']);
        $response->setBody($body);

        foreach ($headerLines as $header) {
            $parts = explode(':', $header, 2);
            if (2 === count($parts)) {
                $response->addHeader(trim($parts[0]), trim($parts[1]));
            }
        }

        $httpCode = $response->getStatus();

        return [
            'status' => $httpCode >= 400 ? self::STATUS_HTTPERROR : self::STATUS_SUCCESS,
            'response' => $response,
            'http_code' => $httpCode,
        ];
    }

    /**
     * Parses the result of a curl call in a format that's a bit more
     * convenient to work with.
     *
     * The method returns an array with the following elements:
     *   * status - one of the 3 STATUS constants.
     *   * curl_errno - A curl error number. Only set if status is
     *                  STATUS_CURLERROR.
     *   * curl_errmsg - A current error message. Only set if status is
     *                   STATUS_CURLERROR.
     *   * response - Response object. Only set if status is STATUS_SUCCESS, or
     *                STATUS_HTTPERROR.
     *   * http_code - HTTP status code, as an int. Only set if Only set if
     *                 status is STATUS_SUCCESS, or STATUS_HTTPERROR
     *
     * @deprecated Use parseCurlResource instead
     *
     * @param resource|\CurlHandle $curlHandle
     */
    protected function parseCurlResult(string $response, $curlHandle): array
    {
        [$curlInfo, $curlErrNo, $curlErrMsg] = $this->curlStuff($curlHandle);

        if ($curlErrNo) {
            return [
                'status' => self::STATUS_CURLERROR,
                'curl_errno' => $curlErrNo,
                'curl_errmsg' => $curlErrMsg,
            ];
        }

        $headerBlob = substr($response, 0, $curlInfo['header_size']);
        // In the case of 204 No Content, strlen($response) == $curlInfo['header_size].
        // This will cause substr($response, $curlInfo['header_size']) return FALSE instead of NULL
        // An exception will be thrown when calling getBodyAsString then
        $responseBody = substr($response, $curlInfo['header_size']) ?: '';

        unset($response);

        // In the case of 100 Continue, or redirects we'll have multiple lists
        // of headers for each separate HTTP response. We can easily split this
        // because they are separated by \r\n\r\n
        $headerBlob = explode("\r\n\r\n", trim($headerBlob, "\r\n"));

        // We only care about the last set of headers
        $headerBlob = $headerBlob[count($headerBlob) - 1];

        // Splitting headers
        $headerBlob = explode("\r\n", $headerBlob);

        return $this->parseCurlResponse($headerBlob, $responseBody, $curlHandle);
    }

    /**
     * Sends an asynchronous HTTP request.
     *
     * We keep this in a separate method, so we can call it without triggering
     * the beforeRequest event and don't do the poll().
     */
    protected function sendAsyncInternal(RequestInterface $request, callable $success, callable $error, int $retryCount = 0)
    {
        if (!$this->curlMultiHandle) {
            $this->curlMultiHandle = curl_multi_init();
        }
        $curl = curl_init();
        $this->prepareCurl($request, $curl);
        curl_multi_add_handle($this->curlMultiHandle, $curl);

        $resourceId = (int) $curl;
        $this->curlMultiMap[$resourceId] = [
            $request,
            $success,
            $error,
            $retryCount,
        ];
    }

    /**
     * @param resource|\CurlHandle $curlHandle
     */
    protected function prepareCurl(RequestInterface $request, $curlHandle): void
    {
        $handleId = (int) $curlHandle;
        $this->headerLinesMap[$handleId] = [];
        $options = $this->createCurlSettingsArray($request);

        $options[CURLOPT_RETURNTRANSFER] = false;
        $options[CURLOPT_HEADER] = false;

        if (!($options[CURLOPT_NOBODY] ?? false) && !array_key_exists(CURLOPT_WRITEFUNCTION, $options)) {
            $options[CURLOPT_FILE] = $this->responseResourcesMap[$handleId] = $options[CURLOPT_FILE] ?? \fopen(
                "php://temp/maxmemory:{$this->maxMemorySize}",
                'rw+b'
            );
        } else {
            $this->responseResourcesMap[$handleId] = null;
        }

        $userHeaderFunction = $this->curlSettings[CURLOPT_HEADERFUNCTION] ?? null;
        $options[CURLOPT_HEADERFUNCTION] = function ($curlHandle, $str) use ($userHeaderFunction) {
            // Call user func
            if (is_callable($userHeaderFunction)) {
                $userHeaderFunction($curlHandle, $str);
            }

            return $this->receiveCurlHeader($curlHandle, $str);
        };

        curl_setopt_array($curlHandle, $options);
    }

    // @codeCoverageIgnoreStart

    /**
     * Calls curl_exec.
     *
     * This method exists so it can easily be overridden and mocked.
     *
     * @param resource|\CurlHandle $curlHandle
     */
    protected function curlExecInternal($curlHandle): bool
    {
        return curl_exec($curlHandle);
    }

    /**
     * Calls curl_exec and returns content string with headers.
     *
     * This method exists so it can easily be overridden and mocked.
     *
     * @param resource|\CurlHandle $curlHandle
     *
     * @deprecated
     */
    protected function curlExec($curlHandle): string
    {
        $result = $this->curlExecInternal($curlHandle);

        $handleId = (int) $curlHandle;

        if (!$result || !isset($this->responseResourcesMap[$handleId])) {
            return '';
        }
        rewind($this->responseResourcesMap[$handleId]);

        return stream_get_contents($this->responseResourcesMap[$handleId]);
    }

    /**
     * Returns a bunch of information about a curl request.
     *
     * This method exists so it can easily be overridden and mocked.
     *
     * @param resource|\CurlHandle $curlHandle
     */
    protected function curlStuff($curlHandle): array
    {
        return [
            curl_getinfo($curlHandle),
            curl_errno($curlHandle),
            curl_error($curlHandle),
        ];
    }

    // @codeCoverageIgnoreEnd
}
