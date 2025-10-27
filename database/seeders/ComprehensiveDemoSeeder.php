<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\CourseCategory;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseLesson;
use App\Models\Assessment;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\CertificateTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ComprehensiveDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Create Departments
        $departments = $this->createDepartments();
        
        // Create Users (5 of each type)
        $users = $this->createUsers($departments);
        
        // Create Course Categories
        $categories = $this->createCategories();
        
        // Create Courses with full content
        $courses = $this->createCourses($categories, $users['instructors']);
        
        // Create Enrollments
        $this->createEnrollments($courses, $users);
        
        // Create Certificate Template
        $this->createCertificateTemplate();
        
        $this->command->info('✅ Comprehensive demo data created successfully!');
    }

    private function createDepartments(): array
    {
        $depts = [
            'Human Resources' => ['code' => 'HR', 'description' => 'Manages employee relations and development'],
            'Information Technology' => ['code' => 'IT', 'description' => 'Technology and systems management'],
            'Sales' => ['code' => 'SALES', 'description' => 'Revenue generation and customer relations'],
            'Marketing' => ['code' => 'MKT', 'description' => 'Brand and product promotion'],
            'Finance' => ['code' => 'FIN', 'description' => 'Financial planning and management'],
            'Operations' => ['code' => 'OPS', 'description' => 'Business operations and logistics'],
        ];

        $departments = [];
        foreach ($depts as $name => $data) {
            $departments[] = Department::create([
                'name' => $name,
                'code' => $data['code'],
                'description' => $data['description'],
            ]);
        }

        return $departments;
    }

    private function createUsers($departments): array
    {
        $users = [
            'admins' => [],
            'instructors' => [],
            'employees' => [],
        ];

        // Create 5 Admins
        for ($i = 1; $i <= 5; $i++) {
            $users['admins'][] = User::create([
                'name' => "Admin User $i",
                'email' => "admin$i@test.com",
                'password' => Hash::make('password123'),
                'role' => $i === 1 ? 'super_admin' : 'admin',
                'department_id' => $departments[array_rand($departments)]->id,
                'email_verified_at' => now(),
            ]);
        }

        // Create 5 Instructors
        $instructorNames = [
            'Dr. Sarah Johnson',
            'Prof. Michael Chen',
            'Dr. Emily Rodriguez',
            'Prof. David Kim',
            'Dr. Lisa Anderson',
        ];

        foreach ($instructorNames as $i => $name) {
            $users['instructors'][] = User::create([
                'name' => $name,
                'email' => "instructor" . ($i + 1) . "@test.com",
                'password' => Hash::make('password123'),
                'role' => 'instructor',
                'department_id' => $departments[array_rand($departments)]->id,
                'email_verified_at' => now(),
            ]);
        }

        // Create 5 Employees
        $employeeNames = [
            'John Smith',
            'Maria Garcia',
            'James Wilson',
            'Jennifer Lee',
            'Robert Brown',
        ];

        foreach ($employeeNames as $i => $name) {
            $users['employees'][] = User::create([
                'name' => $name,
                'email' => "employee" . ($i + 1) . "@test.com",
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'department_id' => $departments[array_rand($departments)]->id,
                'email_verified_at' => now(),
            ]);
        }

        return $users;
    }

    private function createCategories(): array
    {
        $categories = [
            'Professional Development' => 'Career growth and professional skills',
            'Technical Skills' => 'Technology and technical competencies',
            'Leadership' => 'Management and leadership development',
            'Compliance' => 'Regulatory and compliance training',
            'Soft Skills' => 'Communication and interpersonal skills',
            'Sales & Marketing' => 'Sales techniques and marketing strategies',
        ];

        $created = [];
        foreach ($categories as $name => $description) {
            $created[] = CourseCategory::create([
                'name' => $name,
                'description' => $description,
                'slug' => \Illuminate\Support\Str::slug($name),
            ]);
        }

        return $created;
    }

    private function createCourses($categories, $instructors): array
    {
        $coursesData = [
            [
                'title' => 'Introduction to Project Management',
                'description' => 'Learn the fundamentals of project management including planning, execution, and monitoring.',
                'category' => 0,
                'instructor' => 0,
                'difficulty' => 'beginner',
                'duration' => 120,
            ],
            [
                'title' => 'Advanced Data Analytics',
                'description' => 'Master data analysis techniques using modern tools and methodologies.',
                'category' => 1,
                'instructor' => 1,
                'difficulty' => 'advanced',
                'duration' => 180,
            ],
            [
                'title' => 'Effective Leadership Skills',
                'description' => 'Develop essential leadership skills to inspire and manage teams effectively.',
                'category' => 2,
                'instructor' => 2,
                'difficulty' => 'intermediate',
                'duration' => 90,
            ],
            [
                'title' => 'Workplace Safety and Compliance',
                'description' => 'Understand workplace safety regulations and compliance requirements.',
                'category' => 3,
                'instructor' => 3,
                'difficulty' => 'beginner',
                'duration' => 60,
            ],
            [
                'title' => 'Communication Excellence',
                'description' => 'Enhance your communication skills for professional success.',
                'category' => 4,
                'instructor' => 4,
                'difficulty' => 'beginner',
                'duration' => 75,
            ],
        ];

        $courses = [];
        foreach ($coursesData as $data) {
            $course = Course::create([
                'title' => $data['title'],
                'slug' => \Illuminate\Support\Str::slug($data['title']),
                'description' => $data['description'],
                'short_description' => substr($data['description'], 0, 150),
                'category_id' => $categories[$data['category']]->id,
                'created_by' => $instructors[$data['instructor']]->id,
                'difficulty_level' => $data['difficulty'],
                'estimated_duration' => $data['duration'],
                'is_published' => true,
                'is_featured' => rand(0, 1) === 1,
            ]);

            // Create modules and lessons for each course
            $this->createCourseContent($course);
            
            $courses[] = $course;
        }

        return $courses;
    }

    private function createCourseContent($course): void
    {
        // Create 3 modules per course
        for ($m = 1; $m <= 3; $m++) {
            $module = CourseModule::create([
                'course_id' => $course->id,
                'title' => "Module $m: " . $this->getModuleTitle($m),
                'description' => "This module covers important concepts and practical applications.",
                'order_index' => $m,
            ]);

            // Create 4 lessons per module
            for ($l = 1; $l <= 4; $l++) {
                $lessonTypes = ['video', 'pdf', 'text'];
                $type = $lessonTypes[array_rand($lessonTypes)];
                
                $lessonData = [
                    'module_id' => $module->id,
                    'title' => "Lesson $l: " . $this->getLessonTitle($l),
                    'content_type' => $type,
                    'duration' => rand(10, 30),
                    'order_index' => $l,
                ];
                
                if ($type === 'text') {
                    $lessonData['content_text'] = $this->getLessonContent($type);
                } else {
                    $lessonData['content_path'] = $this->getLessonContent($type);
                }
                
                CourseLesson::create($lessonData);
            }

            // Create assessment for each module
            $this->createAssessment($course, $module, $m);
        }
    }

    private function createAssessment($course, $module, $moduleNumber): void
    {
        $assessment = Assessment::create([
            'course_id' => $course->id,
            'title' => "Module $moduleNumber Assessment",
            'description' => "Test your knowledge of the concepts covered in this module.",
            'instructions' => "Answer all questions to the best of your ability. You have 30 minutes to complete this assessment.",
            'passing_score' => 70,
            'time_limit' => 30,
            'max_attempts' => 3,
            'randomize_questions' => true,
            'show_results' => true,
            'is_published' => true,
            'created_by' => $course->created_by,
        ]);

        // Create 10 questions per assessment
        for ($q = 1; $q <= 10; $q++) {
            $question = Question::create([
                'assessment_id' => $assessment->id,
                'question_text' => "Question $q: " . $this->getQuestionText($q),
                'question_type' => 'multiple_choice',
                'points' => 10,
                'order_index' => $q,
                'created_by' => $course->created_by,
            ]);

            // Create 4 options per question
            $correctOption = rand(1, 4);
            for ($o = 1; $o <= 4; $o++) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => "Option $o: " . $this->getOptionText($o),
                    'is_correct' => $o === $correctOption,
                    'order_index' => $o,
                ]);
            }
        }
    }

    private function createEnrollments($courses, $users): void
    {
        // Enroll all employees in all courses with varying progress
        foreach ($users['employees'] as $employee) {
            foreach ($courses as $index => $course) {
                $enrollment = Enrollment::create([
                    'user_id' => $employee->id,
                    'course_id' => $course->id,
                    'enrollment_type' => 'assigned',
                    'enrollment_date' => now()->subDays(rand(1, 30)),
                    'status' => $index < 2 ? 'completed' : 'active',
                    'progress_percentage' => $index < 2 ? 100 : rand(20, 80),
                    'completion_date' => $index < 2 ? now()->subDays(rand(1, 10)) : null,
                    'last_accessed_at' => now()->subDays(rand(0, 5)),
                ]);

                // Create lesson progress for some lessons
                $lessons = CourseLesson::whereHas('module', function($q) use ($course) {
                    $q->where('course_id', $course->id);
                })->get();

                $completedCount = (int)($lessons->count() * ($enrollment->progress_percentage / 100));
                foreach ($lessons->take($completedCount) as $lesson) {
                    LessonProgress::create([
                        'enrollment_id' => $enrollment->id,
                        'lesson_id' => $lesson->id,
                        'is_completed' => true,
                        'time_spent' => rand(300, 1800),
                        'started_at' => now()->subDays(rand(5, 25)),
                        'completed_at' => now()->subDays(rand(1, 20)),
                        'last_accessed_at' => now()->subDays(rand(0, 5)),
                    ]);
                }
            }
        }
    }

    private function createCertificateTemplate(): void
    {
        // Get the first admin user
        $admin = User::where('role', 'super_admin')->orWhere('role', 'admin')->first();
        
        CertificateTemplate::create([
            'name' => 'Default Certificate',
            'description' => 'Standard certificate template for course completion',
            'html_template' => '<div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">
                <h1 style="color: #2c3e50; font-size: 48px; margin-bottom: 20px;">Certificate of Completion</h1>
                <p style="font-size: 18px; color: #7f8c8d;">This certifies that</p>
                <h2 style="color: #3498db; font-size: 36px; margin: 20px 0;">{student_name}</h2>
                <p style="font-size: 18px; color: #7f8c8d;">has successfully completed</p>
                <h3 style="color: #2c3e50; font-size: 28px; margin: 20px 0;">{course_title}</h3>
                <p style="font-size: 16px; color: #7f8c8d; margin-top: 30px;">Completion Date: {completion_date}</p>
                <p style="font-size: 14px; color: #95a5a6; margin-top: 40px;">Certificate Number: {certificate_number}</p>
            </div>',
            'variables' => json_encode(['student_name', 'course_title', 'completion_date', 'certificate_number']),
            'orientation' => 'landscape',
            'page_size' => 'A4',
            'is_default' => true,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
    }

    private function getModuleTitle($num): string
    {
        $titles = [
            'Fundamentals and Core Concepts',
            'Advanced Techniques and Applications',
            'Best Practices and Real-World Examples',
        ];
        return $titles[$num - 1] ?? "Module Content $num";
    }

    private function getLessonTitle($num): string
    {
        $titles = [
            'Introduction and Overview',
            'Key Concepts Explained',
            'Practical Applications',
            'Summary and Review',
        ];
        return $titles[$num - 1] ?? "Lesson Content $num";
    }

    private function getLessonContent($type): string
    {
        if ($type === 'text') {
            return '<h2>Lesson Content</h2><p>This lesson covers important concepts that will help you understand the subject matter. Pay close attention to the key points and examples provided.</p><p>Remember to take notes and review the material regularly for best results.</p>';
        }
        return 'sample-content-' . $type . '.file';
    }

    private function getQuestionText($num): string
    {
        $questions = [
            'What is the primary purpose of this concept?',
            'Which of the following best describes the methodology?',
            'How would you apply this principle in practice?',
            'What are the key benefits of this approach?',
            'Which statement is most accurate?',
            'What is the recommended best practice?',
            'How does this concept relate to real-world scenarios?',
            'What is the most important consideration?',
            'Which factor has the greatest impact?',
            'What is the correct sequence of steps?',
        ];
        return $questions[$num - 1] ?? "Sample question $num";
    }

    private function getOptionText($num): string
    {
        $options = [
            'This is the first possible answer',
            'This is the second possible answer',
            'This is the third possible answer',
            'This is the fourth possible answer',
        ];
        return $options[$num - 1] ?? "Option $num";
    }
}
