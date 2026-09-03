<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever(
            "setting:{$key}",
            fn () => static::find($key)?->value
        ) ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = static::get($key);

        return $value === null ? $default : (int) $value;
    }

    /**
     * Read a value stored encrypted. Returns null rather than throwing when the
     * ciphertext no longer decrypts — a rotated APP_KEY must not take the whole
     * admin panel down, it must just make the SMTP password look unset.
     */
    public static function secret(string $key): ?string
    {
        $value = static::get($key);

        if (blank($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }

    public static function putSecret(string $key, #[\SensitiveParameter] ?string $value): void
    {
        static::put($key, blank($value) ? null : Crypt::encryptString($value));
    }
}
