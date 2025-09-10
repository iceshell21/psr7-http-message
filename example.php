<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use IceShell21\Psr7HttpMessage\Factory\RequestFactory;
use IceShell21\Psr7HttpMessage\Factory\ResponseFactory;
use IceShell21\Psr7HttpMessage\Factory\ServerRequestFactory;
use IceShell21\Psr7HttpMessage\Factory\StreamFactory;
use IceShell21\Psr7HttpMessage\Factory\UriFactory;
use IceShell21\Psr7HttpMessage\Response\JsonResponse;
use IceShell21\Psr7HttpMessage\Response\HtmlResponse;
use IceShell21\Psr7HttpMessage\Enum\HttpStatusCode;

echo "=== PSR-7 HTTP Message Implementation Example ===\n\n";

// 1. Create factories
$requestFactory = new RequestFactory();
$responseFactory = new ResponseFactory();
$streamFactory = new StreamFactory();
$uriFactory = new UriFactory();

// 2. Create a URI
$uri = $uriFactory->createUri('https://api.example.com/users?page=1');
echo "URI: " . $uri . "\n";
echo "Scheme: " . $uri->getScheme() . "\n";
echo "Host: " . $uri->getHost() . "\n";
echo "Path: " . $uri->getPath() . "\n";
echo "Query: " . $uri->getQuery() . "\n\n";

// 3. Create a request
$request = $requestFactory->createRequest('POST', $uri);
$body = $streamFactory->createStream(json_encode(['name' => 'John Doe', 'email' => 'john@example.com']));
$request = $request
    ->withHeader('Content-Type', 'application/json')
    ->withHeader('Accept', 'application/json')
    ->withBody($body);

echo "Request Method: " . $request->getMethod() . "\n";
echo "Request URI: " . $request->getUri() . "\n";
echo "Request Headers:\n";
foreach ($request->getHeaders() as $name => $values) {
    echo "  $name: " . implode(', ', $values) . "\n";
}
echo "Request Body: " . $request->getBody() . "\n\n";

// 4. Create responses
$basicResponse = $responseFactory->createResponse(200, 'OK');
echo "Basic Response Status: " . $basicResponse->getStatusCode() . " " . $basicResponse->getReasonPhrase() . "\n\n";

// 5. JSON Response
$jsonData = [
    'status' => 'success',
    'data' => [
        'id' => 123,
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ],
    'timestamp' => date('c')
];

$jsonResponse = new JsonResponse($jsonData, HttpStatusCode::CREATED);
echo "JSON Response Status: " . $jsonResponse->getStatusCode() . " " . $jsonResponse->getReasonPhrase() . "\n";
echo "JSON Response Content-Type: " . $jsonResponse->getHeaderLine('Content-Type') . "\n";
echo "JSON Response Body: " . $jsonResponse->getBody() . "\n\n";

// 6. HTML Response
$html = '<!DOCTYPE html>
<html>
<head>
    <title>Welcome</title>
</head>
<body>
    <h1>Welcome to our API</h1>
    <p>User created successfully!</p>
</body>
</html>';

$htmlResponse = new HtmlResponse($html, HttpStatusCode::OK);
echo "HTML Response Status: " . $htmlResponse->getStatusCode() . " " . $htmlResponse->getReasonPhrase() . "\n";
echo "HTML Response Content-Type: " . $htmlResponse->getHeaderLine('Content-Type') . "\n";
echo "HTML Response Body Length: " . strlen((string) $htmlResponse->getBody()) . " bytes\n\n";

// 7. Stream operations
$stream = $streamFactory->createStream('Hello, PSR-7 World!');
echo "Stream Content: " . $stream . "\n";
echo "Stream Size: " . $stream->getSize() . " bytes\n";
echo "Stream is readable: " . ($stream->isReadable() ? 'Yes' : 'No') . "\n";
echo "Stream is writable: " . ($stream->isWritable() ? 'Yes' : 'No') . "\n";
echo "Stream is seekable: " . ($stream->isSeekable() ? 'Yes' : 'No') . "\n\n";

