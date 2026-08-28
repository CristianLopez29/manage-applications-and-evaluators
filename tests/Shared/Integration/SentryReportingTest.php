<?php

declare(strict_types=1);

namespace Tests\Shared\Integration;

use PHPUnit\Framework\Attributes\Test;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Tests\TestCase;

/**
 * sentry-laravel strips its own error listeners and only reports what the
 * application hands it via Integration::handles(). Before that call existed in
 * bootstrap/app.php the SDK booted, read the DSN and captured nothing, so this
 * asserts the wiring rather than the vendor package.
 */
class SentryReportingTest extends TestCase
{
    /** @var list<\Throwable> */
    private array $captured = [];

    private function spyOnSentry(): void
    {
        $this->captured = [];

        /** @var HubInterface&\Mockery\MockInterface $hub */
        $hub = \Mockery::mock(HubInterface::class);
        $hub->shouldReceive('captureException')
            ->andReturnUsing(function (\Throwable $e): null {
                $this->captured[] = $e;

                return null;
            });
        $hub->shouldIgnoreMissing();

        SentrySdk::setCurrentHub($hub);
    }

    #[Test]
    public function should_report_an_unhandled_exception_to_sentry(): void
    {
        $this->spyOnSentry();

        report(new \RuntimeException('boom'));

        $this->assertCount(1, $this->captured);
        $this->assertSame('boom', $this->captured[0]->getMessage());
    }

    /**
     * Domain exceptions are mapped to 4xx business responses, so alerting on
     * them would bury genuine 5xx incidents.
     */
    #[Test]
    public function should_not_report_domain_exceptions(): void
    {
        $this->spyOnSentry();

        report(new \DomainException('candidate already assigned'));

        $this->assertSame([], $this->captured);
    }
}
