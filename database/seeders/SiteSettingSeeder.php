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
            ['setting_key' => 'site_description', 'setting_value' => 'Global Institute & CAD CAM, Gamharia (Jamshedpur), offers hands-on AutoCAD, SolidWorks, CATIA, Creo, NX CAD, Fusion 360 and CNC/VMC programming training for Diploma, B.Tech and ITI students, with real industrial projects, interview preparation and placement assistance.', 'setting_type' => 'textarea', 'group_name' => 'general'],
            ['setting_key' => 'site_logo', 'setting_value' => 'images/logo.png', 'setting_type' => 'image', 'group_name' => 'general'],
            ['setting_key' => 'site_favicon', 'setting_value' => 'images/favicon.ico', 'setting_type' => 'image', 'group_name' => 'general'],

            // Contact
            ['setting_key' => 'contact_email', 'setting_value' => 'info@globalinstitute.edu.in', 'setting_type' => 'text', 'group_name' => 'contact'],
            ['setting_key' => 'contact_phone', 'setting_value' => '+91 87899 61355', 'setting_type' => 'text', 'group_name' => 'contact'],
            ['setting_key' => 'contact_phone_2', 'setting_value' => '+91 83401 45191', 'setting_type' => 'text', 'group_name' => 'contact'],
            ['setting_key' => 'contact_address', 'setting_value' => 'Gamharia Lal Building, Lakshmi Narayan, Near Jamshedpur, Jharkhand 832108', 'setting_type' => 'textarea', 'group_name' => 'contact'],
            ['setting_key' => 'business_hours', 'setting_value' => 'Mon – Sun, 8:00 AM – 9:00 PM', 'setting_type' => 'text', 'group_name' => 'contact'],
            ['setting_key' => 'contact_map_embed', 'setting_value' => 'https://www.google.com/maps?q=Global%20Institute%20%26%20CAD%20CAM%2C%20Gamharia%2C%20Jamshedpur%2C%20Jharkhand%20832108&output=embed', 'setting_type' => 'textarea', 'group_name' => 'contact'],
            ['setting_key' => 'google_maps_url', 'setting_value' => 'https://maps.app.goo.gl/oc38H4gZ7vWVUg6PA', 'setting_type' => 'text', 'group_name' => 'contact'],

            // Social Media
            ['setting_key' => 'facebook_url', 'setting_value' => 'https://www.facebook.com/p/Global-Institute-Cad-Cam-61577704497705/', 'setting_type' => 'text', 'group_name' => 'social'],
            ['setting_key' => 'instagram_url', 'setting_value' => 'https://www.instagram.com/global_institute_and_cadcam/', 'setting_type' => 'text', 'group_name' => 'social'],
            ['setting_key' => 'youtube_url', 'setting_value' => 'https://youtube.com/@globalinstitute', 'setting_type' => 'text', 'group_name' => 'social'],
            ['setting_key' => 'linkedin_url', 'setting_value' => 'https://linkedin.com/company/globalinstitute', 'setting_type' => 'text', 'group_name' => 'social'],
            ['setting_key' => 'whatsapp_number', 'setting_value' => '918789961355', 'setting_type' => 'text', 'group_name' => 'social'],

            // Footer
            ['setting_key' => 'footer_text', 'setting_value' => '© 2026 Global Institute & CAD CAM. All rights reserved.', 'setting_type' => 'text', 'group_name' => 'footer'],
            ['setting_key' => 'footer_about', 'setting_value' => 'Global Institute & CAD CAM is an industry-oriented technical training institute in Gamharia, Jamshedpur, offering AutoCAD, SolidWorks, CATIA, Creo, NX CAD, Fusion 360, CNC/VMC programming and industrial skills training with hands-on labs, real projects and placement assistance.', 'setting_type' => 'textarea', 'group_name' => 'footer'],

            // SEO
            ['setting_key' => 'meta_title', 'setting_value' => 'Global Institute & CAD CAM | AutoCAD, SolidWorks, CATIA & CNC Training in Jamshedpur', 'setting_type' => 'text', 'group_name' => 'seo'],
            ['setting_key' => 'meta_description', 'setting_value' => 'Hands-on CAD/CAM and CNC training in Gamharia, Jamshedpur. Learn AutoCAD, SolidWorks, CATIA, Creo, NX CAD, Fusion 360, CNC/VMC programming with real projects and placement assistance. Open Mon-Sun, 8 AM-9 PM.', 'setting_type' => 'textarea', 'group_name' => 'seo'],
            ['setting_key' => 'meta_keywords', 'setting_value' => 'CAD CAM institute Jamshedpur, AutoCAD training Jamshedpur, SolidWorks course, CATIA training, Creo course, NX CAD, Fusion 360, CNC programming course Jamshedpur, VMC programming, mechanical drafting, 3D CAD modeling, industrial training Jharkhand, placement assistance', 'setting_type' => 'textarea', 'group_name' => 'seo'],

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
