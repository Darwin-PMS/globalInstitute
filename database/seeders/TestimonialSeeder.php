<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'student_name' => 'Rahul Sharma',
                'course' => 'CNC Lathe Programming',
                'testimonial' => 'Global Institute transformed my career. The CNC programming course was incredibly comprehensive with hands-on training on actual machines. Within two months of completing the course, I got placed as a CNC Operator at a leading manufacturing company. The faculty is very supportive and the infrastructure is excellent.',
                'batch' => 'Jan 2026',
                'status' => 'published',
                'featured' => true,
                'sort_order' => 1,
            ],
            [
                'student_name' => 'Priya Patel',
                'course' => 'AutoCAD Fundamentals',
                'testimonial' => 'I had no prior experience in CAD before joining Global Institute. The structured curriculum and patient faculty helped me learn AutoCAD from scratch. The practical sessions were well-organized and I now confidently create 2D and 3D drawings. Highly recommend this institute for anyone looking to start a career in design.',
                'batch' => 'Feb 2026',
                'status' => 'published',
                'featured' => true,
                'sort_order' => 2,
            ],
            [
                'student_name' => 'Amit Kumar',
                'course' => 'SolidWorks Professional',
                'testimonial' => 'The SolidWorks course at Global Institute exceeded my expectations. The instructor covered advanced topics like sheet metal design and simulation that are rarely taught elsewhere. The placement cell helped me interview at multiple companies and I am now working as a Product Designer. Thank you, Global Institute!',
                'batch' => 'Dec 2025',
                'status' => 'published',
                'featured' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::create($testimonial);
        }
    }
}
