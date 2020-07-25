<?php declare(strict_types=1);

namespace Kekos\ParseRequestBodyMiddleware;

use Exception;

use function sprintf;

class ParserException extends Exception
{
    public const ERR_JSON = 1;
    public const ERR_NOT_ACCEPTABLE = 2;

    public static function jsonError(string $message): self
    {
        return new self(
            sprintf('JSON decode error: "%s"', $message),
            self::ERR_JSON
        );
    }
}
