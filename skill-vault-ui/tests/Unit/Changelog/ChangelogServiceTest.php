<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog;

use App\Changelog\ChangelogService;
use App\Entity\User;
use App\Tests\Common\UnitTestCase;
use DateTimeImmutable;

/**
 * @internal
 */
final class ChangelogServiceTest extends UnitTestCase
{
    private string $path = '';

    protected function setUp(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'changelog');
        self::assertIsString($path);
        $this->path = $path;
        file_put_contents($this->path, json_encode([
            '2026-01-01' => 'Old feature',
            '2026-03-01' => [
                ['title' => 'New A', 'description' => 'Desc', 'documentationUrl' => '/docs/x'],
                ['title' => 'New B', 'documentationUrl' => 'https://evil.example'],
            ],
            'garbage' => 'ignored',
        ]));
    }

    protected function tearDown(): void
    {
        unlink($this->path);
    }

    public function testEntriesAreSortedNewestFirstAndInvalidDatesDropped(): void
    {
        $entries = new ChangelogService($this->path)->entries();

        self::assertCount(2, $entries);
        self::assertSame('New A', $entries[0]['items'][0]->title);
        self::assertSame('/docs/x', $entries[0]['items'][0]->documentationUrl);
        self::assertNull($entries[0]['items'][1]->documentationUrl);
        self::assertSame('Old feature', $entries[1]['items'][0]->title);
    }

    public function testUserWithNoLastSeenSeesEverything(): void
    {
        self::assertCount(2, new ChangelogService($this->path)->unseenFor(new User()));
    }

    public function testOnlyEntriesAfterLastSeenAreUnseen(): void
    {
        $user = new User()->setLastSeenChangelog(new DateTimeImmutable('2026-01-01'));

        self::assertCount(1, new ChangelogService($this->path)->unseenFor($user));
    }

    public function testMarkSeenMakesNothingUnseen(): void
    {
        $service = new ChangelogService($this->path);
        $user = new User();
        $service->markSeen($user);

        self::assertSame([], $service->unseenFor($user));
    }

    public function testMissingFileYieldsNoEntries(): void
    {
        self::assertSame([], new ChangelogService('/nonexistent.json')->entries());
    }
}
