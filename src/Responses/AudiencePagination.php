<?php

declare(strict_types=1);

namespace Lettr\Responses;

/**
 * Pagination information shared by all audience list responses.
 *
 * Thin subclass of {@see Pagination} that exists only so audience list
 * responses can type-hint a resource-specific pagination object.
 */
final readonly class AudiencePagination extends Pagination {}
