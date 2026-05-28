<?php

declare(strict_types=1);

namespace Lettr\Enums;

/**
 * Lifecycle status of a campaign.
 */
enum CampaignStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Preparing = 'preparing';
    case InReview = 'in_review';
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Scheduled => 'Scheduled',
            self::Preparing => 'Preparing',
            self::InReview => 'In Review',
            self::Sending => 'Sending',
            self::Sent => 'Sent',
            self::Failed => 'Failed',
        };
    }
}
