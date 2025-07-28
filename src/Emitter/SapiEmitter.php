<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Emitter;

use Psr\Http\Message\ResponseInterface;

/**
 * SAPI emitter for outputting HTTP responses.
 */
final class SapiEmitter implements SapiEmitterInterface
{
    public function __construct(
        private bool $enableGzip = true,
        private int $bufferSize = 8192
    ) {}

    public function emit(ResponseInterface $response): void
    {
        // Check if headers have already been sent
        if (!headers_sent()) {
            $this->emitStatusLine($response);
            $this->emitHeaders($response);
        }
        
        $this->emitBody($response);
    }

    private function emitStatusLine(ResponseInterface $response): void
    {
        header(sprintf(
            'HTTP/%s %d %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ), true);
    }

    private function emitHeaders(ResponseInterface $response): void
    {
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value", false);
            }
        }
    }

    private function emitBody(ResponseInterface $response): void
    {
        $body = $response->getBody();
        
        if ($body->isSeekable()) {
            $body->rewind();
        }
        
        while (!$body->eof()) {
            echo $body->read($this->bufferSize);
        }
    }
}
