<?php

namespace Database\Seeders;

use App\Models\GalleryCategory;
use App\Models\GalleryImage;
use Illuminate\Database\Seeder;

class GallerySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Campus', 'slug' => 'campus', 'status' => 'active', 'sort_order' => 1],
            ['name' => 'Workshops', 'slug' => 'workshops', 'status' => 'active', 'sort_order' => 2],
            ['name' => 'Events', 'slug' => 'events', 'status' => 'active', 'sort_order' => 3],
            ['name' => 'Placements', 'slug' => 'placements', 'status' => 'active', 'sort_order' => 4],
        ];

        foreach ($categories as $cat) {
            GalleryCategory::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        $catIds = GalleryCategory::pluck('id', 'slug');

        $images = [
            ['category_id' => $catIds['campus'], 'title' => 'Main Building', 'image' => '', 'alt_text' => 'Global Institute main building', 'status' => 'published', 'sort_order' => 1],
            ['category_id' => $catIds['campus'], 'title' => 'Computer Lab', 'image' => '', 'alt_text' => 'Computer lab with CAD workstations', 'status' => 'published', 'sort_order' => 2],
            ['category_id' => $catIds['campus'], 'title' => 'CNC Workshop', 'image' => '', 'alt_text' => 'CNC machines workshop', 'status' => 'published', 'sort_order' => 3],
            ['category_id' => $catIds['campus'], 'title' => 'Library', 'image' => '', 'alt_text' => 'Institute library', 'status' => 'published', 'sort_order' => 4],
            ['category_id' => $catIds['workshops'], 'title' => 'CNC Training Session', 'image' => '', 'alt_text' => 'Students training on CNC machines', 'status' => 'published', 'sort_order' => 5],
            ['category_id' => $catIds['workshops'], 'title' => 'SolidWorks Workshop', 'image' => '', 'alt_text' => 'SolidWorks hands-on workshop', 'status' => 'published', 'sort_order' => 6],
            ['category_id' => $catIds['workshops'], 'title' => 'AutoCAD Practical', 'image' => '', 'alt_text' => 'AutoCAD practical session', 'status' => 'published', 'sort_order' => 7],
            ['category_id' => $catIds['events'], 'title' => 'Annual Day Celebration', 'image' => '', 'alt_text' => 'Annual day event at the institute', 'status' => 'published', 'sort_order' => 8],
            ['category_id' => $catIds['events'], 'title' => 'Guest Lecture Series', 'image' => '', 'alt_text' => 'Industry expert delivering a lecture', 'status' => 'published', 'sort_order' => 9],
            ['category_id' => $catIds['placements'], 'title' => 'Campus Placement Drive', 'image' => '', 'alt_text' => 'Students attending placement drive', 'status' => 'published', 'sort_order' => 10],
            ['category_id' => $catIds['placements'], 'title' => 'Placement Ceremony', 'image' => '', 'alt_text' => 'Selected students during placement ceremony', 'status' => 'published', 'sort_order' => 11],
            ['category_id' => $catIds['placements'], 'title' => 'Interview Session', 'image' => '', 'alt_text' => 'Student interview with HR', 'status' => 'published', 'sort_order' => 12],
        ];

        foreach ($images as $img) {
            GalleryImage::create($img);
        }
    }
}
