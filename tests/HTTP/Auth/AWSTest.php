<?php

declare(strict_types=1);

namespace Sabre\HTTP\Auth;

use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

class AWSTest extends \PHPUnit\Framework\TestCase
{
    private Response $response;

    private Request $request;

    private AWS $auth;

    public const REALM = 'SabreDAV unittest';

    public function setUp(): void
    {
        $this->response = new Response();
        $this->request = new Request('GET', '/');
        $this->auth = new AWS(self::REALM, $this->request, $this->response);
    }

    public function testNoHeader(): void
    {
        $this->request->setMethod('GET');
        $result = $this->auth->init();

        self::assertFalse($result, 'No AWS Authorization header was supplied, so we should have gotten false');
        self::assertEquals(AWS::ERR_NOAWSHEADER, $this->auth->errorCode);
    }

    public function testInvalidAuthorizationHeader(): void
    {
        $this->request->setMethod('GET');
        $this->request->setHeader('Authorization', 'Invalid Auth Header');

        self::assertFalse($this->auth->init(), 'The Invalid AWS authorization header');
    }

    public function testIncorrectContentMD5(): void
    {
        $accessKey = 'accessKey';
        $secretKey = 'secretKey';

        $this->request->setMethod('GET');
        $this->request->setHeaders([
            'Authorization' => "AWS $accessKey:sig",
            'Content-MD5' => 'garbage',
        ]);
        $this->request->setUrl('/');

        $this->auth->init();
        $result = $this->auth->validate($secretKey);

        self::assertFalse($result);
        self::assertEquals(AWS::ERR_MD5CHECKSUMWRONG, $this->auth->errorCode);
    }

    public function testNoDate(): void
    {
        $accessKey = 'accessKey';
        $secretKey = 'secretKey';
        $content = 'thisisthebody';
        $contentMD5 = base64_encode(md5($content, true));

        $this->request->setMethod('POST');
        $this->request->setHeaders([
            'Authorization' => "AWS $accessKey:sig",
            'Content-MD5' => $contentMD5,
        ]);
        $this->request->setUrl('/');
        $this->request->setBody($content);

        $this->auth->init();
        $result = $this->auth->validate($secretKey);

        self::assertFalse($result);
        self::assertEquals(AWS::ERR_INVALIDDATEFORMAT, $this->auth->errorCode);
    }

    public function testFutureDate(): void
    {
        $accessKey = 'accessKey';
        $secretKey = 'secretKey';
        $content = 'thisisthebody';
        $contentMD5 = base64_encode(md5($content, true));

        $date = new \DateTime('@'.(time() + (60 * 20)));
        $date->setTimezone(new \DateTimeZone('GMT'));
        $date = $date->format('D, d M Y H:i:s \\G\\M\\T');

        $this->request->setMethod('POST');
        $this->request->setHeaders([
            'Authorization' => "AWS $accessKey:sig",
            'Content-MD5' => $contentMD5,
            'Date' => $date,
        ]);

        $this->request->setBody($content);

        $this->auth->init();
        $result = $this->auth->validate($secretKey);

        self::assertFalse($result);
        self::assertEquals(AWS::ERR_REQUESTTIMESKEWED, $this->auth->errorCode);
    }

    public function testPastDate(): void
    {
        $accessKey = 'accessKey';
        $secretKey = 'secretKey';
        $content = 'thisisthebody';
        $contentMD5 = base64_encode(md5($content, true));

        $date = new \DateTime('@'.(time() - (60 * 20)));
        $date->setTimezone(new \DateTimeZone('GMT'));
        $date = $date->format('D, d M Y H:i:s \\G\\M\\T');

        $this->request->setMethod('POST');
        $this->request->setHeaders([
            'Authorization' => "AWS $accessKey:sig",
            'Content-MD5' => $contentMD5,
            'Date' => $date,
        ]);

        $this->request->setBody($content);

        $this->auth->init();
        $result = $this->auth->validate($secretKey);

        self::assertFalse($result);
        self::assertEquals(AWS::ERR_REQUESTTIMESKEWED, $this->auth->errorCode);
    }

    public function testIncorrectSignature(): void
    {
        $accessKey = 'accessKey';
        $secretKey = 'secretKey';
        $content = 'thisisthebody';

        $contentMD5 = base64_encode(md5($content, true));

        $date = new \DateTime('now');
        $date->setTimezone(new \DateTimeZone('GMT'));
        $date = $date->format('D, d M Y H:i:s \\G\\M\\T');

        $this->request->setUrl('/');
        $this->request->setMethod('POST');
        $this->request->setHeaders([
            'Authorization' => "AWS $accessKey:sig",
            'Content-MD5' => $contentMD5,
            'X-amz-date' => $date,
        ]);
        $this->request->setBody($content);

        $this->auth->init();
        $result = $this->auth->validate($secretKey);

        self::assertFalse($result);
        self::assertEquals(AWS::ERR_INVALIDSIGNATURE, $this->auth->errorCode);
    }

    public function testValidRequest(): void
    {
        $accessKey = 'accessKey';
        $secretKey = 'secretKey';
        $content = 'thisisthebody';
        $contentMD5 = base64_encode(md5($content, true));

        $date = new \DateTime('now');
        $date->setTimezone(new \DateTimeZone('GMT'));
        $date = $date->format('D, d M Y H:i:s \\G\\M\\T');

        $sig = base64_encode($this->hmacsha1($secretKey,
            "POST\n$contentMD5\n\n$date\nx-amz-date:$date\n/evert"
        ));

        $this->request->setUrl('/evert');
        $this->request->setMethod('POST');
        $this->request->setHeaders([
            'Authorization' => "AWS $accessKey:$sig",
            'Content-MD5' => $contentMD5,
            'X-amz-date' => $date,
        ]);

        $this->request->setBody($content);

        $this->auth->init();
        $result = $this->auth->validate($secretKey);

        self::assertTrue($result, 'Signature did not validate, got errorcode '.$this->auth->errorCode);
        self::assertEquals($accessKey, $this->auth->getAccessKey());
    }

    public function test401(): void
    {
        $this->auth->requireLogin();
        $test = preg_match('/^AWS$/', $this->response->getHeader('WWW-Authenticate'), $matches);
        self::assertTrue(true == $test, 'The WWW-Authenticate response didn\'t match our pattern');
    }

    /**
     * Generates an HMAC-SHA1 signature.
     */
    private function hmacsha1(string $key, string $message): string
    {
        $blocksize = 64;
        if (strlen($key) > $blocksize) {
            $key = pack('H*', sha1($key));
        }
        $key = str_pad($key, $blocksize, chr(0x00));
        $ipad = str_repeat(chr(0x36), $blocksize);
        $opad = str_repeat(chr(0x5C), $blocksize);
        $hmac = pack('H*', sha1(($key ^ $opad).pack('H*', sha1(($key ^ $ipad).$message))));

        return $hmac;
    }
}
