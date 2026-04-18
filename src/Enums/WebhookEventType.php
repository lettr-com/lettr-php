<?php

declare(strict_types=1);

namespace Lettr\Enums;

/**
 * Event types accepted on webhook subscriptions.
 *
 * Distinct from {@see EventType} (which filters `GET /emails/events`):
 * webhook identifiers are namespaced with a category prefix.
 */
enum WebhookEventType: string
{
    case MessageInjection = 'message.injection';
    case MessageDelivery = 'message.delivery';
    case MessageBounce = 'message.bounce';
    case MessageDelay = 'message.delay';
    case MessageOutOfBand = 'message.out_of_band';
    case MessageSpamComplaint = 'message.spam_complaint';
    case MessagePolicyRejection = 'message.policy_rejection';

    case EngagementClick = 'engagement.click';
    case EngagementOpen = 'engagement.open';
    case EngagementInitialOpen = 'engagement.initial_open';
    case EngagementAmpClick = 'engagement.amp_click';
    case EngagementAmpOpen = 'engagement.amp_open';
    case EngagementAmpInitialOpen = 'engagement.amp_initial_open';

    case GenerationFailure = 'generation.generation_failure';
    case GenerationRejection = 'generation.generation_rejection';

    case UnsubscribeList = 'unsubscribe.list_unsubscribe';
    case UnsubscribeLink = 'unsubscribe.link_unsubscribe';

    /**
     * The category prefix (`message`, `engagement`, `generation`, `unsubscribe`).
     */
    public function category(): string
    {
        return explode('.', $this->value, 2)[0];
    }

    /**
     * Whether this event belongs to the engagement category (opens, clicks).
     */
    public function isEngagement(): bool
    {
        return $this->category() === 'engagement';
    }

    /**
     * Whether this event belongs to the message-delivery category.
     */
    public function isMessage(): bool
    {
        return $this->category() === 'message';
    }
}
