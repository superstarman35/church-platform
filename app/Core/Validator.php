<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    public static function email(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($value) <= 190;
    }

    public static function password(string $value): bool
    {
        return mb_strlen($value) >= 10 && preg_match('/[A-Za-z]/', $value) === 1 && preg_match('/\d/', $value) === 1;
    }

    public static function slug(string $value): bool
    {
        return preg_match('/^[a-z0-9][a-z0-9-]{2,79}$/', $value) === 1;
    }

    public static function text(string $value, int $min, int $max): bool
    {
        $length = mb_strlen(trim($value));
        return $length >= $min && $length <= $max;
    }
}
