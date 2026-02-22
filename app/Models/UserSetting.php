<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $fillable = ['user_id', 'key', 'value'];

    public static function get(int $userId, string $key, mixed $default = null): mixed
    {
        $setting = static::where('user_id', $userId)->where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    public static function set(int $userId, string $key, ?string $value): void
    {
        static::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value]
        );
    }
}
