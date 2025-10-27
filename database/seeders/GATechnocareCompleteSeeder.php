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
use App\Models\Certificate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class GATechnocareCompleteSeeder extends Seeder
{
    private $departments = [];
    private $users = [];
    private $categories = [];
    private $courses = [];
    
    public function run(): void
    {
        $this->command->info('🚀 Setting up GA Technocare Technology LMS...');
        
        $this->createDepartments();
        $this->createUsers();
        $this->createCategories();
        $this->createCoursesWithContent();
        $this->createEnrollmentsWithProgress();
        $this->createCertificateTemplate();
        $this->generateCompletedCertificates();
        
        $this->command->info('✅ GA Technocare Technology LMS setup complete!');
        $this->command->info('');
        $this->command->info('Login at: http://localhost:8000');
        $this->command->info('Admin: admin@test.com / password123');
    }


    
    private function createDepartments(): void
    {
        $this->command->info('Creating departments...');
        
        $depts = [
            ['name' => 'Software Development', 'code' => 'DEV', 'desc' => 'Full-stack and backend development'],
            ['name' => 'Quality Assurance', 'code' => 'QA', 'desc' => 'Software testing and quality control'],
            ['name' => 'DevOps & Cloud', 'code' => 'DEVOPS', 'desc' => 'Infrastructure and deployment'],
            ['name' => 'UI/UX Design', 'code' => 'DESIGN', 'desc' => 'User interface and experience'],
            ['name' => 'Project Management', 'code' => 'PM', 'desc' => 'Project planning and delivery'],
            ['name' => 'Human Resources', 'code' => 'HR', 'desc' => 'Employee management'],
            ['name' => 'Sales & Marketing', 'code' => 'SALES', 'desc' => 'Business development'],
        ];
        
        foreach ($depts as $dept) {
            $this->departments[] = Department::create([
                'name' => $dept['name'],
                'code' => $dept['code'],
                'description' => $dept['desc'],
            ]);
        }
    }

    
    private function createUsers(): void
    {
        $this->command->info('Creating users...');
        
        // Super Admin
        $this->users['admin'] = User::create([
            'name' => 'Gaurav Agarwal',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'department_id' => $this->departments[5]->id,
            'email_verified_at' => now(),
        ]);
        
        // Instructors
        $instructors = [
            ['name' => 'Amit Kumar', 'email' => 'amit.kumar@gatech.com', 'dept' => 0],
            ['name' => 'Sneha Patel', 'email' => 'sneha.patel@gatech.com', 'dept' => 0],
            ['name' => 'Vikram Singh', 'email' => 'vikram.singh@gatech.com', 'dept' => 2],
            ['name' => 'Anjali Reddy', 'email' => 'anjali.reddy@gatech.com', 'dept' => 3],
            ['name' => 'Rajesh Gupta', 'email' => 'rajesh.gupta@gatech.com', 'dept' => 4],
            ['name' => 'Neha Kapoor', 'email' => 'neha.kapoor@gatech.com', 'dept' => 1],
        ];
        
        $this->users['instructors'] = [];
        foreach ($instructors as $inst) {
            $this->users['instructors'][] = User::create([
                'name' => $inst['name'],
                'email' => $inst['email'],
                'password' => Hash::make('password123'),
                'role' => 'instructor',
                'department_id' => $this->departments[$inst['dept']]->id,
                'email_verified_at' => now(),
            ]);
        }
        
        // Employees
        $employees = [
            ['name' => 'Arjun Mehta', 'email' => 'arjun.mehta@gatech.com', 'dept' => 0],
            ['name' => 'Pooja Desai', 'email' => 'pooja.desai@gatech.com', 'dept' => 0],
            ['name' => 'Karan Malhotra', 'email' => 'karan.malhotra@gatech.com', 'dept' => 0],
            ['name' => 'Divya Iyer', 'email' => 'divya.iyer@gatech.com', 'dept' => 0],
            ['name' => 'Sanjay Nair', 'email' => 'sanjay.nair@gatech.com', 'dept' => 1],
            ['name' => 'Kavita Rao', 'email' => 'kavita.rao@gatech.com', 'dept' => 1],
            ['name' => 'Suresh Reddy', 'email' => 'suresh.reddy@gatech.com', 'dept' => 2],
            ['name' => 'Anita Sharma', 'email' => 'anita.sharma@gatech.com', 'dept' => 2],
            ['name' => 'Ravi Krishnan', 'email' => 'ravi.krishnan@gatech.com', 'dept' => 3],
            ['name' => 'Meera Nambiar', 'email' => 'meera.nambiar@gatech.com', 'dept' => 3],
        ];
        
        $this->users['employees'] = [];
        foreach ($employees as $emp) {
            $this->users['employees'][] = User::create([
                'name' => $emp['name'],
                'email' => $emp['email'],
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'department_id' => $this->departments[$emp['dept']]->id,
                'email_verified_at' => now(),
            ]);
        }
    }
    
    private function createCategories(): void
    {
        $this->command->info('Creating categories...');
        
        $cats = [
            ['name' => 'Web Development', 'slug' => 'web-development', 'desc' => 'Frontend and backend web technologies'],
            ['name' => 'Mobile Development', 'slug' => 'mobile-development', 'desc' => 'iOS and Android app development'],
            ['name' => 'Cloud & DevOps', 'slug' => 'cloud-devops', 'desc' => 'Cloud platforms and DevOps practices'],
            ['name' => 'Software Testing', 'slug' => 'software-testing', 'desc' => 'QA and testing methodologies'],
            ['name' => 'UI/UX Design', 'slug' => 'ui-ux-design', 'desc' => 'User interface and experience design'],
            ['name' => 'Project Management', 'slug' => 'project-management', 'desc' => 'Agile and project delivery'],
            ['name' => 'Soft Skills', 'slug' => 'soft-skills', 'desc' => 'Communication and professional development'],
        ];
        
        foreach ($cats as $cat) {
            $this->categories[] = CourseCategory::create([
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'description' => $cat['desc'],
            ]);
        }
    }
    
    private function createCoursesWithContent(): void
    {
        $this->command->info('Creating courses...');
        
        $coursesData = [
            [
                'title' => 'Full Stack Web Development with React & Node.js',
                'category' => 0,
                'instructor' => 0,
                'difficulty' => 'intermediate',
                'duration' => 240,
                'desc' => 'Master modern web development with React frontend and Node.js backend. Build scalable, production-ready applications.',
            ],
            [
                'title' => 'Mobile App Development with React Native',
                'category' => 1,
                'instructor' => 1,
                'difficulty' => 'intermediate',
                'duration' => 180,
                'desc' => 'Create cross-platform mobile applications for iOS and Android using React Native framework.',
            ],
            [
                'title' => 'AWS Cloud Architecture & DevOps',
                'category' => 2,
                'instructor' => 2,
                'difficulty' => 'advanced',
                'duration' => 200,
                'desc' => 'Learn AWS services, cloud architecture patterns, and DevOps practices for scalable applications.',
            ],
            [
                'title' => 'Automated Testing with Selenium & Jest',
                'category' => 3,
                'instructor' => 5,
                'difficulty' => 'intermediate',
                'duration' => 120,
                'desc' => 'Implement comprehensive automated testing strategies for web applications.',
            ],
            [
                'title' => 'UI/UX Design Fundamentals',
                'category' => 4,
                'instructor' => 3,
                'difficulty' => 'beginner',
                'duration' => 90,
                'desc' => 'Learn user-centered design principles, wireframing, prototyping, and usability testing.',
            ],
            [
                'title' => 'Agile Project Management & Scrum',
                'category' => 5,
                'instructor' => 4,
                'difficulty' => 'beginner',
                'duration' => 60,
                'desc' => 'Master Agile methodologies, Scrum framework, and effective team collaboration.',
            ],
            [
                'title' => 'Effective Communication for Tech Teams',
                'category' => 6,
                'instructor' => 4,
                'difficulty' => 'beginner',
                'duration' => 45,
                'desc' => 'Enhance communication skills, presentation techniques, and stakeholder management.',
            ],
        ];
        
        foreach ($coursesData as $data) {
            $course = Course::create([
                'title' => $data['title'],
                'slug' => Str::slug($data['title']),
                'description' => $data['desc'],
                'short_description' => substr($data['desc'], 0, 150),
                'category_id' => $this->categories[$data['category']]->id,
                'created_by' => $this->users['instructors'][$data['instructor']]->id,
                'difficulty_level' => $data['difficulty'],
                'estimated_duration' => $data['duration'],
                'is_published' => true,
                'is_featured' => rand(0, 1) === 1,
            ]);
            
            $this->createCourseContent($course);
            $this->courses[] = $course;
        }
    }
    
    private function createCourseContent($course): void
    {
        for ($m = 1; $m <= 3; $m++) {
            $module = CourseModule::create([
                'course_id' => $course->id,
                'title' => "Module $m: " . $this->getModuleTitle($m),
                'description' => "Comprehensive coverage of key concepts and practical applications.",
                'order_index' => $m,
                'estimated_duration' => 60,
            ]);
            
            for ($l = 1; $l <= 4; $l++) {
                $lessonTypes = ['video', 'pdf', 'text'];
                $type = $lessonTypes[array_rand($lessonTypes)];
                
                $lessonData = [
                    'module_id' => $module->id,
                    'title' => "Lesson $l: " . $this->getLessonTitle($l),
                    'description' => "Detailed explanation with examples and exercises.",
                    'content_type' => $type,
                    'duration' => rand(15, 30),
                    'order_index' => $l,
                    'is_published' => true,
                ];
                
                if ($type === 'text') {
                    $lessonData['content_text'] = '<h2>Lesson Content</h2><p>This lesson covers important concepts with practical examples. Study the material carefully and complete the exercises.</p>';
                } else {
                    $lessonData['content_path'] = 'sample-' . $type . '-file.ext';
                }
                
                CourseLesson::create($lessonData);
            }
            
            $this->createAssessment($course, $module, $m);
        }
    }
    
    private function createAssessment($course, $module, $moduleNumber): void
    {
        $assessment = Assessment::create([
            'course_id' => $course->id,
            'title' => "Module $moduleNumber Assessment",
            'description' => "Test your understanding of the concepts covered in this module.",
            'instructions' => "Answer all questions carefully. You have 30 minutes to complete this assessment.",
            'passing_score' => 70,
            'time_limit' => 30,
            'max_attempts' => 3,
            'randomize_questions' => true,
            'show_results' => true,
            'is_published' => true,
            'created_by' => $course->created_by,
        ]);
        
        for ($q = 1; $q <= 10; $q++) {
            $question = Question::create([
                'assessment_id' => $assessment->id,
                'question_text' => $this->getQuestionText($q, $course->title),
                'question_type' => 'multiple_choice',
                'points' => 10,
                'order_index' => $q,
                'created_by' => $course->created_by,
            ]);
            
            $correctOption = rand(1, 4);
            for ($o = 1; $o <= 4; $o++) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => $this->getOptionText($o),
                    'is_correct' => $o === $correctOption,
                    'order_index' => $o,
                ]);
            }
        }
    }
    
    private function getModuleTitle($num): string
    {
        $titles = [
            'Fundamentals and Core Concepts',
            'Advanced Techniques and Best Practices',
            'Real-World Applications and Projects',
        ];
        return $titles[$num - 1] ?? "Module Content $num";
    }
    
    private function getLessonTitle($num): string
    {
        $titles = [
            'Introduction and Overview',
            'Key Concepts Explained',
            'Practical Implementation',
            'Summary and Best Practices',
        ];
        return $titles[$num - 1] ?? "Lesson Content $num";
    }
    
    private function getQuestionText($num, $courseTitle): string
    {
        $questions = [
            "What is the primary objective of this course module?",
            "Which approach is recommended for implementing the concepts?",
            "How would you apply these principles in a real project?",
            "What are the key benefits of using this methodology?",
            "Which statement best describes the best practice?",
            "What is the recommended workflow for this process?",
            "How does this concept integrate with other technologies?",
            "What is the most important consideration when implementing?",
            "Which factor has the greatest impact on performance?",
            "What is the correct sequence of steps in this process?",
        ];
        return $questions[$num - 1] ?? "Sample question $num for $courseTitle";
    }
    
    private function getOptionText($num): string
    {
        $options = [
            'This is a comprehensive and correct answer',
            'This is a partially correct answer',
            'This is an incorrect but plausible answer',
            'This is clearly an incorrect answer',
        ];
        return $options[$num - 1] ?? "Option $num";
    }
    
    private function createEnrollmentsWithProgress(): void
    {
        $this->command->info('Creating enrollments...');
        
        foreach ($this->users['employees'] as $employee) {
            foreach ($this->courses as $index => $course) {
                $isCompleted = $index < 2;
                $progress = $isCompleted ? 100 : rand(20, 80);
                
                $enrollment = Enrollment::create([
                    'user_id' => $employee->id,
                    'course_id' => $course->id,
                    'enrollment_type' => 'assigned',
                    'enrollment_date' => now()->subDays(rand(1, 30)),
                    'status' => $isCompleted ? 'completed' : 'active',
                    'progress_percentage' => $progress,
                    'completion_date' => $isCompleted ? now()->subDays(rand(1, 10)) : null,
                    'last_accessed_at' => now()->subDays(rand(0, 5)),
                ]);
                
                $lessons = CourseLesson::whereHas('module', function($q) use ($course) {
                    $q->where('course_id', $course->id);
                })->get();
                
                $completedCount = (int)($lessons->count() * ($progress / 100));
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
        $this->command->info('Creating certificate template...');
        
        CertificateTemplate::create([
            'name' => 'GA Technocare Certificate',
            'description' => 'Official course completion certificate',
            'html_template' => '<div style="text-align: center; padding: 60px; font-family: Arial, sans-serif; border: 10px solid #2c3e50;">
                <h1 style="color: #2c3e50; font-size: 48px; margin-bottom: 20px;">Certificate of Completion</h1>
                <p style="font-size: 18px; color: #7f8c8d;">This certifies that</p>
                <h2 style="color: #3498db; font-size: 36px; margin: 20px 0;">{student_name}</h2>
                <p style="font-size: 18px; color: #7f8c8d;">has successfully completed</p>
                <h3 style="color: #2c3e50; font-size: 28px; margin: 20px 0;">{course_title}</h3>
                <p style="font-size: 16px; color: #7f8c8d; margin-top: 30px;">Completion Date: {completion_date}</p>
                <p style="font-size: 14px; color: #95a5a6; margin-top: 40px;">Certificate Number: {certificate_number}</p>
                <p style="font-size: 16px; color: #2c3e50; margin-top: 40px; font-weight: bold;">GA Technocare Technology</p>
            </div>',
            'variables' => json_encode(['student_name', 'course_title', 'completion_date', 'certificate_number']),
            'orientation' => 'landscape',
            'page_size' => 'A4',
            'is_default' => true,
            'is_active' => true,
            'created_by' => $this->users['admin']->id,
        ]);
    }
    
    private function generateCompletedCertificates(): void
    {
        $this->command->info('Generating certificates...');
        
        $template = CertificateTemplate::first();
        $completedEnrollments = Enrollment::where('status', 'completed')->get();
        
        foreach ($completedEnrollments as $enrollment) {
            Certificate::create([
                'enrollment_id' => $enrollment->id,
                'user_id' => $enrollment->user_id,
                'course_id' => $enrollment->course_id,
                'template_id' => $template->id,
                'certificate_number' => 'GATC-' . strtoupper(Str::random(8)),
                'issued_at' => $enrollment->completion_date,
                'expires_at' => null,
                'is_valid' => true,
            ]);
        }
    }
}
