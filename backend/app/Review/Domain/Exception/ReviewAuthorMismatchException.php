<?php

declare(strict_types=1);

namespace App\Review\Domain\Exception;

use App\Shared\Domain\Exception\ForbiddenException;

class ReviewAuthorMismatchException extends ForbiddenException
{
    public static function cannotEdit(): self
    {
        return new self('Only the review author can edit it.');
    }

    public static function cannotDelete(): self
    {
        return new self('Only the review author or an admin can delete it.');
    }
}
