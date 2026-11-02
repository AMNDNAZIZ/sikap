<?php

namespace App\Services\Cbt\Review;

use DomainException;

/**
 * InvalidReviewActionException
 *
 * ESPATA Assessment Intelligence V3 - Stage F3
 * Thrown when a reviewer attempts an action that violates architectural boundaries
 * (e.g. attempting to force-publish an uncompleted essay, converting absence to earned zero,
 * or mutating historical scores).
 */
class InvalidReviewActionException extends DomainException
{
}

