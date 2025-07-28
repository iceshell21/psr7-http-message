
# PSR-7 HTTP Message Implementation

A modern, high-performance PSR-7 HTTP Message implementation with PSR-17 HTTP Factories for PHP 8.3+.

## Features

- ✅ Full PSR-7 HTTP Message Interface compliance
- ✅ Complete PSR-17 HTTP Factory Interface implementation
- ✅ Modern PHP 8.3+ features (readonly classes, enums, match expressions)
- ✅ Immutable message objects
- ✅ Memory-efficient stream handling
- ✅ Comprehensive HTTP status code enum
- ✅ Built-in JSON and HTML response classes
- ✅ Strict type declarations throughout
- ✅ Extensive test coverage

## Installation

Install via Composer:

```bash
composer require iceshell21/psr7-http-message
```

## Requirements

- PHP 8.3 or higher
- PSR-7 HTTP Message interfaces
- PSR-17 HTTP Factory interfaces

## Quick Start

### Creating Requests

```php
use IceShell21\Psr7HttpMessage\Factory\RequestFactory;
use IceShell21\Psr7HttpMessage\Factory\StreamFactory;

$requestFactory = new RequestFactory();
$streamFactory = new StreamFactory();

// Simple GET request
$request = $requestFactory->createRequest('GET', 'https://api.example.com/users');

// POST request with JSON body
$body = $streamFactory->createStream(json_encode(['name' => 'John Doe']));
$request = $requestFactory->createRequest('POST', 'https://api.example.com/users')
    ->withHeader('Content-Type', 'application/json')
    ->withBody($body);
```

### Creating Responses

```php
use IceShell21\Psr7HttpMessage\Factory\ResponseFactory;
use IceShell21\Psr7HttpMessage\Response\JsonResponse;
use IceShell21\Psr7HttpMessage\Response\HtmlResponse;
use IceShell21\Psr7HttpMessage\Enum\HttpStatusCode;

$responseFactory = new ResponseFactory();

// Basic response
$response = $responseFactory->createResponse(200, 'OK');

// JSON response
$jsonResponse = new JsonResponse(['message' => 'Success'], HttpStatusCode::OK);

// HTML response
$htmlResponse = new HtmlResponse('<h1>Welcome</h1>', HttpStatusCode::OK);
```

### Working with URIs

```php
use IceShell21\Psr7HttpMessage\Factory\UriFactory;

$uriFactory = new UriFactory();
$uri = $uriFactory->createUri('https://example.com/path?query=value#fragment');

echo $uri->getScheme(); // 'https'
echo $uri->getHost();   // 'example.com'
echo $uri->getPath();   // '/path'
echo $uri->getQuery();  // 'query=value'
```

### Server Requests

```php
use IceShell21\Psr7HttpMessage\Factory\ServerRequestFactory;

$serverRequestFactory = new ServerRequestFactory();

// Create from globals
$serverRequest = $serverRequestFactory->createServerRequestFromGlobals();

// Access request data
$method = $serverRequest->getMethod();
$uri = $serverRequest->getUri();
$headers = $serverRequest->getHeaders();
$body = $serverRequest->getBody();
$queryParams = $serverRequest->getQueryParams();
$parsedBody = $serverRequest->getParsedBody();
```

## Advanced Usage

### Custom Stream Handling

```php
use IceShell21\Psr7HttpMessage\Factory\StreamFactory;

$streamFactory = new StreamFactory();

// Create from string
$stream = $streamFactory->createStream('Hello, World!');

// Create from file
$fileStream = $streamFactory->createStreamFromFile('/path/to/file.txt', 'r');

// Create from resource
$resource = fopen('php://temp', 'w+b');
$resourceStream = $streamFactory->createStreamFromResource($resource);
```

### HTTP Method Enum

```php
use IceShell21\Psr7HttpMessage\Enum\HttpMethod;

$method = HttpMethod::POST;
echo $method->value; // 'POST'

// Safe conversion from string
$method = HttpMethod::fromString('get'); // Returns HttpMethod::GET
```

### HTTP Status Codes

```php
use IceShell21\Psr7HttpMessage\Enum\HttpStatusCode;

$status = HttpStatusCode::NOT_FOUND;
echo $status->value; // 404
echo $status->getReasonPhrase(); // 'Not Found'
```

## Architecture

This library follows SOLID principles and modern PHP best practices:

- **Single Responsibility**: Each class has a single, well-defined purpose
- **Open/Closed**: Extensible through inheritance and composition
- **Liskov Substitution**: All implementations are substitutable for their interfaces
- **Interface Segregation**: Small, focused interfaces
- **Dependency Inversion**: Depends on abstractions, not concretions

### Key Components

- `Stream`: Memory-efficient stream implementation
- `Uri`: RFC 3986 compliant URI handling
- `Request`/`Response`: Immutable HTTP message objects
- `ServerRequest`: Server-side request with additional context
- `UploadedFile`: File upload handling
- Factories: PSR-17 compliant object creation
- Enums: Type-safe HTTP methods and status codes

## Testing

Run the test suite:

```bash
composer test
```

Run static analysis:

```bash
composer phpstan
```

Check code style:

```bash
composer cs-check
```

Fix code style:

```bash
composer cs-fix
```

## Contributing

Contributions are welcome! Please read our contributing guidelines and submit pull requests to our repository.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes and releases.
