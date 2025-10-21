<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Create a notification for a user
     */
    public function create(User $user, string $type, string $title, string $message, ?array $data = null, ?string $actionUrl = null): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'action_url' => $actionUrl,
        ]);
    }

    /**
     * Create notifications for multiple users
     */
    public function createForUsers(Collection $users, string $type, string $title, string $message, ?array $data = null, ?string $actionUrl = null): void
    {
        foreach ($users as $user) {
            $this->create($user, $type, $title, $message, $data, $actionUrl);
        }
    }

    /**
     * Get unread notifications for a user
     */
    public function getUnread(User $user, int $limit = 10): Collection
    {
        return Notification::where('user_id', $user->id)
            ->unread()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all notifications for a user
     */
    public function getAll(User $user, int $limit = 50): Collection
    {
        return Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(User $user): void
    {
        Notification::where('user_id', $user->id)
            ->unread()
            ->update(['read_at' => now()]);
    }

    /**
     * Delete a notification
     */
    public function delete(Notification $notification): void
    {
        $notification->delete();
    }

    /**
     * Get unread count for a user
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->unread()
            ->count();
    }

    /**
     * Send course enrollment notification
     */
    public function notifyCourseEnrollment(User $user, $course): Notification
    {
        return $this->create(
            $user,
            'course_enrolled',
            'New Course Enrollment',
            "You have been enrolled in {$course->title}",
            ['course_id' => $course->id],
            route('enrollments.show', $course->enrollments()->where('user_id', $user->id)->first())
        );
    }

    /**
     * Send certificate issued notification
     */
    public function notifyCertificateIssued(User $user, $certificate): Notification
    {
        return $this->create(
            $user,
            'certificate_issued',
            'Certificate Issued',
            "Congratulations! You've earned a certificate for {$certificate->course->title}",
            ['certificate_id' => $certificate->id],
            route('certificates.show', $certificate)
        );
    }

    /**
     * Send deadline reminder notification
     */
    public function notifyDeadlineReminder(User $user, $enrollment): Notification
    {
        $daysLeft = now()->diffInDays($enrollment->deadline);
        
        return $this->create(
            $user,
            'deadline_reminder',
            'Course Deadline Reminder',
            "You have {$daysLeft} days left to complete {$enrollment->course->title}",
            ['enrollment_id' => $enrollment->id],
            route('enrollments.show', $enrollment)
        );
    }

    /**
     * Send new course available notification
     */
    public function notifyNewCourse(User $user, $course): Notification
    {
        return $this->create(
            $user,
            'new_course',
            'New Course Available',
            "A new course is available: {$course->title}",
            ['course_id' => $course->id],
            route('catalog.show', $course)
        );
    }

    /**
     * Send assessment graded notification
     */
    public function notifyAssessmentGraded(User $user, $attempt): Notification
    {
        $passed = $attempt->score >= $attempt->assessment->passing_score;
        
        return $this->create(
            $user,
            'assessment_graded',
            $passed ? 'Assessment Passed' : 'Assessment Graded',
            $passed 
                ? "Congratulations! You passed {$attempt->assessment->title} with a score of {$attempt->score}%"
                : "Your assessment {$attempt->assessment->title} has been graded. Score: {$attempt->score}%",
            ['attempt_id' => $attempt->id],
            route('assessments.results', $attempt)
        );
    }
}
