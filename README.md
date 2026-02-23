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

// Template email
$response = $lettr->emails()->sendTemplate(
    from: 'sender@example.com',
    to: 'recipient@example.com',
    subject: 'Welcome!',
    templateSlug: 'welcome-email',
    templateVersion: 2,
    projectId: 123,
    substitutionData: ['name' => 'John'],
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
        ->subject('Your Order #{{order_id}}')
        ->useTemplate('order-confirmation', version: 1, projectId: 123)
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

### Email Options

```php
$email = $lettr->emails()->create()
    ->from('sender@example.com')
    ->to(['recipient@example.com'])
    ->subject('Newsletter')
    ->html($htmlContent)
    // Tracking
    ->withClickTracking(true)
    ->withOpenTracking(true)
    // Mark as transactional (bypasses unsubscribe lists)
    ->transactional(false)
    // CSS inlining
    ->withInlineCss(true)
    // Template variable substitution
    ->withSubstitutions(true);
```

## Domain Management

### List Domains

```php
$domains = $lettr->domains()->list();

foreach ($domains as $domain) {
    echo $domain->domain;                    // example.com
    echo $domain->status->value;             // 'pending', 'approved'
    echo $domain->canSend;                   // true/false
    echo $domain->dkimStatus->value;         // 'valid', 'invalid', etc.
    echo $domain->returnPathStatus->value;   // 'valid', 'invalid', etc.
}
```

### Add a Domain

```php
use Lettr\ValueObjects\DomainName;

$result = $lettr->domains()->create('example.com');
// or
$result = $lettr->domains()->create(new DomainName('example.com'));

echo $result->domain;       // example.com
echo $result->status;       // DomainStatus::Pending
echo $result->statusLabel;  // "Pending Review"

// DKIM configuration
if ($result->dkim !== null) {
    echo $result->dkim->selector;      // DKIM selector (e.g. "scph0226")
    echo $result->dkim->publicKey;     // DKIM public key
    echo $result->dkim->headers;       // Signed headers (e.g. "from:to:subject:date")
    echo $result->dkim->signingDomain; // Signing domain
}
```

### Get Domain Details

```php
$domain = $lettr->domains()->get('example.com');

echo $domain->domain;
echo $domain->status;
echo $domain->canSend;
echo $domain->dkimStatus->label();   // DnsStatus enum
echo $domain->cnameStatus->label();  // DnsStatus enum
echo $domain->dmarcStatus->label();  // DnsStatus enum
echo $domain->trackingDomain;
echo $domain->createdAt;
echo $domain->verifiedAt;

// DKIM configuration (if available)
if ($domain->dkim !== null) {
    echo $domain->dkim->selector;
    echo $domain->dkim->publicKey;
    echo $domain->dkim->headers;
    echo $domain->dkim->recordName('example.com'); // Full DNS record name
    echo $domain->dkim->recordValue();              // Full DNS record value
}
```

### Verify Domain DNS

```php
$verification = $lettr->domains()->verify('example.com');

if ($verification->isFullyVerified()) {
    echo "Domain is ready to send!";
} else {
    // Check individual record statuses
    echo $verification->dkimStatus->label();   // "Valid", "Invalid", "Missing", etc.
    echo $verification->cnameStatus->label();
    echo $verification->dmarcStatus->label();
    echo $verification->spfStatus->label();

    // DNS record errors
    if ($verification->hasErrors()) {
        foreach ($verification->errors() as $type => $error) {
            echo "$type: $error";
        }
    }

    // DMARC details
    if ($verification->dmarc !== null) {
        echo $verification->dmarc->status->label();
        echo $verification->dmarc->policy;
        echo $verification->dmarc->coveredByParentPolicy ? 'Yes' : 'No';
    }

    // SPF details
    if ($verification->spf !== null) {
        echo $verification->spf->status->label();
        echo $verification->spf->record;
        echo $verification->spf->includesSparkpost ? 'Yes' : 'No';
    }
}
```

### Delete a Domain

```php
$lettr->domains()->delete('example.com');
```

## Webhooks

### List Webhooks

```php
$webhooks = $lettr->webhooks()->list();

foreach ($webhooks as $webhook) {
    echo $webhook->id;
    echo $webhook->name;
    echo $webhook->url;
    echo $webhook->enabled;
    echo $webhook->authType->value;  // 'none', 'basic', 'oauth2'

    // Event types this webhook listens to
    foreach ($webhook->eventTypes as $eventType) {
        echo $eventType->value;  // 'delivery', 'bounce', 'open', etc.
    }

    // Health check
    if ($webhook->isFailing()) {
        echo "Last error: " . $webhook->lastError;
    }
}
```

### Get Webhook Details

```php
$webhook = $lettr->webhooks()->get('webhook-id');

echo $webhook->name;
echo $webhook->url;
echo $webhook->lastStatus?->value;
echo $webhook->lastTriggeredAt;

// Check if webhook listens to specific events
if ($webhook->listensTo(EventType::Bounce)) {
    echo "Webhook receives bounce notifications";
}
```

## Templates

### List Templates

```php
use Lettr\Dto\Template\ListTemplatesFilter;

// List all templates
$response = $lettr->templates()->list();

foreach ($response->templates as $template) {
    echo $template->id;
    echo $template->name;
    echo $template->slug;
    echo $template->projectId;
}

// With pagination
$filter = ListTemplatesFilter::create()
    ->projectId(123)
    ->perPage(20)
    ->page(2);

$response = $lettr->templates()->list($filter);
```

### Get Template Details

```php
$template = $lettr->templates()->get('welcome-email');

echo $template->id;
echo $template->name;
echo $template->slug;
echo $template->html;
echo $template->json;
echo $template->activeVersion;
echo $template->versionsCount;

// With specific project
$template = $lettr->templates()->get('welcome-email', projectId: 123);
```

### Create a Template

```php
use Lettr\Dto\Template\CreateTemplateData;

// With HTML content
$template = $lettr->templates()->create(new CreateTemplateData(
    name: 'My Template',
    slug: 'my-template',        // optional, auto-generated if not provided
    projectId: 123,             // optional
    folderId: 5,                // optional
    html: '<html>...</html>',   // provide html OR json, not both
));

// Or with TOPOL.io JSON format
$template = $lettr->templates()->create(new CreateTemplateData(
    name: 'My Template',
    json: '{"blocks":[]}',      // TOPOL.io editor JSON
));

echo $template->id;
echo $template->name;
echo $template->slug;
echo $template->projectId;
echo $template->folderId;
echo $template->activeVersion;

// Merge tags extracted from the template
foreach ($template->mergeTags as $tag) {
    echo $tag->key;
    echo $tag->required;
}
```

### Delete a Template

```php
$lettr->templates()->delete('my-template');

// With specific project
$lettr->templates()->delete('my-template', projectId: 123);
```

### Get Merge Tags

Retrieve merge tags (template variables) from a template:

```php
$response = $lettr->templates()->getMergeTags('welcome-email');

echo $response->projectId;
echo $response->templateSlug;
echo $response->version;

foreach ($response->mergeTags as $tag) {
    echo $tag->key;       // e.g., 'user_name'
    echo $tag->required;  // true/false
    echo $tag->type;      // e.g., 'string', 'object'

    // Nested tags (for objects)
    if ($tag->children !== null) {
        foreach ($tag->children as $child) {
            echo $child->key;   // e.g., 'first_name'
            echo $child->type;  // e.g., 'string'
        }
    }
}

// With specific project and version
$response = $lettr->templates()->getMergeTags(
    'welcome-email',
    projectId: 123,
    version: 2,
);
```

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
echo $timestamp->value;             // DateTimeImmutable instance
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
