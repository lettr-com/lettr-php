<?php

declare(strict_types=1);

namespace Lettr\Exceptions;

use Lettr\Dto\Audience\BulkAttachContactsToListsResult;
use Lettr\Dto\Audience\BulkDetachContactsFromListsResult;
use Lettr\Dto\Audience\BulkStoreAudienceContactsResult;
use Lettr\Dto\Audience\BulkSubscribeContactsToTopicsResult;
use Lettr\Dto\Audience\BulkUnsubscribeContactsFromTopicsResult;
use Throwable;

/**
 * A bulk call larger than the API's per-request cap was split into chunks,
 * and a chunk failed after earlier chunks had already been saved.
 *
 * Chunked calls are not atomic. Everything in `$result` was written, and
 * covers the first `$processed` items of the input. Later chunks were not
 * sent. The failing chunk itself may or may not have been applied — after a
 * timeout or 5xx the API might have processed it. The failure that stopped
 * the run is {@see self::getPrevious()}, e.g. a {@see RateLimitException}
 * once retries ran out. Resubmit the rest (`array_slice($items, $e->processed)`)
 * to continue; the bulk calls report rows that already existed or were
 * already attached, so resubmitting a chunk that did land is harmless.
 *
 * Only thrown when at least one chunk succeeded. If the first chunk fails,
 * its original exception is thrown unchanged, exactly as for a call that
 * fits in one request.
 */
final class PartialBulkFailureException extends LettrException
{
    /**
     * @param  int  $processed  Input items handled by the chunks that succeeded.
     * @param  int  $total  Input items in the whole call.
     */
    public function __construct(
        public readonly BulkStoreAudienceContactsResult|BulkAttachContactsToListsResult|BulkDetachContactsFromListsResult|BulkSubscribeContactsToTopicsResult|BulkUnsubscribeContactsFromTopicsResult $result,
        public readonly int $processed,
        public readonly int $total,
        Throwable $previous,
    ) {
        parent::__construct(
            sprintf(
                'Bulk call failed after %d of %d items were saved: %s',
                $processed,
                $total,
                $previous->getMessage(),
            ),
            0,
            $previous,
        );
    }
}
