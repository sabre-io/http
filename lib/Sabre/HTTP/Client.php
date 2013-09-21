<?php

namespace Sabre\HTTP;

use Sabre\Event\EventEmitter;

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
 *
 * The beforeRequest event allows you to do some last minute changes to the
 * request before it's done, such as adding authentication headers.
 *
 * The afterRequest event will be emitted after the request is completed
 * succesfully.
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
 * @copyright Copyright (C) 2009-2013 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Client extends EventEmitter {

    /**
     * Wether or not exceptions should be thrown when a HTTP error is returned.
     *
     * @var bool
     */
    protected $throwExceptions = false;
    /*
     * @var array Default cURL settings
     */
    protected static $defaultCurlSettings=[
        CURLOPT_RETURNTRANSFER => true,
        // Return headers as part of the response
        CURLOPT_HEADER => true,
        // Automatically follow redirects
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_POSTREDIR => 3,
    ];
    
    /**
     * Initializes the client.
     *
     * @return void
     */
    public function __construct(array $settings=null) {
        static::initCurl();
        
        if (isset($settings['encoding'])) {
            static::setEncodings($settings['encoding']);
        }else{
            static::setEncodings(self::ENCODING_DEFAULT);
        }
        
        if (isset($settings['proxy'])) {
            static::setProxy($settings['proxy']);
        }
        
        $authType=isset($settings['authType'])?$settings['authType']:self::AUTH_DEFAULT;
        
        if (isset($settings['userName'])) {
            static::setAuth($settings['userName'],$settings['password'],$authType);
        }
        
        if (isset($settings['verifyPeer'])) {
            $this->setVerifyPeer($settings['verifyPeer']);
        }
        
        if (isset($settings['cert'])) {
            $this->addTrustedCertificates($settings['cert']);
        }
    }
    
    public function __destruct() {
        if($this->curlHandle)curl_close($this->curlHandle);
    }
    
    /**
    * Initializes CURL handle
    * look for __construct docs
    * @param array $settings settings for CURL in format for curlopt_setopt_array
    */
    protected function initCurl(array &$settings=null){
        $this->curlHandle=curl_init();
        if (!$this->curlHandle) {
            throw new Sabre_DAV_Exception('[CURL] unable to initialize curl handle');
        }
        $curlSettings = static::$defaultCurlSettings;
        if (isset($settings)&&is_array($settings)){
            $curlSettings+=$settings;
            unset($settings);
        }
        static::setCurlSettings($curlSettings);
        unset($curlSettings);
    }
    
    const AUTH_BASIC = 1;//<Basic authentication
    const AUTH_DIGEST = 2;//<Digest authentication
    const AUTH_DEFAULT= 3;//<Default auth type
    
    
    const ENCODING_IDENTITY = 1;//<Identity encoding, which basically does not nothing.
    const ENCODING_DEFLATE = 2;//<Deflate encoding
    const ENCODING_GZIP = 4;//<Gzip encoding
    const ENCODING_ALL = 7;//<Sends all encoding headers.
    const ENCODING_DEFAULT = self::ENCODING_IDENTITY;//<Default encoding.
    
    
    
    /**
     * Sends a request to a HTTP server, and returns a response.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function send(RequestInterface $request) {

        $this->emit('beforeRequest', [$request]);

        $retryCount = 0;

        do {

            $retry = false;

            try {
                $response = $this->doRequest($request);

                $code = (int)$response->getStatus();

                // This was a HTTP error
                if ($code > 399) {

                    $this->emit('error', [$request, $response, &$retry, $retryCount]);
                    $this->emit('error:' . $code, [$request, $response, &$retry, $retryCount]);

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
                $retryCount++;
            }

        } while ($retry);

        $this->emit('afterRequest', [$request, $response]);

        if ($this->throwExceptions && $code > 399) {
            throw new ClientHttpException($response);
        }

        return $response;

    }

    /**
     * If this is set to true, the Client will automatically throw exceptions
     * upon http errors.
     *
     * @param bool $throwExceptions
     * @return void
     */
    public function setThrowExceptions($throwExceptions) {

        $this->throwExceptions = $throwExceptions;

    }

    /**
     * Adds a cURL setting.
     *
     * @param int $optName
     * @param mixed $value
     * @return void
     */
    public function addCurlSetting($optName, $value) {
        static::setCurlSetting($optName,$value);
    }
    
    /**
     * Sets a cURL setting.
     *
     * @param int $optName cURL constant for option
     * @param mixed $value value
     * @return boolean the same that cURL returns
     */
    public function setCurlSetting($optName,$value){
        return curl_setopt($this->curlHandle,$optName,$value);
    }
    
    /**
     * Sets an array of cURL settings.
     *
     * @param $arrayOfSettings the same that for cor_setopt_array
     * @return boolean the same that cURL returns
     */
    public function setCurlSettings(array $arrayOfSettings){
        return curl_setopt_array($this->curlHandle,$arrayOfSettings);
    }
    
    
     /** converts
     * @param number $encodings bitwise OR of needed ENCODING_* constants of this class
     * to format, suitable for CURL
     */
    protected static function convertEncodingsToInnerFormat(&$encodings){
        $encodingsList = [];
        if ($encodings & self::ENCODING_IDENTITY) {
            $encodingsList[] = 'identity';
        }
        if ($encodings & self::ENCODING_DEFLATE) {
            $encodingsList[] = 'deflate';
        }
        if ($encodings & self::ENCODING_GZIP) {
            $encodingsList[] = 'gzip';
        }
        return implode(',', $encodingsList);
    }
    
     /** converts
     * @param number $authType bitwise OR of needed AUTH_* constants of this class
     * to format, suitable for CURL
     */
    protected static function convertAuthTypeToInnerFormat(int &$authType){
        $curlAuthType = 0;
        if ($authType & self::AUTH_BASIC) {
            $curlAuthType |= CURLAUTH_BASIC;
        }
        if ($authType & self::AUTH_DIGEST) {
            $curlAuthType |= CURLAUTH_DIGEST;
        }
        return $curlAuthType;
    }
    
     /**
     * Used to set enconings
     *
     * @param integer $encodings  bitwise OR of needed ENCODING_* constants of this class
     */
    public function setEncodings($encodings=self::ENCODING_DEFAULT){
        static::setCurlSetting(CURLOPT_ENCODING,static::convertEncodingsToInnerFormat($encodings));
    }
    
     /**
     * Used to set proxy
     *
     * @param string $proxyAddr address of proxy in format host:port
     */
    public function setProxy(string $proxyAddr) {
        static::setCurlSetting(CURLOPT_PROXY,$proxyAddr);
    }
    
    
    /**
     * Used to set auth type
     *  
     * @param string $userName 
     * @param string $password 
     * @param integer $authType  If DIGEST is used, the client makes 1 extra request per request, to get the authentication tokens.
     */
    public function setAuth($userName='',$password='',$authType=self::AUTH_DEFAULT) {
        if ($userName && $authType) {
            static::setCurlSetting(CURLOPT_USERPWD,$userName.':'.$password);
        }
        static::setCurlSetting(CURLOPT_HTTPAUTH,static::convertAuthTypeToInnerFormat($authType));
    }
    
    /**
     * Used to set certificates file.
     * Not for usage by end user because addTrustedCertificates checks wheither file exist in call time but
     * this function will check this requirement during execution curl request.
     *
     * @param string $certificatesPath
     */
     
    protected function setCertificates($certificatesPath){
        static::setCurlSetting(CURLOPT_CAINFO,$certificatesPath);
    }
    
    /**
     * Enables/disables SSL peer verification
     *
     * @param boolean $shouldVerifyPeer
     */
    public function setVerifyPeer($shouldVerifyPeer){
        static::setCurlSetting(CURLOPT_SSL_VERIFYPEER,$shouldVerifyPeer);
    }
    
    /**
     * Add trusted root certificates to the webdav client.
     *
     * @param string $certificatesPath absolute path to a file which contains all trusted certificates
     */
    public function addTrustedCertificates($certificatesPath) {
        if(is_string($certificatesPath)){
            if(!file_exists($certificatesPath))throw new Exception('certificates path is not valid');
            static::setCertificates($certificatesPath);
        }else{
            throw new Exception('$certificates must be the absolute path of a file holding one or more certificates to verify the peer with.');
        }
    }

    /**
     * This method is responsible for performing a single request.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    protected function doRequest(RequestInterface $request) {

        //$settings = $this->curlSettings;

        switch($request->getMethod()) {
            case 'HEAD' :
                $settings[CURLOPT_NOBODY] = true;
                $settings[CURLOPT_CUSTOMREQUEST] = 'HEAD';
                $settings[CURLOPT_POSTFIELDS] = null;
                $settings[CURLOPT_PUT] = false;
                break;
            case 'GET' :
                $settings[CURLOPT_CUSTOMREQUEST] = 'GET';
                $settings[CURLOPT_POSTFIELDS] = null;
                $settings[CURLOPT_PUT] = false;
                break;
            default :
                $body = $request->getBody(MessageInterface::BODY_RAW);
                if (is_resource($body)) {
                    // This needs to be set to PUT, regardless of the actual
                    // method used. Without it, INFILE will be ignored for some
                    // reason.
                    $settings[CURLOPT_PUT] = true;
                    $settings[CURLOPT_INFILE] = $request->getBody();
                } else {
                    // Else, it's a string.
                    $settings[CURLOPT_POSTFIELDS] = $body;
                }
                $settings[CURLOPT_CUSTOMREQUEST] = $request->getMethod();
                break;

        }

        $nHeaders = [];
        foreach($request->getHeaders() as $key=>$value) {

            $nHeaders[] = $key . ': ' . $value;

        }
        $settings[CURLOPT_HTTPHEADER] = $nHeaders;
        $settings[CURLOPT_URL] = $request->getUrl();
        static::setCurlSettings($settings);
        
        list(
            $response,
            $curlInfo,
            $curlErrNo,
            $curlError
        ) = static::curlRequest();

        if ($curlErrNo) {
            throw new ClientException($curlError, $curlErrNo);
        }

        $headerBlob = substr($response, 0, $curlInfo['header_size']);
        $responseBody = substr($response, $curlInfo['header_size']);

        unset($response);

        // In the case of 100 Continue, or redirects we'll have multiple lists
        // of headers for each separate HTTP response. We can easily split this
        // because they are separated by \r\n\r\n
        $headerBlob = explode("\r\n\r\n", trim($headerBlob, "\r\n"));

        // We only care about the last set of headers
        $headerBlob = $headerBlob[count($headerBlob)-1];

        // Splitting headers
        $headerBlob = explode("\r\n", $headerBlob);

        $headers = [];
        foreach($headerBlob as $header) {
            $parts = explode(':', $header, 2);
            if (count($parts)==2) {
                $headers[trim($parts[0])] = trim($parts[1]);
            }
        }

        $response = new Response();
        $response->setStatus($curlInfo['http_code']);
        $response->setHeaders($headers);
        $response->setBody($responseBody);


        return $response;

    }


    /**
     * Cached curl handle.
     *
     * By keeping this resource around for the lifetime of this object, things
     * like persistent connections are possible.
     *
     * @var resource
     */
    private $curlHandle;

    /**
     * Wrapper for all curl functions.
     *
     * The only reason this was split out in a separate method, is so it
     * becomes easier to unittest.
     *
     * @return array
     */
    // @codeCoverageIgnoreStart
    protected function curlRequest() {
        return [
            curl_exec($this->curlHandle),
            curl_getinfo($this->curlHandle),
            curl_errno($this->curlHandle),
            curl_error($this->curlHandle)
        ];

    }
    // @codeCoverageIgnoreEnd

}
