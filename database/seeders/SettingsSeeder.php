<?php

namespace Database\Seeders;

use App\Models\settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSettings = [
            [
                'group' => 'general',
                'key' => 'site_name',
                'value' => 'Educational Platform',
                'type' => 'string',
                'is_public' => true,
            ],
            [
                'group' => 'general',
                'key' => 'site_description',
                'value' => 'Leading educational platform for online learning.',
                'type' => 'string',
                'is_public' => true,
            ],
            [
                'group' => 'general',
                'key' => 'contact_email',
                'value' => 'support@platform.com',
                'type' => 'string',
                'is_public' => true,
            ],
            [
                'group' => 'general',
                'key' => 'contact_phone',
                'value' => '+1234567890',
                'type' => 'string',
                'is_public' => true,
            ],
            [
                'group' => 'localization',
                'key' => 'default_locale',
                'value' => 'ar',
                'type' => 'string',
                'is_public' => true,
            ],
            [
                'group' => 'localization',
                'key' => 'default_timezone',
                'value' => 'Africa/Cairo',
                'type' => 'string',
                'is_public' => true,
            ],
            [
                'group' => 'system',
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'is_public' => false,
            ],
            [
                'group' => 'system',
                'key' => 'allow_student_registration',
                'value' => '1',
                'type' => 'boolean',
                'is_public' => true,
            ],
            [
                'group' => 'system',
                'key' => 'max_upload_size_mb',
                'value' => '50',
                'type' => 'integer',
                'is_public' => false,
            ],
        ];

        foreach ($defaultSettings as $setting) {
            settings::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
