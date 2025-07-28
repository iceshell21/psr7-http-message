
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial release of PSR-7 HTTP Message implementation
- Full PSR-7 HTTP Message Interface compliance
- Complete PSR-17 HTTP Factory Interface implementation
- Modern PHP 8.3+ features (readonly classes, enums, match expressions)
- Immutable message objects with efficient cloning
- Memory-efficient stream handling
- Comprehensive HTTP status code enum with reason phrases
- HTTP method enum with safe string conversion
- Built-in JsonResponse and HtmlResponse classes
- SAPI emitter for response output
- Strict type declarations throughout
- Header collection with case-insensitive handling
- URI implementation with RFC 3986 compliance
- Server request implementation with superglobal support
- Uploaded file handling
- Comprehensive test coverage
- Static analysis with PHPStan
- Code style checking with PHP_CodeSniffer

### Security
- Input validation and sanitization
- Safe JSON encoding with error handling
- Proper stream resource management
