<?php

namespace App\Notifications;

use App\Models\AIQuestionJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuestionGenerationCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    protected AIQuestionJob $job;
    protected int $questionsCount;

    /**
     * Create a new notification instance.
     */
    public function __construct(AIQuestionJob $job, int $questionsCount)
    {
        $this->job = $job;
        $this->questionsCount = $questionsCount;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('AI Question Generation Completed')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your AI question generation request has been completed successfully.')
            ->line("**{$this->questionsCount} questions** have been generated and are ready for review.")
            ->line('**Lesson:** ' . $this->job->lesson->title)
            ->action('Review Questions', route('admin.questions.generator.review', $this->job))
            ->line('Please review the generated questions and approve or modify them before adding to an assessment.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'question_generation_completed',
            'job_id' => $this->job->id,
            'lesson_id' => $this->job->lesson_id,
            'lesson_title' => $this->job->lesson->title,
            'questions_count' => $this->questionsCount,
            'message' => "{$this->questionsCount} questions generated successfully for {$this->job->lesson->title}",
            'action_url' => route('admin.questions.generator.review', $this->job),
        ];
    }
}
