<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Factory;

use IceShell21\Psr7HttpMessage\Enum\HttpMethod;
use IceShell21\Psr7HttpMessage\ServerRequest;
use IceShell21\Psr7HttpMessage\Stream;
use IceShell21\Psr7HttpMessage\UploadedFile;
use IceShell21\Psr7HttpMessage\Uri;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * PSR-17 HTTP Server Request Factory implementation.
 */
final class ServerRequestFactory implements ServerRequestFactoryInterface
{
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest(
            HttpMethod::fromString($method),
            is_string($uri) ? new Uri($uri) : $uri,
            serverParams: $serverParams
        );
    }

    /**
     * Create server request from PHP superglobals.
     */
    public function createServerRequestFromGlobals(): ServerRequestInterface
    {
        $method = HttpMethod::fromString($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $this->createUriFromGlobals();
        $headers = $this->getHeadersFromServer($_SERVER);
        $body = new Stream('php://input');
        $protocolVersion = $this->getProtocolVersion($_SERVER);
        
        $uploadedFiles = $this->normalizeUploadedFiles($_FILES ?? []);
        $parsedBody = $this->getParsedBody($method, $headers);

        return new ServerRequest(
            method: $method,
            uri: $uri,
            headers: $headers,
            body: $body,
            protocolVersion: $protocolVersion,
            serverParams: $_SERVER,
            cookieParams: $_COOKIE ?? [],
            queryParams: $_GET ?? [],
            uploadedFiles: $uploadedFiles,
            parsedBody: $parsedBody
        );
    }

    private function createUriFromGlobals(): Uri
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $port = (int) ($_SERVER['SERVER_PORT'] ?? ($scheme === 'https' ? 443 : 80));
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $query = $_SERVER['QUERY_STRING'] ?? '';

        // Remove standard ports
        if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
            $port = null;
        }

        return new Uri($scheme, '', $host, $port, $path, $query);
    }

    private function getHeadersFromServer(array $server): array
    {
        $headers = [];
        
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = [$value];
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $name = str_replace('_', '-', $key);
                $headers[$name] = [$value];
            }
        }

        return $headers;
    }

    private function getProtocolVersion(array $server): string
    {
        return match ($server['SERVER_PROTOCOL'] ?? '') {
            'HTTP/1.0' => '1.0',
            'HTTP/2.0' => '2.0',
            default => '1.1'
        };
    }

    private function normalizeUploadedFiles(array $files): array
    {
        $normalized = [];
        
        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFile) {
                $normalized[$key] = $value;
            } elseif (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = $this->createUploadedFileFromSpec($value);
            } elseif (is_array($value)) {
                $normalized[$key] = $this->normalizeUploadedFiles($value);
            }
        }
        
        return $normalized;
    }

    private function createUploadedFileFromSpec(array $value): UploadedFile
    {
        return new UploadedFile(
            $value['tmp_name'],
            $value['size'] ?? null,
            $value['error'] ?? UPLOAD_ERR_OK,
            $value['name'] ?? null,
            $value['type'] ?? null
        );
    }

    private function getParsedBody(HttpMethod $method, array $headers): mixed
    {
        if (!in_array($method, [HttpMethod::POST, HttpMethod::PUT, HttpMethod::PATCH], true)) {
            return null;
        }

        $contentType = $headers['content-type'][0] ?? '';
        
        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            return $_POST ?? [];
        }

        if (str_contains($contentType, 'multipart/form-data')) {
            return $_POST ?? [];
        }

        return null;
    }
}
