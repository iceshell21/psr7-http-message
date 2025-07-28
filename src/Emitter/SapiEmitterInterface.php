<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Emitter;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface for HTTP response emitters.
 */
interface SapiEmitterInterface
{
    /**
     * Emit an HTTP response.
     */
    public function emit(ResponseInterface $response): void;
}
