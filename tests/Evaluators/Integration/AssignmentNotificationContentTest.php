<?php

declare(strict_types=1);

namespace Tests\Evaluators\Integration;

use Illuminate\Notifications\AnonymousNotifiable;
use PHPUnit\Framework\Attributes\Test;
use Src\Evaluators\Infrastructure\Notifications\OverdueAssignmentEscalationNotification;
use Src\Evaluators\Infrastructure\Notifications\OverdueAssignmentNotification;
use Src\Evaluators\Infrastructure\Notifications\ReportReadyNotification;
use Tests\TestCase;

/**
 * Existing suites assert that a notification was *sent*; none built its mail body. The
 * recipientType branch in particular decides whether the evaluator or the candidate wording
 * goes out, and swapping the two would have gone unnoticed.
 */
class AssignmentNotificationContentTest extends TestCase
{
    private \DateTimeImmutable $deadline;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deadline = new \DateTimeImmutable('2026-01-15 10:00:00');
    }

    #[Test]
    public function should_address_the_evaluator_with_the_reminder_wording(): void
    {
        $notification = new OverdueAssignmentNotification(
            'evaluator',
            'Ada Lovelace',
            'ada@example.com',
            'Grace Hopper',
            $this->deadline
        );

        $mail = $notification->toMail(new AnonymousNotifiable());

        $this->assertSame(['mail'], $notification->via(new AnonymousNotifiable()));
        $this->assertSame('Overdue candidate assignment reminder', $mail->subject);
        $this->assertSame('emails.overdue_assignment_evaluator', $mail->markdown);
        $this->assertSame('Ada Lovelace', $mail->viewData['candidateName']);
        $this->assertSame('ada@example.com', $mail->viewData['candidateEmail']);
        $this->assertSame($this->deadline, $mail->viewData['deadline']);
    }

    /**
     * The candidate must never be told which evaluator's queue they are stuck in beyond the
     * name, and must not receive the evaluator-facing subject line.
     */
    #[Test]
    public function should_address_the_candidate_with_the_delay_wording(): void
    {
        $notification = new OverdueAssignmentNotification(
            'candidate',
            'Ada Lovelace',
            'ada@example.com',
            'Grace Hopper',
            $this->deadline
        );

        $mail = $notification->toMail(new AnonymousNotifiable());

        $this->assertSame('Your candidacy review is delayed', $mail->subject);
        $this->assertSame('emails.overdue_assignment_candidate', $mail->markdown);
        $this->assertSame('Grace Hopper', $mail->viewData['evaluatorName']);
        $this->assertArrayNotHasKey('candidateEmail', $mail->viewData);
    }

    #[Test]
    public function should_build_the_escalation_mail_for_the_admin(): void
    {
        $notification = new OverdueAssignmentEscalationNotification(
            'Ada Lovelace',
            'ada@example.com',
            'Grace Hopper',
            'grace@example.com',
            $this->deadline,
            7
        );

        $mail = $notification->toMail(new AnonymousNotifiable());

        $this->assertSame(['mail'], $notification->via(new AnonymousNotifiable()));
        $this->assertSame('Escalation: Candidate assignment overdue', $mail->subject);
        $this->assertSame('emails.overdue_escalation_admin', $mail->markdown);
        $this->assertSame('grace@example.com', $mail->viewData['evaluatorEmail']);
        $this->assertSame(7, $mail->viewData['daysOverdue']);
    }

    /**
     * The download link is built from the stored path, so a path with spaces or slashes has
     * to survive url-encoding intact or the recipient gets a broken link.
     */
    #[Test]
    public function should_build_an_encoded_download_url_for_the_ready_report(): void
    {
        $notification = new ReportReadyNotification('reports/evaluators report.xlsx');

        $mail = $notification->toMail(new AnonymousNotifiable());

        $this->assertSame(['mail'], $notification->via(new AnonymousNotifiable()));
        $this->assertSame('Evaluators Report Ready', $mail->subject);
        $this->assertSame('emails.report_ready', $mail->markdown);

        $downloadUrl = $mail->viewData['downloadUrl'];
        $this->assertIsString($downloadUrl);
        $this->assertStringContainsString('/api/reports/download?file=', $downloadUrl);
        $this->assertStringContainsString(urlencode('reports/evaluators report.xlsx'), $downloadUrl);
    }
}
