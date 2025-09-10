<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage\Security;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

/**
 * Comprehensive security validator for HTTP message components.
 * Protects against common attacks like header injection, DoS, and malformed data.
 */
final readonly class SecurityValidator
{
    // Security limits
    private const MAX_URI_LENGTH = 2048;
    private const MAX_HEADER_NAME_LENGTH = 128;
    private const MAX_HEADER_VALUE_LENGTH = 8192;
    private const MAX_HEADERS_COUNT = 100;
    private const MAX_METHOD_LENGTH = 10;
    private const MAX_REASON_PHRASE_LENGTH = 255;
    
    // Dangerous patterns
    private const CRLF_PATTERN = '/[\r\n]/';
    private const CONTROL_CHARS_PATTERN = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/';
    private const HEADER_NAME_PATTERN = '/^[a-zA-Z0-9\-_]+$/';
    private const SCRIPT_INJECTION_PATTERN = '/<script|javascript:|data:|vbscript:/i';
    
    public function __construct(
        private bool $strictMode = true,
        private array $allowedSchemes = ['http', 'https']
    ) {}

    /**
     * Validate HTTP method for security and RFC compliance.
     */
    public function validateHttpMethod(string $method): void
    {
        if (strlen($method) === 0) {
            throw new InvalidArgumentException('HTTP method cannot be empty');
        }

        if (strlen($method) > self::MAX_METHOD_LENGTH) {
            throw new InvalidArgumentException('HTTP method too long (max ' . self::MAX_METHOD_LENGTH . ' chars)');
        }

        if (!ctype_alpha($method)) {
            throw new InvalidArgumentException('HTTP method contains invalid characters');
        }

        // Check for control characters
        if (preg_match(self::CONTROL_CHARS_PATTERN, $method)) {
            throw new InvalidArgumentException('HTTP method contains control characters');
        }
    }

    /**
     * Validate URI for security threats and length limits.
     */
    public function validateUri(string $uri): void
    {
        if (strlen($uri) > self::MAX_URI_LENGTH) {
            throw new InvalidArgumentException('URI too long (max ' . self::MAX_URI_LENGTH . ' chars)');
        }

        // Check for dangerous script injection patterns
        if (preg_match(self::SCRIPT_INJECTION_PATTERN, $uri)) {
            throw new InvalidArgumentException('URI contains potentially dangerous script content');
        }

        // Check for control characters that could be used in attacks
        if (preg_match(self::CONTROL_CHARS_PATTERN, $uri)) {
            throw new InvalidArgumentException('URI contains invalid control characters');
        }

        // Validate URL structure if it's not empty
        if ($uri !== '' && !$this->isValidUriStructure($uri)) {
            throw new InvalidArgumentException('Invalid URI structure');
        }
    }

    /**
     * Validate URI object for additional security checks.
     */
    public function validateUriObject(UriInterface $uri): void
    {
        $scheme = $uri->getScheme();
        if ($scheme !== '' && !in_array($scheme, $this->allowedSchemes, true)) {
            throw new InvalidArgumentException('URI scheme not allowed: ' . $scheme);
        }

        // Additional host validation
        $host = $uri->getHost();
        if ($host !== '') {
            $this->validateHost($host);
        }

        // Port validation
        $port = $uri->getPort();
        if ($port !== null && ($port < 1 || $port > 65535)) {
            throw new InvalidArgumentException('Invalid port number: ' . $port);
        }
    }

    /**
     * Validate header name for RFC compliance and security.
     */
    public function validateHeaderName(string $name): void
    {
        if (strlen($name) === 0) {
            throw new InvalidArgumentException('Header name cannot be empty');
        }

        if (strlen($name) > self::MAX_HEADER_NAME_LENGTH) {
            throw new InvalidArgumentException('Header name too long (max ' . self::MAX_HEADER_NAME_LENGTH . ' chars)');
        }

        if (!preg_match(self::HEADER_NAME_PATTERN, $name)) {
            throw new InvalidArgumentException('Invalid header name format: ' . $name);
        }

        // Check for dangerous header names in strict mode
        if ($this->strictMode && $this->isDangerousHeaderName($name)) {
            throw new InvalidArgumentException('Potentially dangerous header name: ' . $name);
        }
    }

    /**
     * Validate header value for CRLF injection and other attacks.
     */
    public function validateHeaderValue(mixed $value): void
    {
        $stringValue = is_array($value) ? implode(', ', $value) : (string) $value;

        if (strlen($stringValue) > self::MAX_HEADER_VALUE_LENGTH) {
            throw new InvalidArgumentException('Header value too long (max ' . self::MAX_HEADER_VALUE_LENGTH . ' chars)');
        }

        // Critical: Prevent CRLF injection attacks
        if (preg_match(self::CRLF_PATTERN, $stringValue)) {
            throw new InvalidArgumentException('Header value contains CRLF characters (potential injection attack)');
        }

        // Check for dangerous control characters
        if (preg_match(self::CONTROL_CHARS_PATTERN, $stringValue)) {
            throw new InvalidArgumentException('Header value contains invalid control characters');
        }

        // In strict mode, check for script injection patterns
        if ($this->strictMode && preg_match(self::SCRIPT_INJECTION_PATTERN, $stringValue)) {
            throw new InvalidArgumentException('Header value contains potentially dangerous script content');
        }
    }

    /**
     * Validate collection of headers for DoS protection.
     */
    public function validateHeaders(array $headers): void
    {
        if (count($headers) > self::MAX_HEADERS_COUNT) {
            throw new InvalidArgumentException('Too many headers (max ' . self::MAX_HEADERS_COUNT . ')');
        }

        $totalSize = 0;
        foreach ($headers as $name => $value) {
            $this->validateHeaderName((string) $name);
            $this->validateHeaderValue($value);
            
            // Track total header size for DoS protection
            $totalSize += strlen((string) $name) + strlen(is_array($value) ? implode(', ', $value) : (string) $value);
        }

        // Prevent DoS through large header sections
        if ($totalSize > 65536) { // 64KB limit for all headers combined
            throw new InvalidArgumentException('Total header size too large (DoS protection)');
        }
    }

    /**
     * Validate status code for HTTP compliance.
     */
    public function validateStatusCode(int $code): void
    {
        if ($code < 100 || $code >= 600) {
            throw new InvalidArgumentException('Invalid HTTP status code: ' . $code);
        }
    }

    /**
     * Validate reason phrase for security.
     */
    public function validateReasonPhrase(string $phrase): void
    {
        if (strlen($phrase) > self::MAX_REASON_PHRASE_LENGTH) {
            throw new InvalidArgumentException('Reason phrase too long (max ' . self::MAX_REASON_PHRASE_LENGTH . ' chars)');
        }

        // Check for CRLF injection
        if (preg_match(self::CRLF_PATTERN, $phrase)) {
            throw new InvalidArgumentException('Reason phrase contains CRLF characters');
        }

        // Check for control characters
        if (preg_match(self::CONTROL_CHARS_PATTERN, $phrase)) {
            throw new InvalidArgumentException('Reason phrase contains control characters');
        }
    }

    /**
     * Validate request target for security.
     */
    public function validateRequestTarget(string $target): void
    {
        if (strlen($target) === 0) {
            throw new InvalidArgumentException('Request target cannot be empty');
        }

        if (strlen($target) > self::MAX_URI_LENGTH) {
            throw new InvalidArgumentException('Request target too long');
        }

        // Check for dangerous patterns
        if (preg_match(self::SCRIPT_INJECTION_PATTERN, $target)) {
            throw new InvalidArgumentException('Request target contains dangerous content');
        }

        // Ensure it starts with / for origin-form or is * for asterisk-form
        if (!str_starts_with($target, '/') && $target !== '*' && !filter_var($target, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid request target format');
        }
    }

    /**
     * Check if URI structure is basically valid.
     */
    private function isValidUriStructure(string $uri): bool
    {
        // Basic URI structure validation
        return parse_url($uri) !== false;
    }

    /**
     * Validate host component for security.
     */
    private function validateHost(string $host): void
    {
        // Check for IPv4
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return;
        }

        // Check for IPv6
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return;
        }

        // Check for valid domain name
        if (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return;
        }

        throw new InvalidArgumentException('Invalid host format: ' . $host);
    }

    /**
     * Check if header name is potentially dangerous.
     */
    private function isDangerousHeaderName(string $name): bool
    {
        $dangerousHeaders = [
            'transfer-encoding', // Can be dangerous if manipulated
        ];

        return in_array(strtolower($name), $dangerousHeaders, true);
    }

    /**
     * Get security configuration.
     */
    public function getConfig(): array
    {
        return [
            'strict_mode' => $this->strictMode,
            'allowed_schemes' => $this->allowedSchemes,
            'limits' => [
                'max_uri_length' => self::MAX_URI_LENGTH,
                'max_header_name_length' => self::MAX_HEADER_NAME_LENGTH,
                'max_header_value_length' => self::MAX_HEADER_VALUE_LENGTH,
                'max_headers_count' => self::MAX_HEADERS_COUNT,
                'max_method_length' => self::MAX_METHOD_LENGTH,
                'max_reason_phrase_length' => self::MAX_REASON_PHRASE_LENGTH,
            ],
        ];
    }
}