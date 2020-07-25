<?php declare(strict_types=1);

namespace Kekos\ParseRequestBodyMiddleware;

use Kekos\MultipartFormDataParser\Parser as MultipartFormDataParser;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;

use function explode;
use function json_decode;
use function json_last_error;
use function json_last_error_msg;
use function parse_str;

use const JSON_ERROR_NONE;

class Parser
{
    /** @var UploadedFileFactoryInterface */
    private $uploaded_file_factory;
    /** @var StreamFactoryInterface */
    private $stream_factory;

    public function __construct(
        UploadedFileFactoryInterface $uploaded_file_factory,
        StreamFactoryInterface $stream_factory
    ) {
        $this->uploaded_file_factory = $uploaded_file_factory;
        $this->stream_factory = $stream_factory;
    }

    public function process(ServerRequestInterface $request): ServerRequestInterface
    {
        [$content_type] = explode(';', $request->getHeaderLine('Content-Type'));

        if ($content_type === 'application/json') {
            $request = $request->withParsedBody($this->jsonDecode((string) $request->getBody()));
        } elseif ($request->getMethod() !== 'POST') {
            switch ($content_type) {
                case 'application/x-www-form-urlencoded':
                    $request = $request->withParsedBody($this->urlQueryDecode((string) $request->getBody()));
                    break;

                case 'multipart/form-data':
                    $multipart_parser = MultipartFormDataParser::createFromRequest(
                        $request,
                        $this->uploaded_file_factory,
                        $this->stream_factory
                    );

                    $request = $multipart_parser->decorateRequest($request);
                    break;
            }
        }

        return $request;
    }

    private function jsonDecode(string $json): array
    {
        $value = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ParserException::jsonError(json_last_error_msg());
        }

        return $value;
    }

    private function urlQueryDecode(string $url_query): array
    {
        parse_str($url_query, $result);

        return $result;
    }
}
