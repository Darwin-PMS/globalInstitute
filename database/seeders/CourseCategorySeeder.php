<?php

namespace Database\Seeders;

use App\Models\CourseCategory;
use Illuminate\Database\Seeder;

class CourseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'CAD/CAM',
                'slug' => 'cad-cam',
                'description' => 'Computer-Aided Design and Computer-Aided Manufacturing courses for precision engineering and product development.',
                'icon' => 'precision_manufacturing',
                'status' => 'published',
                'sort_order' => 1,
            ],
            [
                'name' => 'CNC Programming',
                'slug' => 'cnc-programming',
                'description' => 'CNC machine programming and operation courses covering lathe, mill, and multi-axis machining.',
                'icon' => 'settings',
                'status' => 'published',
                'sort_order' => 2,
            ],
            [
                'name' => 'Industrial Skills',
                'slug' => 'industrial-skills',
                'description' => 'Hands-on industrial training programs for manufacturing, maintenance, and quality control.',
                'icon' => 'build',
                'status' => 'published',
                'sort_order' => 3,
            ],
            [
                'name' => 'Computer Skills',
                'slug' => 'computer-skills',
                'description' => 'Essential computer literacy and software proficiency courses for the modern workplace.',
                'icon' => 'computer',
                'status' => 'published',
                'sort_order' => 4,
            ],
        ];

        foreach ($categories as $category) {
            CourseCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
