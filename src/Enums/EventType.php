<?php

declare(strict_types=1);

namespace Lettr\Enums;

/**
 * Email event types.
 */
enum EventType: string
{
    case Injection = 'injection';
    case Delivery = 'delivery';
    case Bounce = 'bounce';
    case Delay = 'delay';
    case PolicyRejection = 'policy_rejection';
    case OutOfBand = 'out_of_band';
    case Open = 'open';
    case InitialOpen = 'initial_open';
    case Click = 'click';
    case AmpOpen = 'amp_open';
    case AmpInitialOpen = 'amp_initial_open';
    case AmpClick = 'amp_click';
    case GenerationFailure = 'generation_failure';
    case GenerationRejection = 'generation_rejection';
    case SpamComplaint = 'spam_complaint';
    case ListUnsubscribe = 'list_unsubscribe';
    case LinkUnsubscribe = 'link_unsubscribe';

    /**
     * Get a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Injection => 'Injection',
            self::Delivery => 'Delivery',
            self::Bounce => 'Bounce',
            self::Delay => 'Delay',
            self::PolicyRejection => 'Policy Rejection',
            self::OutOfBand => 'Out of Band',
            self::Open => 'Open',
            self::InitialOpen => 'Initial Open',
            self::Click => 'Click',
            self::AmpOpen => 'AMP Open',
            self::AmpInitialOpen => 'AMP Initial Open',
            self::AmpClick => 'AMP Click',
            self::GenerationFailure => 'Generation Failure',
            self::GenerationRejection => 'Generation Rejection',
            self::SpamComplaint => 'Spam Complaint',
            self::ListUnsubscribe => 'List Unsubscribe',
            self::LinkUnsubscribe => 'Link Unsubscribe',
        };
    }

    /**
     * Check if this is a successful delivery event.
     */
    public function isSuccess(): bool
    {
        return in_array($this, [self::Injection, self::Delivery], true);
    }

    /**
     * Check if this is a failure event.
     */
    public function isFailure(): bool
    {
        return in_array($this, [
            self::Bounce,
            self::PolicyRejection,
            self::GenerationFailure,
            self::GenerationRejection,
        ], true);
    }

    /**
     * Check if this is an engagement event.
     */
    public function isEngagement(): bool
    {
        return in_array($this, [
            self::Open,
            self::InitialOpen,
            self::Click,
            self::AmpOpen,
            self::AmpInitialOpen,
            self::AmpClick,
        ], true);
    }

    /**
     * Check if this is an unsubscribe event.
     */
    public function isUnsubscribe(): bool
    {
        return in_array($this, [
            self::ListUnsubscribe,
            self::LinkUnsubscribe,
        ], true);
    }
}
