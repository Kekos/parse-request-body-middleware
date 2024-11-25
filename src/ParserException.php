<?php declare(strict_types=1);

namespace Kekos\ParseRequestBodyMiddleware;

use Exception;

use function sprintf;

class ParserException extends Exception
{
    public const ERR_JSON = 1;
    public const ERR_UNEXPECTED_EMPTY_PARSED_BODY = 2;

    public static function jsonError(string $message): self
    {
        return new self(
            sprintf('JSON decode error: "%s"', $message),
            self::ERR_JSON
        );
    }

    public static function unexpectedEmptyParsedBody(): self
    {
        return new self(
            'The parsed body was unexpectedly empty. Did you consume the `php://input` stream by accident?',
            self::ERR_UNEXPECTED_EMPTY_PARSED_BODY,
        );
    }
}
