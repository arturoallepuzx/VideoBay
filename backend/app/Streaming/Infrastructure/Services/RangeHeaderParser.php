<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Services;

use App\Streaming\Domain\ValueObject\ByteRangeSpecification;

class RangeHeaderParser
{
    private const SINGLE_RANGE_PATTERN = '/^bytes=(\d*)-(\d*)$/';

    public function parse(?string $headerValue): ?ByteRangeSpecification
    {
        if ($headerValue === null) {
            return null;
        }

        if (preg_match(self::SINGLE_RANGE_PATTERN, trim($headerValue), $matches) !== 1) {
            return null;
        }

        $rawFirstBytePos = $matches[1];
        $rawLastBytePos = $matches[2];

        if ($rawFirstBytePos === '') {
            return $rawLastBytePos === ''
                ? null
                : ByteRangeSpecification::fromSuffixLength((int) $rawLastBytePos);
        }

        $firstBytePos = (int) $rawFirstBytePos;
        $lastBytePos = $rawLastBytePos === '' ? null : (int) $rawLastBytePos;

        if ($lastBytePos !== null && $lastBytePos < $firstBytePos) {
            return null;
        }

        return ByteRangeSpecification::create($firstBytePos, $lastBytePos);
    }
}
