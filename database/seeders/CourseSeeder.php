<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseCategory;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = CourseCategory::pluck('id', 'slug');

        $courses = [
            [
                'category_id' => $categories['cad-cam'],
                'title' => 'AutoCAD Fundamentals',
                'slug' => 'autocad-fundamentals',
                'short_description' => 'Master 2D and 3D drafting with AutoCAD, the industry-standard CAD software.',
                'description' => 'This comprehensive course covers all essential AutoCAD features including 2D drafting, 3D modeling, annotations, hatching, dimensions, and printing. Students will learn to create professional engineering drawings and develop a strong foundation in CAD methodology.',
                'overview' => 'AutoCAD is the most widely used CAD software in the world. This course provides hands-on training in 2D drafting and 3D modeling techniques used across engineering, architecture, and manufacturing industries.',
                'eligibility' => '10th pass or equivalent. Basic computer knowledge required.',
                'duration' => '3 Months',
                'fees' => 8000,
                'batch_information' => 'Morning Batch: 9:00 AM - 11:00 AM | Evening Batch: 6:00 PM - 8:00 PM',
                'skills' => ['2D Drafting', '3D Modeling', 'Annotation', 'Dimensioning', 'Printing & Plotting'],
                'career_information' => 'CAD Drafter, Design Engineer, Layout Artist, Technical Illustrator',
                'status' => 'published',
                'featured' => true,
                'sort_order' => 1,
            ],
            [
                'category_id' => $categories['cad-cam'],
                'title' => 'SolidWorks Professional',
                'slug' => 'solidworks-professional',
                'short_description' => 'Advanced 3D product design and simulation using SolidWorks.',
                'description' => 'Learn parametric modeling, assembly design, drawing creation, and basic simulation with SolidWorks. This course prepares students for real-world product design challenges in mechanical engineering and manufacturing.',
                'overview' => 'SolidWorks is a powerful 3D CAD software used for product design and engineering. Students will learn to create complex parts, assemblies, and production-ready drawings.',
                'eligibility' => '12th pass or equivalent. Basic knowledge of engineering drawing preferred.',
                'duration' => '4 Months',
                'fees' => 12000,
                'batch_information' => 'Morning Batch: 9:00 AM - 11:30 AM | Evening Batch: 6:00 PM - 8:30 PM',
                'skills' => ['Part Modeling', 'Assembly Design', 'Drawing Creation', 'Sheet Metal', 'Simulation'],
                'career_information' => 'Product Designer, Mechanical Design Engineer, 3D Modeler, R&D Engineer',
                'status' => 'published',
                'featured' => true,
                'sort_order' => 2,
            ],
            [
                'category_id' => $categories['cnc-programming'],
                'title' => 'CNC Lathe Programming',
                'slug' => 'cnc-lathe-programming',
                'short_description' => 'Complete CNC lathe programming and operation from basics to advanced.',
                'description' => 'This course covers CNC lathe programming including G-code, M-code, tool setup, work offset, canned cycles, threading, and boring operations. Students gain hands-on experience on actual CNC lathe machines.',
                'overview' => 'CNC lathes are essential in modern manufacturing. This course provides comprehensive training in programming and operating CNC turning centers.',
                'eligibility' => 'ITI/Diploma in Mechanical or equivalent. Basic machining knowledge preferred.',
                'duration' => '3 Months',
                'fees' => 15000,
                'batch_information' => 'Morning Batch: 8:00 AM - 11:00 AM | Afternoon Batch: 2:00 PM - 5:00 PM',
                'skills' => ['G-Code Programming', 'M-Code', 'Tool Setup', 'Threading', 'Canned Cycles'],
                'career_information' => 'CNC Operator, CNC Programmer, Production Engineer, Quality Inspector',
                'status' => 'published',
                'featured' => true,
                'sort_order' => 3,
            ],
            [
                'category_id' => $categories['cnc-programming'],
                'title' => 'CNC Milling & VMC',
                'slug' => 'cnc-milling-vmc',
                'short_description' => 'Vertical machining center programming and advanced milling operations.',
                'description' => 'Learn VMC programming, surface milling, pocket milling, contouring, and multi-axis machining. The course includes hands-on training with industrial VMC machines and CAM software integration.',
                'overview' => 'Vertical Machining Centers (VMC) are widely used in aerospace, automotive, and die-making industries. Master VMC operations for a competitive career in manufacturing.',
                'eligibility' => 'ITI/Diploma in Mechanical or equivalent. CNC Lathe programming knowledge preferred.',
                'duration' => '4 Months',
                'fees' => 18000,
                'batch_information' => 'Morning Batch: 8:00 AM - 11:30 AM | Afternoon Batch: 2:00 PM - 5:30 PM',
                'skills' => ['Surface Milling', 'Pocket Milling', 'Contouring', 'Drilling Cycles', 'CAM Integration'],
                'career_information' => 'VMC Operator, CNC Milling Programmer, Tooling Engineer, Manufacturing Engineer',
                'status' => 'published',
                'featured' => false,
                'sort_order' => 4,
            ],
            [
                'category_id' => $categories['industrial-skills'],
                'title' => 'Industrial Safety & Quality',
                'slug' => 'industrial-safety-quality',
                'short_description' => 'Essential safety protocols and quality management for industrial environments.',
                'description' => 'This course covers workplace safety standards, risk assessment, quality control techniques, ISO documentation, and lean manufacturing principles. Students learn to maintain safe and efficient production environments.',
                'overview' => 'Industrial safety and quality management are critical in modern manufacturing. This program prepares students to handle real-world safety challenges and implement quality systems.',
                'eligibility' => '10th pass or equivalent. Open to all backgrounds.',
                'duration' => '2 Months',
                'fees' => 5000,
                'batch_information' => 'Morning Batch: 10:00 AM - 12:00 PM | Evening Batch: 5:00 PM - 7:00 PM',
                'skills' => ['Safety Protocols', 'Risk Assessment', 'Quality Control', 'ISO Standards', 'Lean Manufacturing'],
                'career_information' => 'Safety Officer, Quality Inspector, Production Supervisor, Compliance Analyst',
                'status' => 'published',
                'featured' => false,
                'sort_order' => 5,
            ],
            [
                'category_id' => $categories['computer-skills'],
                'title' => 'Office Productivity Suite',
                'slug' => 'office-productivity-suite',
                'short_description' => 'Master Microsoft Office applications for professional productivity.',
                'description' => 'Comprehensive training in MS Word, Excel, PowerPoint, and Outlook. Learn document creation, data analysis with formulas and pivot tables, professional presentations, and email management.',
                'overview' => 'Proficiency in office software is essential for any professional role. This course provides practical skills that are immediately applicable in any workplace.',
                'eligibility' => '10th pass or equivalent. Basic computer knowledge required.',
                'duration' => '2 Months',
                'fees' => 4000,
                'batch_information' => 'Morning Batch: 9:00 AM - 11:00 AM | Evening Batch: 6:00 PM - 8:00 PM',
                'skills' => ['MS Word', 'MS Excel', 'MS PowerPoint', 'MS Outlook', 'Data Entry'],
                'career_information' => 'Office Executive, Data Entry Operator, Admin Assistant, Accounts Assistant',
                'status' => 'published',
                'featured' => false,
                'sort_order' => 6,
            ],
        ];

        foreach ($courses as $course) {
            Course::updateOrCreate(
                ['slug' => $course['slug']],
                $course
            );
        }
    }
}
