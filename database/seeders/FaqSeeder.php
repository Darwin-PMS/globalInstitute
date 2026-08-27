<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'What are the eligibility criteria for enrolling in a course?',
                'answer' => 'Eligibility varies by course. Most certificate courses require a minimum of 10th pass. Diploma and advanced courses may require 12th pass or ITI/Diploma in a relevant field. Contact us for specific course requirements.',
                'category' => 'admissions',
                'status' => 'published',
                'sort_order' => 1,
            ],
            [
                'question' => 'Does the institute provide placement assistance?',
                'answer' => 'Yes, we provide 100% placement assistance to all our students. We have tie-ups with leading manufacturing companies, engineering firms, and IT companies. Our dedicated placement cell helps students with resume preparation, mock interviews, and job referrals.',
                'category' => 'placements',
                'status' => 'published',
                'sort_order' => 2,
            ],
            [
                'question' => 'What are the batch timings available?',
                'answer' => 'We offer flexible batch timings to suit different schedules. Morning batches typically run from 8:00 AM to 11:30 AM, afternoon batches from 2:00 PM to 5:30 PM, and evening batches from 6:00 PM to 8:30 PM. Specific timings may vary by course.',
                'category' => 'general',
                'status' => 'published',
                'sort_order' => 3,
            ],
            [
                'question' => 'Are there any practical/practical sessions included in the courses?',
                'answer' => 'Absolutely! All our courses include extensive hands-on practical sessions on actual industrial equipment and software. We believe in learning by doing, so at least 60% of course time is dedicated to practical training.',
                'category' => 'courses',
                'status' => 'published',
                'sort_order' => 4,
            ],
            [
                'question' => 'What is the fee structure and are installment options available?',
                'answer' => 'Course fees vary depending on the program. We offer competitive pricing and flexible payment options including installment plans. You can pay in 2-3 installments. Contact our office for detailed fee information for your chosen course.',
                'category' => 'fees',
                'status' => 'published',
                'sort_order' => 5,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }
    }
}
