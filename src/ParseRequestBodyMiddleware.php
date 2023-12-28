<?php declare(strict_types=1);

namespace Kekos\ParseRequestBodyMiddleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ParseRequestBodyMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Parser $parser,
    )
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $this->parser->process($request);

        return $handler->handle($request);
    }
}
