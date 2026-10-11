<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['setting_key' => 'site_name', 'setting_value' => 'Global Institute', 'setting_type' => 'text', 'group_name' => 'general'],
            ['setting_key' => 'site_tagline', 'setting_value' => 'Excellence in Technical Education', 'setting_type' => 'text', 'group_name' => 'general'],
            ['setting_key' => 'site_description', 'setting_value' => 'Global Institute offers industry-oriented training programs in CAD/CAM, CNC Programming, Industrial Skills, and Computer Skills with placement assistance.', 'setting_type' => 'textarea', 'group_name' => 'general'],
            ['setting_key' => 'site_logo', 'setting_value' => 'images/logo.png', 'setting_type' => 'image', 'group_name' => 'general'],
            ['setting_key' => 'site_favicon', 'setting_value' => 'images/favicon.ico', 'setting_type' => 'image', 'group_name' => 'general'],

            // Contact
            ['setting_key' => 'contact_email', 'setting_value' => 'info@globalinstitute.edu.in', 'setting_type' => 'text', 'group_name' => 'contact'],
            ['setting_key' => 'contact_phone', 'setting_value' => '+91 87899 61355', 'setting_type' => 'text', 'group_name' => 'contact'],
            ['setting_key' => 'contact_phone_2', 'setting_value' => '', 'setting_type' => 'text', 'group_name' => 'contact'],
            ['setting_key' => 'contact_address', 'setting_value' => 'Gamharia Chowk, Jamshedpur, Jharkhand', 'setting_type' => 'textarea', 'group_name' => 'contact'],
            ['setting_key' => 'contact_map_embed', 'setting_value' => '', 'setting_type' => 'textarea', 'group_name' => 'contact'],

            // Social Media
            ['setting_key' => 'facebook_url', 'setting_value' => 'https://facebook.com/globalinstitute', 'setting_type' => 'text', 'group_name' => 'social'],
            ['setting_key' => 'instagram_url', 'setting_value' => 'https://instagram.com/globalinstitute', 'setting_type' => 'text', 'group_name' => 'social'],
            ['setting_key' => 'youtube_url', 'setting_value' => 'https://youtube.com/@globalinstitute', 'setting_type' => 'text', 'group_name' => 'social'],
            ['setting_key' => 'linkedin_url', 'setting_value' => 'https://linkedin.com/company/globalinstitute', 'setting_type' => 'text', 'group_name' => 'social'],
            ['setting_key' => 'whatsapp_number', 'setting_value' => '918789961355', 'setting_type' => 'text', 'group_name' => 'social'],

            // Footer
            ['setting_key' => 'footer_text', 'setting_value' => '© 2026 Global Institute. All rights reserved.', 'setting_type' => 'text', 'group_name' => 'footer'],
            ['setting_key' => 'footer_about', 'setting_value' => 'Global Institute is a premier technical training institute offering industry-oriented courses with placement assistance.', 'setting_type' => 'textarea', 'group_name' => 'footer'],

            // SEO
            ['setting_key' => 'meta_title', 'setting_value' => 'Global Institute - Technical Training & Placement Institute', 'setting_type' => 'text', 'group_name' => 'seo'],
            ['setting_key' => 'meta_description', 'setting_value' => 'Join Global Institute for professional training in CAD/CAM, CNC Programming, Industrial Skills, and Computer Skills. 100% placement assistance.', 'setting_type' => 'textarea', 'group_name' => 'seo'],
            ['setting_key' => 'meta_keywords', 'setting_value' => 'CAD CAM training, CNC programming course, industrial training, computer courses, technical institute, placement', 'setting_type' => 'textarea', 'group_name' => 'seo'],

            // Enrollment
            ['setting_key' => 'enrollment_open', 'setting_value' => '1', 'setting_type' => 'boolean', 'group_name' => 'enrollment'],
            ['setting_key' => 'enrollment_message', 'setting_value' => 'Admissions are now open for upcoming batches. Contact us today!', 'setting_type' => 'textarea', 'group_name' => 'enrollment'],
        ];

        foreach ($settings as $setting) {
            SiteSetting::updateOrCreate(
                ['setting_key' => $setting['setting_key']],
                $setting
            );
        }
    }
}
