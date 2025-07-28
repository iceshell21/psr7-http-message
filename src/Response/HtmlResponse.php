<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Response;

use IceShell21\Psr7HttpMessage\Enum\HttpStatusCode;
use IceShell21\Psr7HttpMessage\Response;
use IceShell21\Psr7HttpMessage\Stream;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

/**
 * Specialized response class for HTML content.
 */
final class HtmlResponse extends Response
{
    public function __construct(
        string $html,
        HttpStatusCode $status = HttpStatusCode::OK,
        array $headers = []
    ) {
        $headers = array_merge(['Content-Type' => 'text/html; charset=utf-8'], $headers);
        parent::__construct($this->createStream($html), $status, $headers);
    }

    private function createStream(string $html): StreamInterface
    {
        if (!is_string($html)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid content (%s) provided to %s',
                get_debug_type($html),
                self::class
            ));
        }

        $body = new Stream();
        $body->write($html);
        $body->rewind();
        return $body;
    }
}
