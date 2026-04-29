<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /** Returns the configured currency symbol, e.g. "$", "€", "PKR" */
    public static function currency(): string
    {
        return static::get('currency_symbol', '$');
    }

    /** Format a monetary value using the configured currency symbol */
    public static function money(float|int|string|null $amount, int $decimals = 2): string
    {
        $symbol = static::currency();
        return $symbol . number_format((float) ($amount ?? 0), $decimals);
    }
}
