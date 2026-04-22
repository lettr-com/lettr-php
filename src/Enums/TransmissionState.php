<?php

declare(strict_types=1);

namespace Lettr\Enums;

/**
 * Transmission states for scheduled emails.
 */
enum TransmissionState: string
{
    case Submitted = 'submitted';
    case Generating = 'generating';
    case Scheduled = 'scheduled';
    case Delivered = 'delivered';
    case Bounced = 'bounced';
    case Failed = 'failed';
    case Unknown = 'unknown';
}
