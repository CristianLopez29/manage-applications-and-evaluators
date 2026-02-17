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

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/api/reports/download?file=' . urlencode($this->filePath));

        return (new MailMessage)
            ->subject('Evaluators Report Ready')
            ->markdown('emails.report_ready', [
                'downloadUrl' => $url,
            ]);
    }
}
