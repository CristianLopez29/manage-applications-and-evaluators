<?php

namespace Src\Evaluators\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CandidateAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $recipientType,
        private readonly string $candidateName,
        private readonly string $candidateEmail,
        private readonly string $evaluatorName
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
                ->subject('New candidate assigned to you')
                ->line('A new candidate has been assigned to you for evaluation.')
                ->line("Candidate: {$this->candidateName} ({$this->candidateEmail})")
                ->line('Please log in to the platform to review the application.');
        }

        return (new MailMessage)
            ->subject('Your candidacy has been assigned to an evaluator')
            ->line('Your candidacy has been assigned to an evaluator for review.')
            ->line("Evaluator: {$this->evaluatorName}")
            ->line('You will be notified once a decision has been made.');
    }
}

