<?php

namespace App\Services;

use App\Http\Resources\SettingsResource;
use App\Models\settings;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    /**
     * Retrieve all settings with optional filtering and caching.
     *
     * @param  array<string, mixed>  $filters
     * @return array{status: bool, message: string, data: mixed, code: int}
     */
    public function getAll(array $filters = []): array
    {
        $hasFilters = ! empty($filters['group']) || isset($filters['is_public']);

        if ($hasFilters) {
            $query = settings::query();

            if (! empty($filters['group'])) {
                $query->where('group', $filters['group']);
            }

            if (isset($filters['is_public'])) {
                $query->where('is_public', filter_var($filters['is_public'], FILTER_VALIDATE_BOOLEAN));
            }

            $settingsList = $query->get();
        } else {
            $settingsList = Cache::remember('settings.all', now()->addHours(24), function () {
                return settings::all();
            });
        }

        return [
            'status' => true,
            'message' => __('api.settings_retrieved'),
            'data' => SettingsResource::collection($settingsList),
            'code' => 200,
        ];
    }

    /**
     * Retrieve a single setting by ID or unique key with caching.
     *
     * @return array{status: bool, message: string, data?: SettingsResource, code: int}
     */
    public function getById(int|string $id): array
    {
        $cacheKey = "settings.item.{$id}";

        $setting = Cache::remember($cacheKey, now()->addHours(24), function () use ($id) {
            return is_numeric($id)
                ? settings::where('id', $id)->first()
                : settings::where('key', $id)->first();
        });

        if (! $setting) {
            Cache::forget($cacheKey);

            return [
                'status' => false,
                'message' => __('api.setting_not_found'),
                'code' => 404,
            ];
        }

        return [
            'status' => true,
            'message' => __('api.setting_retrieved'),
            'data' => new SettingsResource($setting),
            'code' => 200,
        ];
    }

    /**
     * Retrieve grouped public settings with caching.
     * Only settings where is_public = true are returned.
     *
     * @return array{status: bool, message: string, data: array<string, array<string, mixed>>, code: int}
     */
    public function getPublicSettings(): array
    {
        $publicSettings = Cache::remember('settings.public', now()->addHours(24), function () {
            $settings = settings::where('is_public', true)->get();

            $grouped = [];
            foreach ($settings as $setting) {
                $grouped[$setting->group][$setting->key] = $setting->value;
            }

            return $grouped;
        });

        return [
            'status' => true,
            'message' => __('api.settings_retrieved'),
            'data' => $publicSettings,
            'code' => 200,
        ];
    }

    /**
     * Create a new setting and invalidate relevant caches.
     *
     * @param  array<string, mixed>  $data
     * @return array{status: bool, message: string, data: SettingsResource, code: int}
     */
    public function create(array $data): array
    {
        $setting = settings::create([
            'group' => $data['group'] ?? 'general',
            'key' => $data['key'],
            'value' => array_key_exists('value', $data) ? $data['value'] : null,
            'type' => $data['type'] ?? 'string',
            'is_public' => array_key_exists('is_public', $data) ? $data['is_public'] : false,
        ]);

        $this->invalidateCache($setting);

        return [
            'status' => true,
            'message' => __('api.setting_created'),
            'data' => new SettingsResource($setting),
            'code' => 201,
        ];
    }

    /**
     * Update an existing setting and invalidate relevant caches.
     *
     * @param  array<string, mixed>  $data
     * @return array{status: bool, message: string, data?: SettingsResource, code: int}
     */
    public function update(int|string $id, array $data): array
    {
        $setting = is_numeric($id)
            ? settings::where('id', $id)->first()
            : settings::where('key', $id)->first();

        if (! $setting) {
            return [
                'status' => false,
                'message' => __('api.setting_not_found'),
                'code' => 404,
            ];
        }

        $oldKey = $setting->key;

        $setting->update([
            'group' => $data['group'] ?? $setting->group,
            'key' => $data['key'] ?? $setting->key,
            'value' => array_key_exists('value', $data) ? $data['value'] : $setting->value,
            'type' => $data['type'] ?? $setting->type,
            'is_public' => array_key_exists('is_public', $data) ? $data['is_public'] : $setting->is_public,
        ]);

        $this->invalidateCache($setting, $oldKey);

        return [
            'status' => true,
            'message' => __('api.setting_updated'),
            'data' => new SettingsResource($setting),
            'code' => 200,
        ];
    }

    /**
     * Delete a setting and invalidate relevant caches.
     *
     * @return array{status: bool, message: string, data?: null, code: int}
     */
    public function delete(int|string $id): array
    {
        $setting = is_numeric($id)
            ? settings::where('id', $id)->first()
            : settings::where('key', $id)->first();

        if (! $setting) {
            return [
                'status' => false,
                'message' => __('api.setting_not_found'),
                'code' => 404,
            ];
        }

        $settingId = $setting->id;
        $settingKey = $setting->key;

        $setting->delete();

        $this->forgetSettingCaches($settingId, $settingKey);

        return [
            'status' => true,
            'message' => __('api.setting_deleted'),
            'code' => 200,
            'data' => null,
        ];
    }

    /**
     * Invalidate caches associated with settings.
     */
    protected function invalidateCache(settings $setting, ?string $oldKey = null): void
    {
        $this->forgetSettingCaches($setting->id, $setting->key);

        if ($oldKey && $oldKey !== $setting->key) {
            Cache::forget("settings.item.{$oldKey}");
        }
    }

    /**
     * Clear all cached keys for settings.
     */
    protected function forgetSettingCaches(int|string $id, ?string $key = null): void
    {
        Cache::forget('settings.all');
        Cache::forget('settings.public');
        Cache::forget("settings.item.{$id}");

        if ($key) {
            Cache::forget("settings.item.{$key}");
        }
    }
}
