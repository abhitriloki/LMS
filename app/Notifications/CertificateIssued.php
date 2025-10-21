<?php

namespace App\Notifications;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class CertificateIssued extends Notification implements ShouldQueue
{
    use Queueable;

    public Certificate $certificate;

    /**
     * Create a new notification instance.
     */
    public function __construct(Certificate $certificate)
    {
        $this->certificate = $certificate;
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
        $course = $this->certificate->course;
        
        $message = (new MailMessage)
            ->subject('Certificate Issued - ' . $course->title)
            ->greeting('Congratulations, ' . $notifiable->name . '!')
            ->line('You have successfully completed the course: ' . $course->title)
            ->line('Your certificate has been issued and is now available for download.')
            ->line('Certificate Number: ' . $this->certificate->certificate_number)
            ->action('View Certificate', route('certificates.show', $this->certificate->id))
            ->line('You can download your certificate at any time from your dashboard.');

        // Attach PDF if it exists
        if ($this->certificate->file_path && Storage::disk('public')->exists($this->certificate->file_path)) {
            $message->attach(Storage::disk('public')->path($this->certificate->file_path), [
                'as' => 'Certificate-' . $this->certificate->certificate_number . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'certificate_id' => $this->certificate->id,
            'certificate_number' => $this->certificate->certificate_number,
            'course_id' => $this->certificate->course_id,
            'course_title' => $this->certificate->course->title,
            'issued_at' => $this->certificate->issued_at->toDateTimeString(),
        ];
    }
}
