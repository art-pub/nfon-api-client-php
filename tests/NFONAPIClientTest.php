<?php declare (strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/NFONAPIClient.php';

use NFONAPIClient\client;
use PHPUnit\Framework\TestCase;

class NFONApiClientTest extends TestCase
{
    public function testGetAuthentication(): void
    {
        // given
        $method = "GET";
        $body = "";
        $path = "customers";
        $apiSecret = "4f5f5402-da77-410a-9aad-fd5ef74f746e";
        $date = "Sun, 31 Dec 2023 12:00:00 GMT";
        $contentType = 'application/json';

        $expectedContentMD5 = 'd41d8cd98f00b204e9800998ecf8427e';
        $expectedSignature = '2kbei1CrOlKo4pkwcMJ1aOPkYzw=';

        // when
        [$rdate, $rcontentType, $rcontentMD5, $signature] = client::getAuthentication($path, $apiSecret, $method, $body, $contentType, $date);

        // then
        $this->assertSame($contentType, $rcontentType, "Content-Type is not identical: " . $rcontentType);
        $this->assertSame($expectedContentMD5, $rcontentMD5, "Content-MD5 is not identical: " . $rcontentMD5);
        $this->assertSame($expectedSignature, $signature, "Signature is not identical: " . $signature);
    }

    public function testRequestStatus(): void
    {
        // given
        $apiBasePath = getenv("API_BASEPATH", true); // you need to set the API_BASEPATH i.e. with bash$ export API_BASEPATH="https://api09.nfon.com"

        // when
        [$response, $success] = client::request(
            apiBasePath: $apiBasePath,
            apiKey: "status needs no key",
            signature: "status needs no secret",
            method: "GET",
            path: "/api/version",
            apiDate: date("D, d M Y H:i:s T"),
            contentMD5: "",
            body: "",
            contentLength: 0,
            contentType: "application/json",
            apiHeaders: [],
        );
        // then
        $this->assertSame("/api/version", $response['href']);
        $this->assertArrayHasKey('version', $response['dataMap']);
    }

    public function testRequestPhonebook(): void
    {
        // given
        $apiBasePath = getenv("API_BASEPATH", true); // you need to set the API_BASEPATH i.e. with bash$ export API_BASEPATH="https://api09.nfon.com"
        $apiKey = getenv("API_KEY", true);
        $apiSecret = getenv("API_SECRET", true);
        $account = getenv("API_ACCOUNT", true);
        $path = "/api/customers/" . $account . "/phone-books?_pagesize=3";
        $method = "GET";
        $body = "";
        $contentType = "application/json";

        // when
        [$rdate, $rcontentType, $rcontentMD5, $signature] = client::getAuthentication($path, $apiSecret, $method, $body, $contentType);

        [$response, $success] = client::request(
            apiBasePath: $apiBasePath,
            apiKey: $apiKey,
            signature: $signature,
            method: $method,
            path: $path,
            apiDate: $rdate,
            contentMD5: $rcontentMD5,
            body: $body,
            contentLength: strlen($body),
            contentType: $rcontentType,
            apiHeaders: [],
        );
        // then
        $this->assertTrue($success, "request failed");
        $this->assertArrayHasKey('href', $response);
        $this->assertArrayHasKey('offset', $response);
        $this->assertArrayHasKey('total', $response);
        $this->assertArrayHasKey('size', $response);
        $this->assertArrayHasKey('links', $response);
        $this->assertArrayHasKey('items', $response);
        $this->assertArrayHasKey(0, $response['dataMap']);
    }
}
