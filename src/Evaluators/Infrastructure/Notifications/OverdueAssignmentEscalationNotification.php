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
        $deadlineFormatted = $this->deadline->format('Y-m-d H:i:s');

        return (new MailMessage)
            ->subject('Escalation: Candidate assignment overdue')
            ->line('A candidate assignment has exceeded the overdue threshold.')
            ->line("Candidate: {$this->candidateName} ({$this->candidateEmail})")
            ->line("Evaluator: {$this->evaluatorName} ({$this->evaluatorEmail})")
            ->line("Original deadline: {$deadlineFormatted}")
            ->line("Days overdue: {$this->daysOverdue}")
            ->line('Please intervene to resolve this overdue assignment.');
    }
}

