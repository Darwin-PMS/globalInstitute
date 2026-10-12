<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $page) {
            Page::updateOrCreate(['slug' => $page['slug']], $page);
        }
    }

    /**
     * All public pages for the institute site.
     *
     * The first group mirrors the site's primary navigation routes (these are
     * rendered by the SPA); the second group are standalone CMS pages served at
     * /{slug}. Slugs are unique identifiers used for lookups.
     *
     * @return array<int, array<string, mixed>>
     */
    private function pages(): array
    {
        return [
            [
                'title' => 'About Us',
                'slug' => 'about',
                'excerpt' => 'An industry-oriented technical training institute in Gamharia, near Jamshedpur, Jharkhand.',
                'content' => '<p>Global Institute &amp; CAD CAM is an industry-oriented technical training institute located in Gamharia, near Jamshedpur, Jharkhand. We deliver hands-on, job-ready training in CAD/CAM design and CNC programming to Diploma, B.Tech and ITI students as well as working professionals.</p>'
                    .'<p>Our curriculum is built around real industrial workflows. Students train on industry-standard software such as AutoCAD, SolidWorks, CATIA, Creo, NX CAD and Fusion 360, and gain practical exposure to 2D drafting, 3D modelling, CNC turning, VMC machining, industrial safety and quality, and office productivity tools.</p>'
                    .'<p>Every program combines classroom instruction, computer-lab practice and project work. We support learners with real industrial projects, interview preparation, resume guidance and placement assistance, so they leave with a portfolio and the confidence to start their careers.</p>'
                    .'<p>The institute is open Monday to Sunday, 8:00 AM to 9:00 PM, with flexible morning, afternoon and evening batches. Visit us at Gamharia Lal Building, Lakshmi Narayan, Near Jamshedpur, Jharkhand 832108, or call +91 87899 61355 / +91 83401 45191.</p>',
                'template' => 'default',
                'status' => 'published',
                'seo_title' => 'About Us | Global Institute & CAD CAM',
                'seo_description' => 'Learn about Global Institute & CAD CAM, an industry-oriented CAD/CAM and CNC training institute in Gamharia, Jamshedpur, with hands-on labs and placement assistance.',
            ],
            [
                'title' => 'Courses',
                'slug' => 'courses',
                'excerpt' => 'Practical CAD/CAM design, CNC programming, industrial skills and computer productivity programs.',
                'content' => '<p>We offer focused, practical training programs across CAD/CAM design, CNC programming, industrial skills and computer productivity.</p>'
                    .'<ul><li>AutoCAD Fundamentals</li><li>SolidWorks Professional</li><li>CNC Lathe Programming</li><li>CNC Milling &amp; VMC</li><li>Industrial Safety &amp; Quality</li><li>Office Productivity Suite</li></ul>'
                    .'<p>Each course blends classroom sessions, hands-on lab practice and a project, and is suitable for Diploma, B.Tech and ITI students as well as working professionals. Flexible batches run through the week. <a href="/courses">Browse all courses</a> or contact us to choose the right program.</p>',
                'template' => 'default',
                'status' => 'published',
                'seo_title' => 'Courses & Training Programs | Global Institute & CAD CAM',
                'seo_description' => 'Explore CAD/CAM design, CNC programming, industrial skills and computer training programs at Global Institute & CAD CAM, Gamharia, Jamshedpur.',
            ],
            [
                'title' => 'Our Faculty',
                'slug' => 'faculty',
                'excerpt' => 'Experienced engineers and industry professionals who teach our technical programs.',
                'content' => '<p>Our faculty are experienced engineers and working professionals who bring current industry practice into the classroom. They mentor students through real design and machining workflows and provide individual guidance on projects, interviews and portfolios.</p>'
                    .'<p>To meet our trainers in person and discuss your goals, visit the campus or <a href="/contact">contact us</a>.</p>',
                'template' => 'default',
                'status' => 'published',
                'seo_title' => 'Our Faculty | Global Institute & CAD CAM',
                'seo_description' => 'Meet the experienced trainers and industry professionals who teach CAD/CAM, CNC and technical programs at Global Institute & CAD CAM, Jamshedpur.',
            ],
            [
                'title' => 'Campus & Facilities',
                'slug' => 'facilities',
                'excerpt' => 'Industry-standard CAD labs, a CNC lab, workshop and classrooms.',
                'content' => '<p>Our campus is equipped for practical, hands-on learning.</p>'
                    .'<ul><li>CAD and design computer labs with industry-standard software</li><li>CNC lab for lathe, milling and VMC practice</li><li>Workshop for hands-on fabrication and machining</li><li>Air-conditioned classrooms and a seminar room</li></ul>'
                    .'<p>All labs are set up for individual practice, so every student gets system time and machine hands-on experience. <a href="/contact">Plan a campus visit</a>.</p>',
                'template' => 'default',
                'status' => 'published',
                'seo_title' => 'Campus & Facilities | Global Institute & CAD CAM',
                'seo_description' => 'Industry-standard CAD labs, computer labs, CNC lab, workshop and classrooms at Global Institute & CAD CAM, Gamharia, Jamshedpur.',
            ],
            [
                'title' => 'Gallery',
                'slug' => 'gallery',
                'excerpt' => 'Photos from our labs, workshops, classrooms and student training sessions.',
                'content' => '<p>Explore photos of our CAD labs, CNC workshop, classrooms and student training sessions at Gamharia, Jamshedpur.</p>'
                    .'<p>Follow us on Instagram for the latest batch updates and student work.</p>',
                'template' => 'default',
                'status' => 'published',
                'seo_title' => 'Gallery | Global Institute & CAD CAM',
                'seo_description' => 'Photo gallery of labs, workshops, classrooms and student training activities at Global Institute & CAD CAM, Gamharia, Jamshedpur.',
            ],
            [
                'title' => 'Admissions',
                'slug' => 'admissions',
                'excerpt' => 'Eligibility, batch timings and the enrollment process for our training programs.',
                'content' => '<p>Admissions are open for CAD/CAM design, CNC programming, industrial skills and computer productivity programs. Diploma, B.Tech and ITI students and working professionals are all welcome.</p>'
                    .'<p>To enroll, submit an enquiry online or visit the campus. Our team will help you choose the right course, confirm the batch timing and complete the enrollment. Flexible morning, afternoon and evening batches are available.</p>'
                    .'<p>For eligibility, fees and the current batch schedule, call +91 87899 61355 or +91 83401 45191, or <a href="/contact">send us an enquiry</a>.</p>',
                'template' => 'default',
                'status' => 'published',
                'seo_title' => 'Admissions & Enrollment | Global Institute & CAD CAM',
                'seo_description' => 'Admission details, eligibility, batch timings and enrollment process for CAD/CAM, CNC and industrial training courses at Global Institute & CAD CAM, Jamshedpur.',
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact',
                'excerpt' => 'Call, write or visit our campus in Gamharia, near Jamshedpur.',
                'content' => '<p>Reach out to our admissions team to start your technical training journey.</p>'
                    .'<ul><li>Address: Gamharia Lal Building, Lakshmi Narayan, Near Jamshedpur, Jharkhand 832108</li>'
                    .'<li>Phone: +91 87899 61355, +91 83401 45191</li>'
                    .'<li>Email: info@globalinstitute.edu.in</li>'
                    .'<li>Hours: Monday to Sunday, 8:00 AM to 9:00 PM</li></ul>'
                    .'<p>You can also message us on WhatsApp for quick replies, or <a href="/contact">use the enquiry form</a>.</p>',
                'template' => 'default',
                'status' => 'published',
                'seo_title' => 'Contact Us | Global Institute & CAD CAM',
                'seo_description' => 'Contact Global Institute & CAD CAM in Gamharia, Jamshedpur. Call +91 87899 61355 or +91 83401 45191, open Monday to Sunday, 8 AM to 9 PM.',
            ],
            [
                'title' => 'Placement & Career Support',
                'slug' => 'placement',
                'excerpt' => 'Projects, interview preparation, resume guidance and placement assistance for every student.',
                'content' => '<p>We are committed to helping our students move from training into employment. Alongside technical skills, every learner receives career support including:</p>'
                    .'<ul><li>Real, industry-style projects for a job-ready portfolio</li><li>Interview preparation and mock interviews</li><li>Resume and portfolio guidance</li><li>Placement assistance and employer connects</li></ul>'
                    .'<p>Our training is designed around the skills employers ask for in CAD/CAM design, drafting and CNC programming roles. <a href="/contact">Talk to us</a> about your career goals.</p>',
                'template' => 'default',
                'status' => 'published',
                'seo_title' => 'Placement & Career Support | Global Institute & CAD CAM',
                'seo_description' => 'Real projects, interview preparation, resume guidance and placement assistance for CAD/CAM and CNC students at Global Institute & CAD CAM.',
            ],
            [
                'title' => 'Frequently Asked Questions',
                'slug' => 'faq',
                'excerpt' => 'Answers to the questions we are asked most often.',
                'content' => '<p>Answers to the questions we are asked most often. If you do not find what you need, <a href="/contact">contact us</a>.</p>'
                    .'<h3>Who can join the courses?</h3><p>Diploma, B.Tech and ITI students, and working professionals who want practical technical skills.</p>'
                    .'<h3>Do I need prior experience?</h3><p>No. Foundation courses start from the basics and build up steadily.</p>'
                    .'<h3>Are the batches flexible?</h3><p>Yes. We run morning, afternoon and evening batches through the week.</p>'
                    .'<h3>Do you provide placement assistance?</h3><p>Yes. We offer interview preparation, resume guidance and placement assistance.</p>'
                    .'<h3>How do I enroll?</h3><p>Submit an enquiry online or visit the campus; our team will guide you through the process.</p>',
                'template' => 'default',
                'status' => 'published',
                'seo_title' => 'Frequently Asked Questions | Global Institute & CAD CAM',
                'seo_description' => 'Common questions about eligibility, batches and placement support for CAD/CAM and CNC courses at Global Institute & CAD CAM, Jamshedpur.',
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'excerpt' => 'How we collect, use and protect the information you share with us.',
                'content' => '<p>This Privacy Policy explains how Global Institute &amp; CAD CAM collects, uses and protects the information you provide through this website.</p>'
                    .'<h3>Information we collect</h3><p>When you submit an enquiry or contact form, we may collect your name, phone number, email address and the message or course you are interested in.</p>'
                    .'<h3>How we use it</h3><p>We use your information only to respond to your enquiry, share course and admission details, and communicate about our programs. We do not sell your personal information.</p>'
                    .'<h3>Data protection</h3><p>We take reasonable measures to keep your information secure. Access is limited to staff who need it to respond to you.</p>'
                    .'<h3>Contact</h3><p>For any privacy question, contact us at info@globalinstitute.edu.in or +91 87899 61355.</p>',
                'template' => 'legal',
                'status' => 'published',
                'seo_title' => 'Privacy Policy | Global Institute & CAD CAM',
                'seo_description' => 'How Global Institute & CAD CAM collects, uses and protects the personal information you provide through this website.',
            ],
            [
                'title' => 'Terms & Conditions',
                'slug' => 'terms-and-conditions',
                'excerpt' => 'The terms that apply to using this website and enrolling in our programs.',
                'content' => '<p>By using this website and enrolling in our programs, you agree to these Terms &amp; Conditions.</p>'
                    .'<h3>Use of the website</h3><p>The content on this website is provided for general information about our courses, faculty and facilities. Course details, fees and batch schedules may change and are confirmed at the time of admission.</p>'
                    .'<h3>Admissions</h3><p>Admission is confirmed only after the enrollment process and applicable fees are completed. Batch allotment is subject to availability.</p>'
                    .'<h3>Intellectual property</h3><p>All course material provided is for your personal learning and may not be copied or redistributed.</p>'
                    .'<h3>Contact</h3><p>Questions about these terms? Write to info@globalinstitute.edu.in or call +91 87899 61355.</p>',
                'template' => 'legal',
                'status' => 'published',
                'seo_title' => 'Terms & Conditions | Global Institute & CAD CAM',
                'seo_description' => 'Terms and conditions for using the Global Institute & CAD CAM website and enrolling in our training programs.',
            ],
            [
                'title' => 'Refund & Cancellation Policy',
                'slug' => 'refund-policy',
                'excerpt' => 'How fees are handled if you cancel or withdraw your admission.',
                'content' => '<p>This Refund &amp; Cancellation Policy describes how fees are handled if you cancel your admission at Global Institute &amp; CAD CAM.</p>'
                    .'<h3>Cancellation</h3><p>To cancel or withdraw, inform the institute office in writing as early as possible. Please speak to our team for the applicable refund based on your course and batch.</p>'
                    .'<h3>Refunds</h3><p>Refund eligibility and the amount depend on the course, the batch stage and the time of cancellation. Registration and material charges may be non-refundable.</p>'
                    .'<h3>Contact</h3><p>For any refund or cancellation request, contact the office at +91 87899 61355 or info@globalinstitute.edu.in.</p>',
                'template' => 'legal',
                'status' => 'published',
                'seo_title' => 'Refund & Cancellation Policy | Global Institute & CAD CAM',
                'seo_description' => 'The refund and cancellation policy for course admissions at Global Institute & CAD CAM, Gamharia, Jamshedpur.',
            ],
            [
                'title' => 'Disclaimer',
                'slug' => 'disclaimer',
                'excerpt' => 'Important information about the content published on this website.',
                'content' => '<p>The information on this website is provided in good faith for general guidance about Global Institute &amp; CAD CAM, its courses and facilities.</p>'
                    .'<p>While we keep details current, course content, fees, batch timings and availability may change. Software names such as AutoCAD, SolidWorks, CATIA, Creo, NX CAD and Fusion 360 are trademarks of their respective owners and are used here only to describe the software taught.</p>'
                    .'<p>For the latest and most accurate details, please <a href="/contact">contact us</a> directly.</p>',
                'template' => 'legal',
                'status' => 'published',
                'seo_title' => 'Disclaimer | Global Institute & CAD CAM',
                'seo_description' => 'Disclaimer for the Global Institute & CAD CAM website, including content accuracy and software trademarks.',
            ],
        ];
    }
}
