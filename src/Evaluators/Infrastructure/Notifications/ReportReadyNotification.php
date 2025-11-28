<?php

namespace Src\Evaluators\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ReportReadyNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $filePath
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Generate correct URL pointing to /storage/reports/...
        // Storage::disk('public')->url('reports/file.xlsx') returns '/storage/reports/file.xlsx'
        $url = Storage::disk('public')->url($this->filePath);

        // Ensure it's a full URL (http://localhost/storage/...)
        if (!str_starts_with($url, 'http')) {
            $url = url($url);
        }

        return (new MailMessage)
            ->subject('Evaluators Report Ready')
            ->line('The consolidated evaluators report has been generated.')
            ->line('You can download it using the button below.')
            ->action('Download Report', $url)
            ->line('Thank you for using our application!');
    }
}
