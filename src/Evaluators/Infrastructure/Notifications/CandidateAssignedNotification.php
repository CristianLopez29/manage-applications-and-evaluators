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
                ->markdown('emails.candidate_assigned_evaluator', [
                    'candidateName' => $this->candidateName,
                    'candidateEmail' => $this->candidateEmail,
                ]);
        }

        return (new MailMessage)
            ->subject('Your candidacy has been assigned to an evaluator')
            ->markdown('emails.candidate_assigned_candidate', [
                'evaluatorName' => $this->evaluatorName,
            ]);
    }
}
