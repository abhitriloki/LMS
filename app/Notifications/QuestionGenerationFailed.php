<?php

namespace App\Notifications;

use App\Models\AIQuestionJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuestionGenerationFailed extends Notification implements ShouldQueue
{
    use Queueable;

    protected AIQuestionJob $job;
    protected ?string $errorMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct(AIQuestionJob $job, ?string $errorMessage = null)
    {
        $this->job = $job;
        $this->errorMessage = $errorMessage;
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
        $mail = (new MailMessage)
            ->subject('AI Question Generation Failed')
            ->error()
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Unfortunately, your AI question generation request has failed.')
            ->line('**Lesson:** ' . $this->job->lesson->title);

        if ($this->errorMessage) {
            $mail->line('**Error:** ' . $this->errorMessage);
        }

        return $mail
            ->action('Try Again', route('admin.questions.generator.create', ['lesson_id' => $this->job->lesson_id]))
            ->line('You can try generating questions again or contact support if the problem persists.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'question_generation_failed',
            'job_id' => $this->job->id,
            'lesson_id' => $this->job->lesson_id,
            'lesson_title' => $this->job->lesson->title,
            'error_message' => $this->errorMessage,
            'message' => "Question generation failed for {$this->job->lesson->title}",
            'action_url' => route('admin.questions.generator.create', ['lesson_id' => $this->job->lesson_id]),
        ];
    }
}
