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
        $deadlineFormatted = $this->deadline->format('Y-m-d H:i:s');

        if ($this->recipientType === 'evaluator') {
            return (new MailMessage)
                ->subject('Overdue candidate assignment reminder')
                ->line('You have a candidate assignment that is overdue.')
                ->line("Candidate: {$this->candidateName} ({$this->candidateEmail})")
                ->line("Deadline: {$deadlineFormatted}")
                ->line('Please review this candidate as soon as possible.');
        }

        return (new MailMessage)
            ->subject('Your candidacy review is delayed')
            ->line('The review of your candidacy is taking longer than expected.')
            ->line("Evaluator: {$this->evaluatorName}")
            ->line("Original deadline: {$deadlineFormatted}")
            ->line('We apologize for the delay and will notify you once a decision is made.');
    }
}

