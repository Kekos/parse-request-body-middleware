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
use function request_parse_body;

use const JSON_ERROR_NONE;
use const PHP_VERSION_ID;

class Parser
{
    private UploadedFileCollectionFactory $file_collection_factory;

    public function __construct(
        private UploadedFileFactoryInterface $uploaded_file_factory,
        private StreamFactoryInterface $stream_factory,
    ) {
        $this->file_collection_factory = new UploadedFileCollectionFactory(
            $this->uploaded_file_factory,
            $this->stream_factory,
        );
    }

    public function process(ServerRequestInterface $request): ServerRequestInterface
    {
        [$content_type] = explode(';', $request->getHeaderLine('Content-Type'));

        if ($content_type === 'application/json') {
            $request = $request->withParsedBody($this->jsonDecode((string) $request->getBody()));
        } elseif ($request->getMethod() !== 'POST') {
            if (self::usePolyfill()) {
                return $this->parseWithPolyfill($request, $content_type);
            }

            return $this->parse($request, $content_type);
        }

        return $request;
    }

    private function parse(ServerRequestInterface $request, string $content_type): ServerRequestInterface
    {
        $has_content_length = ((int) ($_SERVER['HTTP_CONTENT_LENGTH'] ?? 0)) > 0;

        switch ($content_type) {
            case 'application/x-www-form-urlencoded':
                [$post_body] = request_parse_body();

                if (!$post_body && $has_content_length) {
                    throw ParserException::unexpectedEmptyParsedBody();
                }

                $request = $request->withParsedBody($post_body);
                break;

            case 'multipart/form-data':
                [$post_body, $files] = request_parse_body();

                if (!$post_body && !$files && $has_content_length) {
                    throw ParserException::unexpectedEmptyParsedBody();
                }

                $files = $this->file_collection_factory->fromPhpFilesArray($files);

                $request = $request
                    ->withParsedBody($post_body)
                    ->withUploadedFiles($files)
                ;
                break;
        }

        return $request;
    }

    private function parseWithPolyfill(ServerRequestInterface $request, string $content_type): ServerRequestInterface
    {
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

    private static function usePolyfill(): bool
    {
        // PHP 8.4 is required for native parsing
        if (PHP_VERSION_ID < 80400) {
            return true;
        }

        // `request_parse_body()` can only be used when the PHP script is invoked through a web context, e.g. via SAPI
        if (!isset($_SERVER['CONTENT_TYPE'])) {
            return true;
        }

        return false;
    }
}
