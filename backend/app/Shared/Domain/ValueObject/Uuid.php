<?php

namespace App\Shared\Domain\ValueObject;

class Uuid
{
    private const PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    private string $value;

    private function __construct(string $value)
    {
        if (! preg_match(self::PATTERN, $value)) {
            throw new \InvalidArgumentException("Invalid UUID: $value");
        }
        $this->value = strtolower($value);
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function generate(): self
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0F | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3F | 0x80);
        $hex = bin2hex($bytes);
        $uuid = substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-'.substr($hex, 12, 4).'-'.substr($hex, 16, 4).'-'.substr($hex, 20, 12);

        return self::create($uuid);
    }

    public function value(): string
    {
        return $this->value;
    }
}
