<?php declare(strict_types=1);

namespace Kekos\ParseRequestBodyMiddleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ParseRequestBodyMiddleware implements MiddlewareInterface
{
    /** @var Parser */
    private $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $this->parser->process($request);

        return $handler->handle($request);
    }
}
