<?php declare(strict_types=1);

namespace Kekos\ParseRequestBodyMiddleware\Tests;

use Kekos\ParseRequestBodyMiddleware\Parser;
use Kekos\ParseRequestBodyMiddleware\ParserException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

use function current;
use function http_build_query;
use function json_encode;
use function json_last_error;
use function json_last_error_msg;
use function key;
use function sprintf;

use const JSON_ERROR_NONE;

class ParserTest extends TestCase
{
    private static Psr17Factory $psr17_factory;
    private static Parser $parser;

    public static function setUpBeforeClass(): void
    {
        self::$psr17_factory = new Psr17Factory();
        self::$parser = new Parser(self::$psr17_factory, self::$psr17_factory);
    }

    public function testProcessPostJson(): void
    {
        $data = [
            'foo' => 'bar',
        ];

        $request = self::createJsonRequest('POST', self::safeJsonEncode($data));
        $request = self::$parser->process($request);

        $this->assertEquals($data, $request->getParsedBody());
    }

    public function testProcessPostMalformedJson(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionCode(ParserException::ERR_JSON);

        $request = self::createJsonRequest('POST', '{"wrong');

        self::$parser->process($request);
    }

    public function testProcessPutUrlEncoded(): void
    {
        $data = [
            'foo' => 'bar',
        ];

        $request = self::createUrlEncodedRequest('PUT', http_build_query($data));
        $request = self::$parser->process($request);

        $this->assertEquals($data, $request->getParsedBody());
    }

    public function testProcessPutMultipartFormData(): void
    {
        $expected_data = [
            'foo' => 'bar',
        ];

        $expected_file_key = 'baz';
        $expected_file = [
            'contents' => 'this is a test',
            'mime' => 'text/plain',
            'name' => 'text.txt',
        ];

        $request = self::createMultipartFormDataRequest(
            'PUT',
            key($expected_data),
            current($expected_data),
            $expected_file_key,
            $expected_file['name'],
            $expected_file['mime'],
            $expected_file['contents']
        );
        $request = self::$parser->process($request);
        $files = $request->getUploadedFiles();

        $this->assertEquals($expected_data, $request->getParsedBody());
        $this->assertEquals($expected_file['contents'], (string) $files[$expected_file_key]->getStream());
        $this->assertEquals($expected_file['mime'], $files[$expected_file_key]->getClientMediaType());
        $this->assertEquals($expected_file['name'], $files[$expected_file_key]->getClientFilename());
    }

    private static function safeJsonEncode(mixed $value): string
    {
        $value = json_encode($value);

        if ($value === false || json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(json_last_error_msg());
        }

        return $value;
    }

    private static function createJsonRequest(string $method, string $data): ServerRequestInterface
    {
        $request = self::$psr17_factory->createServerRequest($method, '/')
            ->withHeader('Content-Type', 'application/json');
        $request->getBody()->write($data);

        return $request;
    }

    private static function createUrlEncodedRequest(string $method, string $data): ServerRequestInterface
    {
        $request = self::$psr17_factory->createServerRequest($method, '/')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        $request->getBody()->write($data);

        return $request;
    }

    private static function createMultipartFormDataRequest(
        string $method,
        string $data_name,
        string $data_value,
        string $file_key,
        string $file_name,
        string $file_mime,
        string $file_contents
    ): ServerRequestInterface
    {
        $boundary = 'b----1234';

        $body = <<<EOF
--$boundary\r
Content-Disposition: form-data; name="$data_name"\r
\r
$data_value\r
--$boundary\r
Content-Disposition: form-data; name="$file_key"; filename="$file_name"\r
Content-Type: $file_mime\r
\r
$file_contents\r
--$boundary--\r
EOF;

        $request = self::$psr17_factory->createServerRequest($method, '/')
            ->withHeader('Content-Type', sprintf('multipart/form-data;boundary=%s', $boundary));
        $request->getBody()->write($body);

        return $request;
    }
}
