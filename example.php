<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use IceShell21\Psr7HttpMessage\Factory\RequestFactory;
use IceShell21\Psr7HttpMessage\Factory\ResponseFactory;
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

echo "=== Example completed successfully! ===\n";
