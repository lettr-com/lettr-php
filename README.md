# Lettr PHP SDK

[![CI](https://github.com/TOPOL-io/lettr-php/actions/workflows/ci.yml/badge.svg)](https://github.com/TOPOL-io/lettr-php/actions/workflows/ci.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/lettr/lettr-php.svg)](https://packagist.org/packages/lettr/lettr-php)
[![Total Downloads](https://img.shields.io/packagist/dt/lettr/lettr-php.svg)](https://packagist.org/packages/lettr/lettr-php)
[![PHP Version](https://img.shields.io/packagist/php-v/lettr/lettr-php.svg)](https://packagist.org/packages/lettr/lettr-php)
[![License](https://img.shields.io/packagist/l/lettr/lettr-php.svg)](https://packagist.org/packages/lettr/lettr-php)

Official PHP SDK for the [Lettr](https://lettr.com) email API.

## Requirements

- PHP 8.4+
- Guzzle HTTP client 7.5+

## Installation

```bash
composer require lettr/lettr-php
```

## Quick Start

```php
use Lettr\Lettr;

$lettr = Lettr::client('your-api-key');

// Send an email
$response = $lettr->emails()->send(
    $lettr->emails()->create()
        ->from('sender@example.com', 'Sender Name')
        ->to(['recipient@example.com'])
        ->subject('Hello from Lettr')
        ->html('<h1>Hello!</h1><p>This is a test email.</p>')
);

echo $response->requestId; // Request ID for tracking
echo $response->accepted;  // Number of accepted recipients

// Sending quota (free tier teams only)
if ($response->quota !== null) {
    echo $response->quota->monthlyLimit;     // e.g. 3000
    echo $response->quota->monthlyRemaining; // e.g. 2500
    echo $response->quota->dailyLimit;       // e.g. 100
    echo $response->quota->dailyRemaining;   // e.g. 75
}
```

## Sending Emails

### Using the Email Builder (Recommended)

The fluent builder provides a clean API for constructing emails:

```php
$response = $lettr->emails()->send(
    $lettr->emails()->create()
        ->from('sender@example.com', 'Sender Name')
        ->to(['recipient@example.com'])
        ->cc(['cc@example.com'])
        ->bcc(['bcc@example.com'])
        ->replyTo('reply@example.com')
        ->subject('Welcome!')
        ->html('<h1>Welcome</h1>')
        ->text('Welcome (plain text fallback)')
        ->transactional()
        ->withClickTracking(true)
        ->withOpenTracking(true)
        ->metadata(['user_id' => '123', 'campaign' => 'welcome'])
        ->substitutionData(['name' => 'John', 'company' => 'Acme'])
        ->tag('welcome')
);
```

### Using SendEmailData DTO

For programmatic email construction:

```php
use Lettr\Dto\Email\SendEmailData;
use Lettr\Dto\Email\EmailOptions;
use Lettr\ValueObjects\EmailAddress;
use Lettr\ValueObjects\Subject;
use Lettr\Collections\EmailAddressCollection;

$email = new SendEmailData(
    from: new EmailAddress('sender@example.com', 'Sender'),
    to: EmailAddressCollection::from(['recipient@example.com']),
    subject: new Subject('Hello'),
    html: '<p>Email content</p>',
);

$response = $lettr->emails()->send($email);
```

### Quick Send Methods

For simple use cases:

The `from` parameter accepts a plain email string or an `EmailAddress` value object when you need a sender name:

```php
use Lettr\ValueObjects\EmailAddress;

// Pass a string — validated as an email address
$response = $lettr->emails()->sendHtml(
    from: 'sender@example.com',
    to: 'recipient@example.com',
    subject: 'Hello',
    html: '<p>HTML content</p>',
);

// Pass an EmailAddress — includes sender name
$response = $lettr->emails()->sendHtml(
    from: new EmailAddress('sender@example.com', 'Sender Name'),
    to: 'recipient@example.com',
    subject: 'Hello',
    html: '<p>HTML content</p>',
);

// Plain text email
$response = $lettr->emails()->sendText(
    from: 'sender@example.com',
    to: ['recipient1@example.com', 'recipient2@example.com'],
    subject: 'Hello',
    text: 'Plain text content',
);

// Template email — subject is optional; if omitted, the template's own subject is used
$response = $lettr->emails()->sendTemplate(
    from: 'sender@example.com',
    to: 'recipient@example.com',
    templateSlug: 'welcome-email',
    templateVersion: 2,
    projectId: 123,
    substitutionData: ['name' => 'John'],
);

// Override the template's subject
$response = $lettr->emails()->sendTemplate(
    from: 'sender@example.com',
    to: 'recipient@example.com',
    templateSlug: 'welcome-email',
    subject: 'Welcome!',
);
```

### Attachments

```php
use Lettr\Dto\Email\Attachment;

$email = $lettr->emails()->create()
    ->from('sender@example.com')
    ->to(['recipient@example.com'])
    ->subject('Document attached')
    ->html('<p>Please find the document attached.</p>')
    // From file path
    ->attachFile('/path/to/document.pdf')
    // With custom name and mime type
    ->attachFile('/path/to/file', 'custom-name.pdf', 'application/pdf')
    // From binary data
    ->attachData($binaryContent, 'report.csv', 'text/csv')
    // Using Attachment DTO
    ->attach(Attachment::fromFile('/path/to/image.png'));

$response = $lettr->emails()->send($email);
```

### Templates with Substitution Data

```php
$response = $lettr->emails()->send(
    $lettr->emails()->create()
        ->from('sender@example.com')
        ->to(['recipient@example.com'])
        ->useTemplate('order-confirmation', version: 1, projectId: 123)
        // subject() is optional when using a template — if omitted, the template must have a subject
        // defined, otherwise the API will return an error
        ->subject('Your Order #{{order_id}}')
        ->substitutionData([
            'order_id' => '12345',
            'customer_name' => 'John Doe',
            'items' => [
                ['name' => 'Product A', 'price' => 29.99],
                ['name' => 'Product B', 'price' => 49.99],
            ],
            'total' => 79.98,
        ])
);
```

### Custom Headers

You can add custom email headers (e.g. `X-Custom-ID`, `X-Entity-Ref-ID`) to your emails. Maximum 10 headers, each value up to 998 characters:

```php
$email = $lettr->emails()->create()
    ->from('sender@example.com')
    ->to(['recipient@example.com'])
    ->subject('Hello')
    ->html('<p>Content</p>')
    // Bulk set
    ->headers(['X-Custom-ID' => 'abc-123', 'X-Entity-Ref-ID' => 'order-456'])
    // Or add individually
    ->addHeader('X-Custom-ID', 'abc-123');
```

> **Note:** Some standard headers (e.g. `List-Unsubscribe` for non-transactional emails) may be overwritten by the email delivery provider. Use custom headers for application-specific headers like `X-Custom-ID`.

### Email Options

Emails are sent as **transactional by default**, matching the API's default behavior. For marketing emails, explicitly set `transactional(false)`:

```php
$email = $lettr->emails()->create()
    ->from('sender@example.com')
    ->to(['recipient@example.com'])
    ->subject('Newsletter')
    ->html($htmlContent)
    // Tracking
    ->withClickTracking(true)
    ->withOpenTracking(true)
    // Mark as marketing (non-transactional)
    ->transactional(false)
    // CSS inlining
    ->withInlineCss(true)
    // Template variable substitution
    ->withSubstitutions(true);
```

### Marketing Emails & Unsubscribe

When sending marketing emails (`transactional(false)`), the email provider automatically adds `List-Unsubscribe` and `List-Unsubscribe-Post` headers for compliance. To allow recipients to unsubscribe from your marketing emails:

1. **Add an unsubscribe link in your HTML** using the `data-msys-unsubscribe` attribute:

```html
<a data-msys-unsubscribe="1"
   href="https://yourapp.com/unsubscribe"
   title="Unsubscribe">Unsubscribe from these emails</a>
```

The `href` must use `https://` — when clicked, the user will be redirected to your URL. The actual unsubscribe handling should be done server-side by listening for webhook events.

2. **Listen for unsubscribe events** via webhooks — subscribe to `link_unsubscribe` and `list_unsubscribe` event types to process unsubscribes in your application.

## Documentation

Full guides, every method, and complete request/response details live in the docs:

📚 **[docs.lettr.com/quickstart/php](https://docs.lettr.com/quickstart/php/introduction)**

| Topic | Guide |
|-|-|
| Install & client setup | [Installation](https://docs.lettr.com/quickstart/php/installation) |
| Sending — HTML, text, templates, attachments, tracking, errors | [Sending Emails](https://docs.lettr.com/quickstart/php/sending-emails) |
| Managing templates & merge tags | [Templates](https://docs.lettr.com/quickstart/php/templates) |
| Add, verify, and manage sending domains | [Domains](https://docs.lettr.com/quickstart/php/domains) |
| Webhook endpoints for delivery & engagement events | [Webhooks](https://docs.lettr.com/quickstart/php/webhooks) |
| Lists, contacts, topics, properties, segments | [Audience](https://docs.lettr.com/quickstart/php/audience) |
| List, send, and schedule campaigns | [Campaigns](https://docs.lettr.com/quickstart/php/campaigns) |
| Endpoint reference (params & schemas) | [API Reference](https://docs.lettr.com/api-reference/introduction) |


## Health Check

```php
// Check API health (no authentication required)
$status = $lettr->health()->check();

echo $status->status;      // 'ok'
echo $status->timestamp;   // Timestamp object
echo $status->isHealthy(); // true/false

// Verify API key is valid and get team info
$auth = $lettr->health()->authCheck();

echo $auth->teamId;    // Your team ID
echo $auth->timestamp; // Timestamp object
```

## Value Objects

The SDK uses value objects for type safety and validation:

```php
use Lettr\ValueObjects\EmailAddress;
use Lettr\ValueObjects\DomainName;
use Lettr\ValueObjects\RequestId;
use Lettr\ValueObjects\Timestamp;

// Email addresses with optional name
$email = new EmailAddress('user@example.com', 'User Name');
echo $email->address;  // user@example.com
echo $email->name;     // User Name

// Domain names (validated)
$domain = new DomainName('example.com');

// Request IDs
$requestId = new RequestId('req_abc123');

// Timestamps
$timestamp = Timestamp::fromString('2024-01-15T10:30:00Z');
echo $timestamp->toIso8601();       // ISO 8601 string
$timestamp->value;                  // DateTimeImmutable instance (not echoable directly)
echo $timestamp->format('Y-m-d');   // Custom format
```

## Error Handling

```php
use Lettr\Exceptions\ApiException;
use Lettr\Exceptions\TransporterException;
use Lettr\Exceptions\ValidationException;
use Lettr\Exceptions\NotFoundException;
use Lettr\Exceptions\UnauthorizedException;
use Lettr\Exceptions\ForbiddenException;
use Lettr\Exceptions\ConflictException;
use Lettr\Exceptions\QuotaExceededException;
use Lettr\Exceptions\RateLimitException;
use Lettr\Exceptions\InvalidValueException;

try {
    $response = $lettr->emails()->send($email);
} catch (ValidationException $e) {
    // Invalid request data (422)
    echo "Validation failed: " . $e->getMessage();
} catch (UnauthorizedException $e) {
    // Invalid API key (401)
    echo "Authentication failed: " . $e->getMessage();
} catch (ForbiddenException $e) {
    // Insufficient API key permissions (403)
    echo "Forbidden: " . $e->getMessage();
} catch (NotFoundException $e) {
    // Resource not found (404)
    echo "Not found: " . $e->getMessage();
} catch (ConflictException $e) {
    // Resource conflict (409)
    echo "Conflict: " . $e->getMessage();
} catch (QuotaExceededException $e) {
    // Sending quota exceeded (429) - monthly or daily limit reached
    echo "Quota exceeded: " . $e->getMessage();

    if ($e->quota !== null) {
        echo $e->quota->monthlyLimit;       // Total monthly limit
        echo $e->quota->monthlyRemaining;   // 0 when exhausted
        echo $e->quota->monthlyReset;       // Unix timestamp - start of next month
        echo $e->quota->dailyLimit;         // Total daily limit
        echo $e->quota->dailyRemaining;     // 0 when exhausted
        echo $e->quota->dailyReset;         // Unix timestamp - tomorrow midnight UTC
    }
} catch (RateLimitException $e) {
    // API rate limit exceeded (429) - too many requests per second
    echo "Rate limited: " . $e->getMessage();

    if ($e->rateLimit !== null) {
        echo $e->rateLimit->limit;      // Max requests per second
        echo $e->rateLimit->remaining;  // Remaining requests
        echo $e->rateLimit->reset;      // Unix timestamp when limit resets
    }
    if ($e->retryAfter !== null) {
        sleep($e->retryAfter);          // Seconds to wait before retrying
    }
} catch (ApiException $e) {
    // Other API errors
    echo "API error ({$e->getCode()}): " . $e->getMessage();
} catch (TransporterException $e) {
    // Network/transport errors
    echo "Network error: " . $e->getMessage();
} catch (InvalidValueException $e) {
    // Invalid value object (e.g., invalid email format)
    echo "Invalid value: " . $e->getMessage();
}
```

### Rate Limits

The API enforces a rate limit of **3 requests per second** per team, shared across all API keys. Rate limit headers are included in every authenticated API response:

| Header | Description |
|--------|-------------|
| `X-Ratelimit-Limit` | Maximum requests per second |
| `X-Ratelimit-Remaining` | Remaining requests in current window |
| `X-Ratelimit-Reset` | Unix timestamp when the limit resets |
| `Retry-After` | Seconds to wait (only on 429 responses) |

You can read rate limit info after any API call:

```php
$lettr->domains()->list();

$rateLimit = $lettr->lastRateLimit();

if ($rateLimit !== null) {
    echo $rateLimit->limit;     // 3
    echo $rateLimit->remaining; // 2
    echo $rateLimit->reset;     // Unix timestamp
}
```

### Sending Quotas

Free tier teams have monthly and daily sending limits. Quota headers are included in send email responses:

| Header | Description |
|--------|-------------|
| `X-Monthly-Limit` | Total monthly email limit |
| `X-Monthly-Remaining` | Remaining emails this month |
| `X-Monthly-Reset` | Unix timestamp when monthly quota resets |
| `X-Daily-Limit` | Total daily email limit |
| `X-Daily-Remaining` | Remaining emails today |
| `X-Daily-Reset` | Unix timestamp when daily quota resets |

Quota information is available on successful responses via `$response->quota` and on quota exceeded errors via the `QuotaExceededException`.

## Development

### Install Dependencies

```bash
composer install
```

### Code Style

This project uses Laravel Pint for code style:

```bash
composer lint
```

### Static Analysis

This project uses PHPStan at level 8:

```bash
composer analyse
```

### Testing

This project uses Pest for testing:

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

MIT License. See [LICENSE](LICENSE) for details.
