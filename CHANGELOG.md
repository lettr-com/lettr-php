# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/), and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.3.0] - 2026-03-21

### Added
- Custom email headers support via `CustomHeaders` DTO, `EmailBuilder::headers()` and `EmailBuilder::addHeader()` methods
- Validation for max 10 headers and max 998 characters per header value

### Fixed
- Changed default `transactional` option in `EmailBuilder` from `false` to `true` to match the API default

## [0.1.3] - 2025-01-23

### Fixed

- Fixed API response parsing to unwrap `data` envelope

## [0.1.2] - 2025-01-23

### Fixed

- Fixed `from` field format to use `from` (email) and `from_name` (name) as separate fields

## [0.1.1] - 2025-01-23

### Fixed

- Fixed API base URL missing trailing slash causing incorrect endpoint URLs

## [0.1.0] - 2025-01-23

### Added

- Initial release of the Lettr PHP SDK
- **Email Service**
  - Send emails with HTML, plain text, or templates
  - Fluent `EmailBuilder` for composing emails
  - Support for attachments, CC, BCC, and reply-to
  - Email tracking and event history
  - List emails with filtering and pagination
- **Domain Service**
  - Create and manage sending domains
  - DNS verification status
  - DKIM configuration
- **Webhook Service**
  - Create, update, and delete webhooks
  - Support for multiple event types (delivered, bounced, opened, clicked, etc.)
  - Webhook authentication (Basic, Bearer)
- **Type-safe DTOs and Value Objects**
  - Strongly typed request/response objects
  - Value objects for EmailAddress, MessageId, DomainName, etc.
- **Exception Handling**
  - Specific exceptions for API errors (ValidationException, NotFoundException, etc.)
  - Error codes enum for programmatic error handling
- **PHP 8.4+ Support**
  - Modern PHP with strict types
  - Native enums for status values
