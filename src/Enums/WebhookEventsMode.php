<?php

declare(strict_types=1);

namespace Lettr\Enums;

enum WebhookEventsMode: string
{
    case All = 'all';
    case Selected = 'selected';
}
