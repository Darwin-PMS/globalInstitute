<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Trainer;
use App\Models\Batch;
use App\Models\CourseModule;
use App\Models\FeeStructure;
use App\Models\FeeStructureItem;
use App\Models\Student;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\FeePlan;
use App\Models\FeeInstallment;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InstituteDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRooms();
        $this->seedTeachers();
        $this->seedCourseModules();
        $this->seedFeeStructures();
        $this->seedStudents();
        $this->seedBatches();
        $this->seedEnrollments();
    }

    private function seedRooms(): void
    {
        $rooms = [
            ['name' => 'Main Lab 1', 'room_number' => 'ML-01', 'capacity' => 40, 'type' => 'computer_lab', 'status' => 'active'],
            ['name' => 'Main Lab 2', 'room_number' => 'ML-02', 'capacity' => 40, 'type' => 'computer_lab', 'status' => 'active'],
            ['name' => 'CAD Lab', 'room_number' => 'CL-01', 'capacity' => 30, 'type' => 'cad_lab', 'status' => 'active'],
            ['name' => 'CNC Lab', 'room_number' => 'NL-01', 'capacity' => 20, 'type' => 'cnc_lab', 'status' => 'active'],
            ['name' => 'Workshop', 'room_number' => 'WS-01', 'capacity' => 50, 'type' => 'workshop', 'status' => 'active'],
            ['name' => 'Lecture Hall A', 'room_number' => 'LH-A', 'capacity' => 60, 'type' => 'classroom', 'status' => 'active'],
            ['name' => 'Lecture Hall B', 'room_number' => 'LH-B', 'capacity' => 60, 'type' => 'classroom', 'status' => 'active'],
            ['name' => 'Seminar Room', 'room_number' => 'SR-01', 'capacity' => 25, 'type' => 'classroom', 'status' => 'active'],
        ];

        foreach ($rooms as $room) {
            Room::updateOrCreate(['room_number' => $room['room_number']], $room);
        }
    }

    private function seedTeachers(): void
    {
        $teachers = [
            ['name' => 'Dr. Rajesh Patel', 'phone' => '9876543210', 'email' => 'rajesh@global.com', 'qualification' => 'Ph.D. Mechanical Engineering', 'experience' => 15, 'specialization' => 'CAD/CAM Design', 'employment_type' => 'full_time', 'status' => 'active', 'joining_date' => '2020-01-15'],
            ['name' => 'Prof. Sneha Sharma', 'phone' => '9876543211', 'email' => 'sneha@global.com', 'qualification' => 'M.Tech Computer Science', 'experience' => 10, 'specialization' => 'Programming & Web Development', 'employment_type' => 'full_time', 'status' => 'active', 'joining_date' => '2021-06-01'],
            ['name' => 'Mr. Amit Kumar', 'phone' => '9876543212', 'email' => 'amit@global.com', 'qualification' => 'MCA', 'experience' => 8, 'specialization' => 'AutoCAD & CATIA', 'employment_type' => 'full_time', 'status' => 'active', 'joining_date' => '2022-03-10'],
            ['name' => 'Dr. Priya Verma', 'phone' => '9876543213', 'email' => 'priya@global.com', 'qualification' => 'Ph.D. Industrial Engineering', 'experience' => 12, 'specialization' => 'CNC Programming', 'employment_type' => 'visiting', 'status' => 'active', 'joining_date' => '2023-01-05'],
            ['name' => 'Mr. Sanjay Joshi', 'phone' => '9876543214', 'email' => 'sanjay@global.com', 'qualification' => 'B.Tech + Master Diploma', 'experience' => 6, 'specialization' => 'SolidWorks & ANSYS', 'employment_type' => 'part_time', 'status' => 'active', 'joining_date' => '2023-07-15'],
            ['name' => 'Ms. Kavita Desai', 'phone' => '9876543215', 'email' => 'kavita@global.com', 'qualification' => 'M.Tech Digital Manufacturing', 'experience' => 5, 'specialization' => '3D Printing & Additive Manufacturing', 'employment_type' => 'full_time', 'status' => 'active', 'joining_date' => '2024-01-10'],
        ];

        foreach ($teachers as $teacher) {
            Trainer::updateOrCreate(['email' => $teacher['email']], $teacher);
        }
    }

    private function seedCourseModules(): void
    {
        $courseIds = \App\Models\Course::pluck('id')->toArray();
        if (empty($courseIds)) return;

        $modules = [
            ['course_id' => $courseIds[0] ?? 1, 'name' => 'Introduction & Basics', 'description' => 'Fundamental concepts and overview', 'duration' => '2 weeks', 'sequence' => 1, 'status' => 'active'],
            ['course_id' => $courseIds[0] ?? 1, 'name' => 'Core Skills', 'description' => 'Primary skill development', 'duration' => '4 weeks', 'sequence' => 2, 'status' => 'active'],
            ['course_id' => $courseIds[0] ?? 1, 'name' => 'Advanced Topics', 'description' => 'Advanced concepts and techniques', 'duration' => '4 weeks', 'sequence' => 3, 'status' => 'active'],
            ['course_id' => $courseIds[0] ?? 1, 'name' => 'Project Work', 'description' => 'Hands-on project implementation', 'duration' => '2 weeks', 'sequence' => 4, 'status' => 'active'],
            ['course_id' => $courseIds[1] ?? 2, 'name' => 'Foundation Module', 'description' => 'Basic concepts and tools', 'duration' => '3 weeks', 'sequence' => 1, 'status' => 'active'],
            ['course_id' => $courseIds[1] ?? 2, 'name' => 'Practical Application', 'description' => 'Real-world project applications', 'duration' => '5 weeks', 'sequence' => 2, 'status' => 'active'],
            ['course_id' => $courseIds[1] ?? 2, 'name' => 'Industry Preparation', 'description' => 'Industry-standard practices and portfolio', 'duration' => '4 weeks', 'sequence' => 3, 'status' => 'active'],
        ];

        foreach ($modules as $module) {
            CourseModule::create($module);
        }
    }

    private function seedFeeStructures(): void
    {
        $courses = \App\Models\Course::all();
        if ($courses->isEmpty()) return;

        foreach ($courses as $course) {
            $fs = FeeStructure::create([
                'course_id' => $course->id,
                'name' => $course->title . ' - Standard Fee',
                'effective_from' => '2026-01-01',
                'effective_to' => '2026-12-31',
                'total_amount' => $course->fees ?? 15000,
                'status' => 'active',
            ]);

            FeeStructureItem::insert([
                ['fee_structure_id' => $fs->id, 'fee_type' => 'registration', 'name' => 'Registration Fee', 'amount' => 1000, 'mandatory' => true, 'sequence' => 1],
                ['fee_structure_id' => $fs->id, 'fee_type' => 'tuition', 'name' => 'Tuition Fee', 'amount' => ($course->fees ?? 15000) * 0.6, 'mandatory' => true, 'sequence' => 2],
                ['fee_structure_id' => $fs->id, 'fee_type' => 'material', 'name' => 'Study Material', 'amount' => ($course->fees ?? 15000) * 0.2, 'mandatory' => true, 'sequence' => 3],
                ['fee_structure_id' => $fs->id, 'fee_type' => 'examination', 'name' => 'Examination Fee', 'amount' => ($course->fees ?? 15000) * 0.1, 'mandatory' => true, 'sequence' => 4],
                ['fee_structure_id' => $fs->id, 'fee_type' => 'certificate', 'name' => 'Certificate Fee', 'amount' => ($course->fees ?? 15000) * 0.1, 'mandatory' => true, 'sequence' => 5],
            ]);
        }
    }

    private function seedStudents(): void
    {
        $students = [
            ['name' => 'Rahul Mehta', 'email' => 'rahul@student.com', 'phone' => '9900110001', 'father_name' => 'Mr. Suresh Mehta', 'mother_name' => 'Mrs. Anita Mehta', 'date_of_birth' => '2002-05-15', 'gender' => 'male', 'whatsapp' => '9900110001', 'city' => 'Pune', 'state' => 'Maharashtra', 'pin_code' => '411001', 'qualification' => 'B.E. Mechanical', 'institution' => 'SPPU', 'passing_year' => '2024', 'emergency_contact_name' => 'Suresh Mehta', 'emergency_relationship' => 'Father', 'emergency_contact_number' => '9900110000', 'status' => 'active'],
            ['name' => 'Priya Jadhav', 'email' => 'priya.j@student.com', 'phone' => '9900110002', 'father_name' => 'Mr. Ramesh Jadhav', 'mother_name' => 'Mrs. Sunita Jadhav', 'date_of_birth' => '2003-08-22', 'gender' => 'female', 'whatsapp' => '9900110002', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'pin_code' => '400001', 'qualification' => 'B.Sc Computer Science', 'institution' => 'MU', 'passing_year' => '2024', 'emergency_contact_name' => 'Ramesh Jadhav', 'emergency_relationship' => 'Father', 'emergency_contact_number' => '9900110003', 'status' => 'active'],
            ['name' => 'Arjun Singh', 'email' => 'arjun@student.com', 'phone' => '9900110004', 'father_name' => 'Mr. Harpal Singh', 'mother_name' => 'Mrs. Gurnam Kaur', 'date_of_birth' => '2001-12-03', 'gender' => 'male', 'whatsapp' => '9900110004', 'city' => 'Nagpur', 'state' => 'Maharashtra', 'pin_code' => '440001', 'qualification' => 'Diploma Civil', 'institution' => 'MSBTE', 'passing_year' => '2023', 'emergency_contact_name' => 'Harpal Singh', 'emergency_relationship' => 'Father', 'emergency_contact_number' => '9900110005', 'status' => 'active'],
            ['name' => 'Neha Gupta', 'email' => 'neha@student.com', 'phone' => '9900110006', 'father_name' => 'Mr. Ashok Gupta', 'mother_name' => 'Mrs. Kiran Gupta', 'date_of_birth' => '2002-03-18', 'gender' => 'female', 'whatsapp' => '9900110006', 'city' => 'Nashik', 'state' => 'Maharashtra', 'pin_code' => '422001', 'qualification' => 'B.Com', 'institution' => 'SNDT', 'passing_year' => '2024', 'emergency_contact_name' => 'Ashok Gupta', 'emergency_relationship' => 'Father', 'emergency_contact_number' => '9900110007', 'status' => 'active'],
            ['name' => 'Vikram Patil', 'email' => 'vikram@student.com', 'phone' => '9900110008', 'father_name' => 'Mr. Ganesh Patil', 'mother_name' => 'Mrs. Lata Patil', 'date_of_birth' => '2000-11-25', 'gender' => 'male', 'whatsapp' => '9900110008', 'city' => 'Satara', 'state' => 'Maharashtra', 'pin_code' => '415001', 'qualification' => 'B.E. Electronics', 'institution' => 'SPPU', 'passing_year' => '2023', 'emergency_contact_name' => 'Ganesh Patil', 'emergency_relationship' => 'Father', 'emergency_contact_number' => '9900110009', 'status' => 'active'],
        ];

        $counter = Student::count();
        foreach ($students as $data) {
            $counter++;
            $studentId = 'GIS-' . date('Y') . '-' . str_pad($counter, 5, '0', STR_PAD_LEFT);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'role' => 'student',
                'password' => bcrypt('password'),
            ]);

            unset($data['email'], $data['phone'], $data['name']);
            $data['student_id'] = $studentId;
            $data['user_id'] = $user->id;
            if (!isset($data['address'])) $data['address'] = '';

            Student::create($data);
        }
    }

    private function seedBatches(): void
    {
        $courses = \App\Models\Course::all();
        $trainers = Trainer::all();
        $rooms = Room::all();

        if ($courses->isEmpty()) return;

        foreach ($courses as $index => $course) {
            $trainer = $trainers[$index % $trainers->count()] ?? $trainers->first();
            $room = $rooms[$index % $rooms->count()] ?? $rooms->first();

            Batch::updateOrCreate(
                ['batch_code' => 'B' . date('Y') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT)],
                [
                    'course_id' => $course->id,
                    'batch_name' => $course->title . ' Batch ' . ($index + 1),
                    'start_date' => Carbon::now()->subMonths(2)->startOfMonth()->toDateString(),
                    'end_date' => Carbon::now()->addMonths(4)->endOfMonth()->toDateString(),
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'days' => 'Mon,Tue,Wed,Thu,Fri',
                    'capacity' => 40,
                    'current_strength' => rand(15, 35),
                    'trainer_id' => $trainer?->id,
                    'room_id' => $room?->id,
                    'status' => 'active',
                ]
            );
        }
    }

    private function seedEnrollments(): void
    {
        $students = Student::with('user')->get();
        $batches = Batch::all();
        $courses = \App\Models\Course::all();

        if ($students->isEmpty() || $batches->isEmpty()) return;

        $counter = Enrollment::count();
        foreach ($students as $index => $student) {
            $batch = $batches[$index % $batches->count()];
            $course = $courses->where('id', $batch->course_id)->first() ?? $courses->first();
            $fs = FeeStructure::where('course_id', $course->id)->first();

            $counter++;
            $enrollment = Enrollment::create([
                'enrollment_number' => 'ENR-' . date('Y') . '-' . str_pad($counter, 6, '0', STR_PAD_LEFT),
                'student_id' => $student->id,
                'course_id' => $course->id,
                'batch_id' => $batch->id,
                'enrollment_date' => Carbon::now()->subDays(rand(10, 60))->toDateString(),
                'start_date' => $batch->start_date,
                'expected_end_date' => $batch->end_date,
                'fee_structure_id' => $fs?->id,
                'total_fee' => $course->fees ?? 15000,
                'discount_amount' => 0,
                'final_fee' => $course->fees ?? 15000,
                'total_paid' => 0,
                'balance' => $course->fees ?? 15000,
                'fee_status' => 'pending',
                'status' => 'active',
            ]);

            $plan = FeePlan::create([
                'enrollment_id' => $enrollment->id,
                'plan_name' => 'Full Course',
                'total_amount' => $enrollment->final_fee,
                'total_installments' => 3,
                'status' => 'active',
            ]);

            $installmentAmount = round($enrollment->final_fee / 3);
            for ($i = 1; $i <= 3; $i++) {
                $amount = ($i === 3) ? $enrollment->final_fee - ($installmentAmount * 2) : $installmentAmount;
                FeeInstallment::create([
                    'fee_plan_id' => $plan->id,
                    'installment_number' => $i,
                    'amount' => $amount,
                    'due_date' => Carbon::now()->addMonths($i - 1)->toDateString(),
                    'paid_amount' => 0,
                    'balance' => $amount,
                    'status' => 'pending',
                ]);
            }

            if ($index % 3 === 0) {
                $installment = $plan->installments()->first();
                $paymentAmount = min(5000, $installment->amount);

                $payment = Payment::create([
                    'payment_id' => 'PAY-' . date('Y') . '-' . str_pad($counter, 6, '0', STR_PAD_LEFT),
                    'student_id' => $student->id,
                    'enrollment_id' => $enrollment->id,
                    'fee_installment_id' => $installment->id,
                    'amount' => $paymentAmount,
                    'payment_date' => Carbon::now()->subDays(rand(1, 15))->toDateString(),
                    'payment_method' => collect(['cash', 'gpay', 'upi', 'bank_transfer'])->random(),
                    'status' => 'completed',
                    'notes' => 'Initial payment',
                    'received_by' => User::where('is_admin', true)->first()?->id,
                ]);

                Receipt::create([
                    'receipt_number' => 'REC-' . date('Y') . '-' . str_pad($counter, 6, '0', STR_PAD_LEFT),
                    'payment_id' => $payment->id,
                    'student_id' => $student->id,
                    'enrollment_id' => $enrollment->id,
                    'amount_paid' => $paymentAmount,
                    'previous_balance' => $enrollment->final_fee,
                    'remaining_balance' => $enrollment->final_fee - $paymentAmount,
                    'payment_method' => $payment->payment_method,
                    'payment_date' => $payment->payment_date,
                    'received_by' => $payment->received_by,
                ]);

                $installment->update([
                    'paid_amount' => $paymentAmount,
                    'balance' => $installment->amount - $paymentAmount,
                    'status' => $paymentAmount >= $installment->amount ? 'paid' : 'partial',
                ]);

                $totalPaid = $enrollment->installments()->sum('paid_amount');
                $enrollment->update([
                    'total_paid' => $totalPaid,
                    'balance' => $enrollment->final_fee - $totalPaid,
                    'fee_status' => $totalPaid >= $enrollment->final_fee ? 'paid' : ($totalPaid > 0 ? 'partial' : 'pending'),
                ]);
            }
        }
    }
}
