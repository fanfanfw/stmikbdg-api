<?php

namespace App\Services\ArsipDigital;

use App\Models\ArsipDigital\Setting;

class ArsipDigitalSettingsService
{
    public const DEFAULT_KEY = 'archive_defaults';

    public const FALLBACK = [
        'default_max_file_size_mb' => 10,
        'default_allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'],
        'storage_disk' => 's3',
    ];

    public function getDefaults(): array
    {
        $setting = Setting::where('key', self::DEFAULT_KEY)->first();

        return array_replace(self::FALLBACK, $setting?->value ?? []);
    }

    public function updateDefaults(array $payload): array
    {
        $value = array_replace($this->getDefaults(), array_intersect_key($payload, self::FALLBACK));

        Setting::updateOrCreate(
            ['key' => self::DEFAULT_KEY],
            [
                'value' => $value,
                'description' => 'Default Arsip Digital V1 settings',
            ]
        );

        return $value;
    }
}
