<?php

declare(strict_types=1);

namespace IceShell21\Psr7HttpMessage;

use InvalidArgumentException;
use IceShell21\Psr7HttpMessage\Security\SecurityValidator;
use Psr\Http\Message\UriInterface;

/**
 * RFC 3986 compliant URI implementation with efficient parsing and validation.
 * Enhanced with PHP 8.4+ features and security validation.
 */
final class Uri implements UriInterface
{
    private const SCHEMES = ['http' => 80, 'https' => 443, 'ftp' => 21, 'ftps' => 990];
    private const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';
    private const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';
    
    private ?SecurityValidator $validator = null;
    
    // Property hook for computed authority (PHP 8.4+)
    public string $computedAuthority {
        get => $this->getAuthority();
    }
    
    // Asymmetric visibility for URI statistics (PHP 8.4+)
    public private(set) array $uriStats = [
        'parsed' => false,
        'validated' => false,
        'length' => 0,
    ];

    public function __construct(
        private string $scheme = '',
        private string $userInfo = '',
        private string $host = '',
        private ?int $port = null,
        private string $path = '',
        private string $query = '',
        private string $fragment = ''
    ) {
        // Initialize security validator
        $this->validator = new SecurityValidator(strictMode: false);
        
        if (func_num_args() === 1 && is_string($scheme)) {
            $this->parseUri($scheme);
            $this->uriStats['parsed'] = true;
        }
        
        // Validate components if provided
        if ($this->scheme !== '' || $this->host !== '') {
            $this->validateComponents();
            $this->uriStats['validated'] = true;
        }
        
        $this->uriStats['length'] = strlen($this->__toString());
    }

    private function parseUri(string $uri): void
    {
        if ($uri === '') {
            return;
        }
        
        // Security validation
        $this->validator?->validateUri($uri);

        $parts = parse_url($uri);
        if ($parts === false) {
            throw new InvalidArgumentException("Invalid URI: $uri");
        }

        $this->scheme = isset($parts['scheme']) ? mb_strtolower($parts['scheme'], 'UTF-8') : '';
        $this->userInfo = $this->buildUserInfo($parts);
        $this->host = isset($parts['host']) ? mb_strtolower($parts['host'], 'UTF-8') : '';
        $this->port = isset($parts['port']) ? $this->filterPort($parts['port']) : null;
        $this->path = isset($parts['path']) ? $this->filterPath($parts['path']) : '';
        $this->query = isset($parts['query']) ? $this->filterQueryAndFragment($parts['query']) : '';
        $this->fragment = isset($parts['fragment']) ? $this->filterQueryAndFragment($parts['fragment']) : '';
    }

    private function buildUserInfo(array $parts): string
    {
        if (!isset($parts['user'])) {
            return '';
        }

        $userInfo = $parts['user'];
        if (isset($parts['pass'])) {
            $userInfo .= ':' . $parts['pass'];
        }

        return $userInfo;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        if ($this->host === '') {
            return '';
        }

        $authority = $this->host;

        if ($this->userInfo !== '') {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme(string $scheme): static
    {
        $scheme = mb_strtolower($scheme, 'UTF-8');
        if ($this->scheme === $scheme) {
            return $this;
        }

        $new = clone $this;
        $new->scheme = $scheme;
        $new->port = $new->filterPort($new->port);
        return $new;
    }

    public function withUserInfo(string $user, ?string $password = null): static
    {
        $info = $user . ($password !== null ? ':' . $password : '');
        
        if ($this->userInfo === $info) {
            return $this;
        }

        $new = clone $this;
        $new->userInfo = $info;
        return $new;
    }

    public function withHost(string $host): static
    {
        $host = mb_strtolower($host, 'UTF-8');
        if ($this->host === $host) {
            return $this;
        }

        $new = clone $this;
        $new->host = $host;
        return $new;
    }

    public function withPort(?int $port): static
    {
        $port = $this->filterPort($port);
        if ($this->port === $port) {
            return $this;
        }

        $new = clone $this;
        $new->port = $port;
        return $new;
    }

    public function withPath(string $path): static
    {
        $path = $this->filterPath($path);
        if ($this->path === $path) {
            return $this;
        }

        $new = clone $this;
        $new->path = $path;
        return $new;
    }

    public function withQuery(string $query): static
    {
        $query = $this->filterQueryAndFragment($query);
        if ($this->query === $query) {
            return $this;
        }

        $new = clone $this;
        $new->query = $query;
        return $new;
    }

    public function withFragment(string $fragment): static
    {
        $fragment = $this->filterQueryAndFragment($fragment);
        if ($this->fragment === $fragment) {
            return $this;
        }

        $new = clone $this;
        $new->fragment = $fragment;
        return $new;
    }

    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }

        $authority = $this->getAuthority();
        if ($authority !== '') {
            $uri .= '//' . $authority;
        }

        $uri .= $this->path;

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    private function filterPort(?int $port): ?int
    {
        if ($port === null) {
            return null;
        }

        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException('Invalid port: ' . $port);
        }

        return $this->isStandardPort($this->scheme, $port) ? null : $port;
    }

    private function isStandardPort(string $scheme, int $port): bool
    {
        return isset(self::SCHEMES[$scheme]) && self::SCHEMES[$scheme] === $port;
    }

    private function filterPath(string $path): string
    {
        return preg_replace_callback(
            '/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/]++|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'rawurlencodeMatchZero'],
            $path
        );
    }

    private function filterQueryAndFragment(string $str): string
    {
        return preg_replace_callback(
            '/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'rawurlencodeMatchZero'],
            $str
        );
    }

    private function rawurlencodeMatchZero(array $match): string
    {
        return rawurlencode($match[0]);
    }
    
    /**
     * Validate URI components for security.
     */
    private function validateComponents(): void
    {
        if ($this->scheme !== '') {
            $this->validator?->validateUri($this->scheme . '://' . $this->host);
        }
    }
    
    /**
     * Create URI from string with validation.
     */
    public static function fromString(string $uri): self
    {
        return new self($uri);
    }
    
    /**
     * Get URI security and performance statistics.
     */
    public function getStats(): array
    {
        return [
            'uri_stats' => $this->uriStats,
            'scheme' => $this->scheme,
            'has_authority' => $this->getAuthority() !== '',
            'has_userinfo' => $this->userInfo !== '',
            'host_type' => $this->getHostType(),
            'default_port' => $this->isStandardPort($this->scheme, $this->port ?? 0),
            'path_segments' => count(array_filter(explode('/', $this->path))),
            'query_params' => $this->query !== '' ? count(parse_str($this->query, $params) ?: []) : 0,
            'has_fragment' => $this->fragment !== '',
            'total_length' => $this->uriStats['length'],
        ];
    }
    
    private function getHostType(): string
    {
        if ($this->host === '') {
            return 'none';
        }
        
        if (filter_var($this->host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return 'ipv4';
        }
        
        if (filter_var($this->host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return 'ipv6';
        }
        
        return 'domain';
    }
}
