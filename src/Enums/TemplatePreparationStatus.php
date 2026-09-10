<?php

declare(strict_types=1);

namespace Lettr\Enums;

/**
 * How far a template has got through preparation.
 *
 * Creating or updating a template through the API defers image migration and
 * HTML rendering to a background job. On a create with JSON there is no HTML at
 * all until that finishes; on an update the previous render stays in place, so
 * the template is still sendable but is serving the *old* content.
 */
enum TemplatePreparationStatus: string
{
    /** Queued or running. The current content has not landed yet. */
    case Pending = 'pending';

    /** Finished. What you sent is what will be sent. */
    case Ready = 'ready';

    /** Gave up after its retries. It will not become ready on its own. */
    case Failed = 'failed';

    /**
     * Read the field from an API response.
     *
     * Absent means an API deployment that predates the field, where every
     * template with HTML was simply usable - so `Ready` is the honest default.
     * `Pending` would make an older API look like a stalled queue and hang any
     * caller that waits for readiness.
     */
    public static function fromResponse(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Ready;
    }

    /**
     * Whether the content you last sent is the content that will go out.
     *
     * Deliberately not "can I send this": a template being prepared after an
     * update keeps its previous render and stays sendable.
     */
    public function isSettled(): bool
    {
        return $this === self::Ready;
    }
}
