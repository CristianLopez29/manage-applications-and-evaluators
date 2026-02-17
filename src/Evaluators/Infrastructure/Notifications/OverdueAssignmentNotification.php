<?php

namespace Src\Evaluators\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverdueAssignmentNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $recipientType,
        private readonly string $candidateName,
        private readonly string $candidateEmail,
        private readonly string $evaluatorName,
        private readonly \DateTimeInterface $deadline
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
                ->subject('Overdue candidate assignment reminder')
                ->markdown('emails.overdue_assignment_evaluator', [
                    'candidateName' => $this->candidateName,
                    'candidateEmail' => $this->candidateEmail,
                    'deadline' => $this->deadline,
                ]);
        }

        return (new MailMessage)
            ->subject('Your candidacy review is delayed')
            ->markdown('emails.overdue_assignment_candidate', [
                'evaluatorName' => $this->evaluatorName,
                'deadline' => $this->deadline,
            ]);
    }
}
