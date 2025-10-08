<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
        'is_public',
        'is_encrypted',
        'validation_rules',
        'options',
        'metadata',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_public' => 'boolean',
        'is_encrypted' => 'boolean',
        'validation_rules' => 'array',
        'options' => 'array',
        'metadata' => 'array',
    ];

    /**
     * The cache key prefix.
     */
    protected static string $cachePrefix = 'settings:';

    /**
     * Boot the model.
     */
    protected static function booted()
    {
        static::saved(function ($setting) {
            $setting->clearCache();
        });

        static::deleted(function ($setting) {
            $setting->clearCache();
        });
    }

    /**
     * Get the value attribute.
     */
    public function getValueAttribute($value)
    {
        if ($this->is_encrypted && $value !== null) {
            try {
                $value = Crypt::decryptString($value);
            } catch (\Exception $e) {
                // Return raw value if decryption fails
            }
        }

        return $this->castValue($value);
    }

    /**
     * Set the value attribute.
     */
    public function setValueAttribute($value)
    {
        if ($this->is_encrypted && $value !== null) {
            $value = Crypt::encryptString(is_array($value) ? json_encode($value) : $value);
        } elseif (is_array($value)) {
            $value = json_encode($value);
        }

        $this->attributes['value'] = $value;
    }

    /**
     * Cast value based on type.
     */
    protected function castValue($value)
    {
        if ($value === null) {
            return null;
        }

        return match ($this->type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($value, true),
            'float' => (float) $value,
            default => $value,
        };
    }

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, $default = null, string $group = 'general')
    {
        $cacheKey = self::$cachePrefix . $group . ':' . $key;
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $group, $default) {
            $setting = self::where('group', $group)->where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, $value, string $group = 'general'): self
    {
        $setting = self::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $value]
        );

        $setting->clearCache();
        return $setting;
    }

    /**
     * Get all settings for a group.
     */
    public static function getGroup(string $group): array
    {
        $cacheKey = self::$cachePrefix . 'group:' . $group;
        
        return Cache::remember($cacheKey, 3600, function () use ($group) {
            return self::where('group', $group)
                ->orderBy('sort_order')
                ->get()
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    /**
     * Get all public settings.
     */
    public static function getPublic(): array
    {
        $cacheKey = self::$cachePrefix . 'public';
        
        return Cache::remember($cacheKey, 3600, function () {
            $settings = self::where('is_public', true)
                ->orderBy('group')
                ->orderBy('sort_order')
                ->get();

            $grouped = [];
            foreach ($settings as $setting) {
                $grouped[$setting->group][$setting->key] = $setting->value;
            }

            return $grouped;
        });
    }

    /**
     * Clear cache for this setting.
     */
    public function clearCache(): void
    {
        Cache::forget(self::$cachePrefix . $this->group . ':' . $this->key);
        Cache::forget(self::$cachePrefix . 'group:' . $this->group);
        Cache::forget(self::$cachePrefix . 'public');
        Cache::forget(self::$cachePrefix . 'all');
    }

    /**
     * Clear all settings cache.
     */
    public static function clearAllCache(): void
    {
        Cache::flush();
    }

    /**
     * Scope to filter by group.
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope to filter public settings.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to filter encrypted settings.
     */
    public function scopeEncrypted($query)
    {
        return $query->where('is_encrypted', true);
    }
}