// 8. Demonstrate immutability
$originalResponse = $responseFactory->createResponse(200);
$modifiedResponse = $originalResponse
    ->withStatus(404, 'Not Found')
    ->withHeader('X-Custom-Header', 'Custom Value');

echo "Original Response Status: " . $originalResponse->getStatusCode() . "\n";
echo "Modified Response Status: " . $modifiedResponse->getStatusCode() . "\n";
echo "Modified Response has custom header: " . ($modifiedResponse->hasHeader('X-Custom-Header') ? 'Yes' : 'No') . "\n";
echo "Original Response has custom header: " . ($originalResponse->hasHeader('X-Custom-Header') ? 'Yes' : 'No') . "\n\n";

// 9. Server Request Creation - NEW STATIC API
echo "=== Server Request Creation ===\n";

// Установим некоторые переменные для демонстрации
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_HOST'] = 'api.example.com';
$_SERVER['REQUEST_URI'] = '/users/create';
$_SERVER['QUERY_STRING'] = 'debug=1';
$_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
$_SERVER['HTTP_ACCEPT'] = 'application/json';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_GET['debug'] = '1';
$_POST['name'] = 'John Doe';
$_COOKIE['session'] = 'abc123';

// Новый статический метод (рекомендуемый)
$serverRequest = ServerRequestFactory::fromGlobals();
echo "Static method: Created server request\n";
echo "Method: " . $serverRequest->getMethod() . "\n";
echo "URI: " . $serverRequest->getUri() . "\n";
echo "Query Params: " . json_encode($serverRequest->getQueryParams()) . "\n";
echo "Server Params (partial): " . $serverRequest->getServerParams()['HTTP_HOST'] . "\n";

// Старый способ (для сравнения)
$factory = new ServerRequestFactory();
$legacyRequest = $factory->createServerRequestFromGlobals();
echo "\nInstance method: Created server request\n";
echo "Method: " . $legacyRequest->getMethod() . "\n";
echo "URI: " . $legacyRequest->getUri() . "\n";
echo "Query Params: " . json_encode($legacyRequest->getQueryParams()) . "\n";

// Сравнение объектов
echo "\nRequests are equivalent: " . 
     ($serverRequest->getMethod() === $legacyRequest->getMethod() && 
      (string)$serverRequest->getUri() === (string)$legacyRequest->getUri() ? 'Yes' : 'No') . "\n\n";

// 10. Performance Comparison
echo "=== Performance Comparison ===\n";

// Тест производительности
$iterations = 1000;

// Статический метод
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    ServerRequestFactory::fromGlobals();
}
$staticTime = microtime(true) - $start;

// Экземплярный метод
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $factory = new ServerRequestFactory();
    $factory->createServerRequestFromGlobals();
}
$instanceTime = microtime(true) - $start;

echo "Static method time: " . number_format($staticTime * 1000, 2) . "ms\n";
echo "Instance method time: " . number_format($instanceTime * 1000, 2) . "ms\n";
echo "Performance improvement: " . number_format(($instanceTime - $staticTime) / $instanceTime * 100, 1) . "%\n\n";

// 11. Real-world Usage Examples
echo "=== Real-world Usage Examples ===\n";

// Пример 1: Простой HTTP обработчик
function handleRequest(): void {
    $request = ServerRequestFactory::fromGlobals();
    
    if ($request->getMethod() === 'POST') {
        echo "Handling POST request to: " . $request->getUri()->getPath() . "\n";
    } else {
        echo "Handling GET request to: " . $request->getUri()->getPath() . "\n";
    }
}

// Пример 2: Middleware pipeline
function createMiddlewarePipeline() {
    return ServerRequestFactory::fromGlobals()
        ->withAttribute('middleware', 'processed')
        ->withAttribute('timestamp', time());
}

handleRequest();
$processedRequest = createMiddlewarePipeline();
echo "Middleware processed request with attributes: " . 
     implode(', ', array_keys($processedRequest->getAttributes())) . "\n\n";

echo "=== Example completed successfully! ===\n";
