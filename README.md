# Request body parser PSR-15 middleware

PSR-15 middleware for parsing requests with JSON and URI encoded bodies regardless of HTTP method.

## Install

You can install this package via [Composer](http://getcomposer.org/):

```
composer require kekos/multipart-form-data-parser
```

## Documentation

### Supported content types native PHP

MIME type                         | POST | PUT/PATCH
--------------------------------- | ---- | ---------
application/json                  |      |
application/x-www-form-urlencoded |   ✓  |
multipart/form-data               |   ✓  |

### Supported content types this package

This package acts like a polyfill for unsupported content types (JSON)
and types only supported in POST methods by PHP.

MIME type                         | POST | PUT/PATCH
--------------------------------- | ---- | ---------
application/json                  |   ✓  |     ✓
application/x-www-form-urlencoded |      |     ✓
multipart/form-data               |      |     ✓

## Usage

Add the `\Kekos\ParseRequestBodyMiddleware\ParseRequestBodyMiddleware` middleware
to your PSR-15 handler, and it will populate
`ServerRequestInterface::getParsedBody()` as array.

The parser will throw \Kekos\ParseRequestBodyMiddleware\ParserException` if
a malformed JSON body was sent.

## Bugs and improvements

Report bugs in GitHub issues or feel free to make a pull request :-)

## License

MIT
