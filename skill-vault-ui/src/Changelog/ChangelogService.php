<?php

declare(strict_types=1);

namespace App\Changelog;

use App\Entity\User;
use DateTimeImmutable;
use JsonException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Reads the repo-root changelog.json (date => list of {title, description, icon?, documentationUrl?}; plain strings are accepted as a title) and works out what a user hasn't seen yet.
 */
final class ChangelogService
{
    public function __construct(
        #[Autowire(param: 'app.changelog_path')]
        private readonly string $path,
    ) {
    }

    /**
     * All entries, newest first.
     *
     * @return list<array{date: DateTimeImmutable, items: list<ChangelogItem>}>
     */
    public function entries(): array
    {
        if (!is_file($this->path)) {
            return [];
        }

        $raw = file_get_contents($this->path);
        if ($raw === false) {
            return [];
        }

        try {
            $data = json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }
        if (!\is_array($data)) {
            return [];
        }

        $entries = [];
        foreach ($data as $date => $text) {
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $date);
            if ($parsed === false) {
                continue;
            }
            $items = [];
            foreach (\is_array($text) && array_is_list($text) ? $text : [$text] as $raw) {
                $item = ChangelogItem::fromJson($raw);
                if ($item !== null) {
                    $items[] = $item;
                }
            }
            if ($items === []) {
                continue;
            }
            $entries[] = ['date' => $parsed, 'items' => $items];
        }

        usort($entries, static fn (array $a, array $b): int => $b['date'] <=> $a['date']);

        return $entries;
    }

    public function latestDate(): ?DateTimeImmutable
    {
        return $this->entries()[0]['date'] ?? null;
    }

    /**
     * Entries newer than what the user last acknowledged (everything, if they never have).
     *
     * @return list<array{date: DateTimeImmutable, items: list<ChangelogItem>}>
     */
    public function unseenFor(User $user): array
    {
        $lastSeen = $user->getLastSeenChangelog();
        if ($lastSeen === null) {
            return $this->entries();
        }

        return array_values(array_filter(
            $this->entries(),
            static fn (array $entry): bool => $entry['date'] > $lastSeen,
        ));
    }

    public function markSeen(User $user): void
    {
        $latest = $this->latestDate();
        if ($latest !== null) {
            $user->setLastSeenChangelog($latest);
        }
    }
}
