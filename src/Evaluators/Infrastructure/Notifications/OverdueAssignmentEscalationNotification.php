<?php

namespace Src\Evaluators\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverdueAssignmentEscalationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $candidateName,
        private readonly string $candidateEmail,
        private readonly string $evaluatorName,
        private readonly string $evaluatorEmail,
        private readonly \DateTimeInterface $deadline,
        private readonly int $daysOverdue
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
        return (new MailMessage)
            ->subject('Escalation: Candidate assignment overdue')
            ->markdown('emails.overdue_escalation_admin', [
                'candidateName' => $this->candidateName,
                'candidateEmail' => $this->candidateEmail,
                'evaluatorName' => $this->evaluatorName,
                'evaluatorEmail' => $this->evaluatorEmail,
                'deadline' => $this->deadline,
                'daysOverdue' => $this->daysOverdue,
            ]);
    }
}
