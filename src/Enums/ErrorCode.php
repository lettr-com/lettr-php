<?php

declare(strict_types=1);

namespace Lettr\Enums;

/**
 * API error codes.
 */
enum ErrorCode: string
{
    case ValidationError = 'validation_error';
    case InvalidDomain = 'invalid_domain';
    case UnconfiguredDomain = 'unconfigured_domain';
    case DomainNotFound = 'domain_not_found';
    case DomainNotVerified = 'domain_not_verified';
    case DomainAlreadyExists = 'domain_already_exists';
    case ResourceAlreadyExists = 'resource_already_exists';
    case WebhookNotFound = 'webhook_not_found';
    case TemplateNotFound = 'template_not_found';
    case NotFound = 'not_found';
    case InvalidApiKey = 'invalid_api_key';
    case RateLimitExceeded = 'rate_limit_exceeded';
    case QuotaExceeded = 'quota_exceeded';
    case DailyQuotaExceeded = 'daily_quota_exceeded';
    case InternalError = 'internal_error';
    case InvalidRecipient = 'invalid_recipient';
    case MessageTooLarge = 'message_too_large';
    case AttachmentTooLarge = 'attachment_too_large';
    case InsufficientScope = 'insufficient_scope';
    case SendError = 'send_error';
    case RetrievalError = 'retrieval_error';
    case TransmissionFailed = 'transmission_failed';
    case ScheduleCancellationFailed = 'schedule_cancellation_failed';

    /**
     * Get a human-readable message for the error code.
     */
    public function message(): string
    {
        return match ($this) {
            self::ValidationError => 'The request contains invalid data.',
            self::InvalidDomain => 'The specified domain is invalid.',
            self::UnconfiguredDomain => 'The sending domain is not configured for your account.',
            self::DomainNotFound => 'The specified domain was not found.',
            self::DomainNotVerified => 'The domain has not been verified.',
            self::DomainAlreadyExists => 'The domain already exists.',
            self::ResourceAlreadyExists => 'The resource already exists.',
            self::WebhookNotFound => 'The specified webhook was not found.',
            self::TemplateNotFound => 'The specified template was not found.',
            self::NotFound => 'The requested resource was not found.',
            self::InvalidApiKey => 'The API key is invalid.',
            self::RateLimitExceeded => 'Rate limit exceeded. Please try again later.',
            self::QuotaExceeded => 'Sending quota exceeded. Upgrade your plan to continue sending.',
            self::DailyQuotaExceeded => 'Daily sending quota exceeded. Please try again tomorrow.',
            self::InternalError => 'An internal error occurred.',
            self::InvalidRecipient => 'One or more recipients are invalid.',
            self::MessageTooLarge => 'The message exceeds the maximum size limit.',
            self::AttachmentTooLarge => 'One or more attachments exceed the size limit.',
            self::InsufficientScope => 'Your API key does not have the required permissions for this action.',
            self::SendError => 'Failed to submit the message to the mail service.',
            self::RetrievalError => 'Failed to retrieve the requested data from the upstream service.',
            self::TransmissionFailed => 'The transmission could not be completed.',
            self::ScheduleCancellationFailed => 'The scheduled transmission could not be cancelled.',
        };
    }

    /**
     * Check if this is a client error.
     */
    public function isClientError(): bool
    {
        return in_array($this, [
            self::ValidationError,
            self::InvalidDomain,
            self::UnconfiguredDomain,
            self::DomainNotFound,
            self::DomainNotVerified,
            self::DomainAlreadyExists,
            self::ResourceAlreadyExists,
            self::WebhookNotFound,
            self::TemplateNotFound,
            self::NotFound,
            self::InvalidApiKey,
            self::InsufficientScope,
            self::InvalidRecipient,
            self::MessageTooLarge,
            self::AttachmentTooLarge,
            self::ScheduleCancellationFailed,
        ], true);
    }

    /**
     * Check if this is a server error.
     */
    public function isServerError(): bool
    {
        return in_array($this, [
            self::InternalError,
            self::SendError,
            self::RetrievalError,
            self::TransmissionFailed,
        ], true);
    }

    /**
     * Check if this is a rate limit error.
     */
    public function isRateLimitError(): bool
    {
        return $this === self::RateLimitExceeded;
    }

    /**
     * Check if this is a quota error.
     */
    public function isQuotaError(): bool
    {
        return in_array($this, [
            self::QuotaExceeded,
            self::DailyQuotaExceeded,
        ], true);
    }
}
