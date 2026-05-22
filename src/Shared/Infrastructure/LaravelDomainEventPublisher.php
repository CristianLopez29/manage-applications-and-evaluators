<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure;

use Src\Shared\Domain\DomainEvent;
use Src\Shared\Domain\DomainEventPublisher;

class LaravelDomainEventPublisher implements DomainEventPublisher
{
    public function publish(DomainEvent $event): void
    {
        event($event);
    }
}
