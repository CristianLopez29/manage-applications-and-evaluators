<?php

namespace Src\Evaluators\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignmentStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $recipientType,
        private readonly string $candidateName,
        private readonly string $candidateEmail,
        private readonly string $evaluatorName,
        private readonly string $previousStatus,
        private readonly string $newStatus
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
        if ($this->recipientType === 'evaluator') {
            return (new MailMessage)
                ->subject('Candidate assignment status updated')
                ->markdown('emails.assignment_status_changed_evaluator', [
                    'candidateName' => $this->candidateName,
                    'candidateEmail' => $this->candidateEmail,
                    'previousStatus' => $this->previousStatus,
                    'newStatus' => $this->newStatus,
                ]);
        }

        return (new MailMessage)
            ->subject('Update on your candidacy status')
            ->markdown('emails.assignment_status_changed_candidate', [
                'evaluatorName' => $this->evaluatorName,
                'previousStatus' => $this->previousStatus,
                'newStatus' => $this->newStatus,
            ]);
    }
}
