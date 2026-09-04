<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // AdminUserSeeder must run before RbacSeeder so the admin user
        // exists when RbacSeeder assigns the super-admin role.
        $this->call([
            AdminUserSeeder::class,
            RbacSeeder::class,
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
