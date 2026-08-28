<?php

declare(strict_types=1);

namespace Tests\Shared\Unit;

use DOMDocument;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * phpunit.xml declares its test suites one by one, so a new top-level directory
 * under tests/ is simply never executed — locally or in CI, which stays green
 * while the tests inside it never run. This pins that gap shut.
 */
class TestSuiteCoverageTest extends TestCase
{
    #[Test]
    public function should_cover_every_test_directory_with_a_declared_suite(): void
    {
        $declared = $this->declaredSuiteDirectories();

        $this->assertNotEmpty($declared, 'phpunit.xml declares no test suite directories.');

        foreach ($this->existingTestDirectories() as $directory) {
            $this->assertContains(
                $directory,
                $declared,
                sprintf(
                    'tests/%s belongs to no <testsuite> in phpunit.xml, so none of its tests run. '
                    .'Add a <testsuite name="%s"><directory>tests/%s</directory></testsuite>.',
                    $directory,
                    $directory,
                    $directory
                )
            );
        }
    }

    /**
     * Top-level directory names covered by a <testsuite> entry.
     *
     * @return list<string>
     */
    private function declaredSuiteDirectories(): array
    {
        $document = new DOMDocument;
        $this->assertTrue(
            $document->load(base_path('phpunit.xml')),
            'phpunit.xml could not be parsed.'
        );

        $declared = [];

        foreach ($document->getElementsByTagName('directory') as $node) {
            // <source> also uses <directory>; only the testsuite ones decide what runs.
            if ($node->parentNode?->nodeName !== 'testsuite') {
                continue;
            }

            $segments = explode('/', str_replace('\\', '/', trim($node->textContent)));

            if ($segments[0] !== 'tests' || ! isset($segments[1])) {
                continue;
            }

            $declared[] = $segments[1];
        }

        return array_values(array_unique($declared));
    }

    /**
     * @return list<string>
     */
    private function existingTestDirectories(): array
    {
        $entries = scandir(base_path('tests')) ?: [];

        return array_values(array_filter(
            $entries,
            fn (string $entry): bool => $entry !== '.'
                && $entry !== '..'
                && is_dir(base_path('tests/'.$entry))
        ));
    }
}
