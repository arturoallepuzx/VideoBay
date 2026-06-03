<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;

interface SubtitleFileStorageInterface
{
    public function store(Uuid $subtitleId, string $contents): string;

    public function delete(string $filePath): void;

    public function absolutePath(string $filePath): string;

    public function exists(string $filePath): bool;
}
