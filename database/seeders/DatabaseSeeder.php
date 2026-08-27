<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            CourseCategorySeeder::class,
            CourseSeeder::class,
            SiteSettingSeeder::class,
            FaqSeeder::class,
            TestimonialSeeder::class,
            GallerySeeder::class,
            InstituteDataSeeder::class,
        ]);
    }
}
