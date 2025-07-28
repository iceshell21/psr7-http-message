<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Response;

use IceShell21\Psr7HttpMessage\Enum\HttpStatusCode;
use IceShell21\Psr7HttpMessage\Response;
use IceShell21\Psr7HttpMessage\Stream;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\StreamInterface;

/**
 * Specialized response class for JSON content with automatic encoding.
 */
final class JsonResponse extends Response
{
    private const DEFAULT_JSON_FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    public function __construct(
        mixed $data = null,
        HttpStatusCode $status = HttpStatusCode::OK,
        array $headers = [],
        int $encodingOptions = self::DEFAULT_JSON_FLAGS
    ) {
        $json = $this->encodeJson($data, $encodingOptions);
        
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);
        
        parent::__construct($this->createStream($json), $status, $headers);
    }

    private function encodeJson(mixed $data, int $options): string
    {
        if ($data === null) {
            return 'null';
        }

        try {
            return json_encode($data, $options);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Unable to encode data to JSON: ' . $e->getMessage(), 0, $e);
        }
    }

    private function createStream(string $json): StreamInterface
    {
        $body = new Stream();
        $body->write($json);
        $body->rewind();

        return $body;
    }
}
