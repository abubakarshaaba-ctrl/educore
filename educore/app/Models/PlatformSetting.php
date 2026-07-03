<?php
namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PlatformSetting extends Model
{
    protected $table = 'platform_settings';

    public const SECRET_KEYS = [
        'paystack_secret_key',
        'flutterwave_secret_key',
        'monnify_secret_key',
    ];

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
    ];

    public function getTypedValueAttribute(): mixed
    {
        return self::castStoredValue($this->value, $this->type, $this->key);
    }

    public static function valueFor(string $key, mixed $default = null): mixed
    {
        $setting = self::query()->where('key', $key)->first();

        return $setting ? $setting->typed_value : $default;
    }

    public static function valuesFor(array $keys): array
    {
        return self::query()
            ->whereIn('key', $keys)
            ->get()
            ->mapWithKeys(fn (self $setting) => [$setting->key => $setting->typed_value])
            ->all();
    }

    public static function setValue(
        string $key,
        mixed $value,
        string $type = 'string',
        string $group = 'general',
        ?string $label = null
    ): self {
        $type = strtolower($type);

        return self::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => self::stringifyValue($value, $type),
                'type' => $type,
                'group' => $group,
                'label' => $label,
            ]
        );
    }

    public static function isSecretKey(string $key): bool
    {
        return in_array($key, self::SECRET_KEYS, true);
    }

    private static function castStoredValue(?string $value, ?string $type, ?string $key = null): mixed
    {
        if ($value !== null && ($type === 'encrypted' || ($key && self::isSecretKey($key)))) {
            try {
                return Crypt::decryptString($value);
            } catch (DecryptException) {
                return $value;
            }
        }

        return match (strtolower($type ?? 'string')) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'integer' => (int) $value,
            'json' => $value !== null ? json_decode($value, true) : null,
            default => $value,
        };
    }

    private static function stringifyValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) (int) $value,
            'json' => json_encode($value) ?: null,
            'encrypted' => Crypt::encryptString((string) $value),
            default => (string) $value,
        };
    }
}
