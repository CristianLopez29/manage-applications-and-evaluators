<?php

namespace Tests\Candidates\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Candidates\Domain\Exceptions\InvalidEmailException;
use Src\Candidates\Domain\ValueObjects\Email;

class EmailValidationByValueObjectTest extends TestCase
{
    #[Test]
    public function email_value_object_rejects_invalid_format(): void
    {
        $this->expectException(InvalidEmailException::class);
        Email::fromString('not-an-email');
    }

    #[Test]
    public function email_value_object_accepts_valid_email(): void
    {
        $email = Email::fromString('valid@example.com');
        $this->assertSame('valid@example.com', $email->value());
    }
}
