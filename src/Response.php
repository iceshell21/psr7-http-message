<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage;

use IceShell21\Psr7HttpMessage\Enum\HttpStatusCode;
use IceShell21\Psr7HttpMessage\Trait\MessageTrait;
use IceShell21\Psr7HttpMessage\Security\SecurityValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * HTTP response implementation with immutable design.
 * Enhanced with PHP 8.4+ features, security validation and performance monitoring.
 */
class Response implements ResponseInterface
{
    use MessageTrait;

    private ?StreamInterface $body;
    private ?SecurityValidator $validator = null;
    
    // Property hook for computed reason phrase (PHP 8.4+)
    public string $computedReasonPhrase {
        get => $this->reasonPhrase ?? $this->statusCode->getReasonPhrase();
    }
    
    // Asymmetric visibility for security headers (PHP 8.4+)
    public private(set) array $securityHeaders = [];
    public private(set) array $performanceMetrics = [
        'created_at' => 0,
        'headers_processed' => 0,
        'body_size' => 0,
    ];

    public function __construct(
        mixed $body = null,
        private HttpStatusCode $statusCode = HttpStatusCode::OK,
        array $headers = [],
        private string $protocolVersion = '1.1',
        private ?string $reasonPhrase = null
    ) {
        // Initialize security validator
        $this->validator = new SecurityValidator(strictMode: false);
        
        // Validate status code
        $this->validator->validateStatusCode($this->statusCode->value);
        
        // Validate reason phrase if provided
        if ($this->reasonPhrase !== null) {
            $this->validator->validateReasonPhrase($this->reasonPhrase);
        }
        
        // Create body and initialize headers
        $this->body = $this->createBody($body);
        $this->initializeHeaders($headers);
        
        // Initialize metrics
        $this->performanceMetrics['created_at'] = hrtime(true);
        $this->performanceMetrics['headers_processed'] = count($headers);
        $this->performanceMetrics['body_size'] = $this->body?->getSize() ?? 0;
        
        // Set default security headers
        $this->addDefaultSecurityHeaders();
    }

    private function createBody(mixed $body): ?StreamInterface
    {
        return match (true) {
            $body === null => null,
            $body instanceof StreamInterface => $body,
            default => new Stream($body)
        };
    }

    public function getStatusCode(): int
    {
        return $this->statusCode->value;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        // Validate status code
        $this->validator?->validateStatusCode($code);
        
        if ($reasonPhrase !== '') {
            $this->validator?->validateReasonPhrase($reasonPhrase);
        }
        
        $new = clone $this;
        $new->statusCode = HttpStatusCode::from($code);
        $new->reasonPhrase = $reasonPhrase ?: $new->statusCode->getReasonPhrase();
        return $new;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase ?? $this->statusCode->getReasonPhrase();
    }
    
    /**
     * Add default security headers.
     */
    private function addDefaultSecurityHeaders(): void
    {
        $defaultSecurityHeaders = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];

        foreach ($defaultSecurityHeaders as $name => $value) {
            if (!$this->hasHeader($name)) {
                $this->headerCollection = $this->headerCollection->with($name, $value);
                $this->securityHeaders[$name] = $value;
            }
        }
    }
    
    /**
     * Add security header with validation.
     */
    public function withSecurityHeader(string $name, string $value): static
    {
        $this->validator?->validateHeaderName($name);
        $this->validator?->validateHeaderValue($value);
        
        $response = $this->withHeader($name, $value);
        $response->securityHeaders[$name] = $value;
        
        return $response;
    }
    
    /**
     * Check if response has security headers.
     */
    public function hasSecurityHeaders(): bool
    {
        return !empty($this->securityHeaders);
    }
    
    /**
     * Get all security headers.
     */
    public function getSecurityHeaders(): array
    {
        return $this->securityHeaders;
    }
    
    /**
     * Create JSON response with proper headers.
     */
    public static function json(array $data, int $status = 200, array $headers = []): static
    {
        $headers['Content-Type'] = 'application/json';
        
        return new static(
            body: json_encode($data, JSON_THROW_ON_ERROR),
            statusCode: HttpStatusCode::from($status),
            headers: $headers
        );
    }
    
    /**
     * Get performance and security statistics.
     */
    public function getStats(): array
    {
        return [
            'performance_metrics' => $this->performanceMetrics,
            'security_headers' => $this->securityHeaders,
            'status_code' => $this->statusCode->value,
            'headers_count' => count($this->getHeaders()),
            'body_size' => $this->body?->getSize(),
            'protocol_version' => $this->protocolVersion,
        ];
    }
}
