<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Service;

class ProductTitleNormalizer
{
    private const NOISE_PATTERN = '/\b(dvd|blu[\s\-]?ray|bd|4k|uhd|ultra\s?hd|vhs|steelbook)\b/iu';

    public function normalize(string $title): string
    {
        $cleaned = (string) preg_replace(self::NOISE_PATTERN, ' ', $title);
        $cleaned = (string) preg_replace('/\s+/', ' ', $cleaned);
        $cleaned = trim($cleaned, " \t-:|/[]()");

        return $cleaned === '' ? trim($title) : $cleaned;
    }
}
