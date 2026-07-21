<?php

namespace App\Support;

use App\Models\Setting;

class CostCipher
{
    public static function encode(float|int|string $cost, ?string $key = null): string
    {
        $key = strtoupper($key ?? Setting::current()->cipher_key);

        if (strlen($key) < 10) {
            return '----';
        }

        $digits = (string) (int) round((float) $cost);

        return collect(str_split($digits))
            ->map(fn ($d) => $key[(int) $d] ?? '?')
            ->implode('');
    }

    public static function isValidKey(string $key): bool
    {
        $key = strtoupper(trim($key));

        return strlen($key) === 10 && count(array_unique(str_split($key))) === 10;
    }
}
