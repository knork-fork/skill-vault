<?php

declare(strict_types=1);

namespace App\Skill;

use App\Support\RelativeTime;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;

/**
 * Reads per-skill edit history from the internal git repo at `{resourcesDir}/skills/.git`,
 * where each commit corresponds to one saved edit of a skill's `skill.md`. Recording those
 * commits is out of scope here - this only reads what's already there.
 */
final class SkillHistoryRepository
{
    public function __construct(
        #[Autowire(param: 'app.resources_dir')]
        private readonly string $resourcesDir,
    ) {
    }

    /**
     * @return array<string, mixed>|null null when there's no git repo, or the skill file has no commits yet
     */
    public function findHistory(string $slug): ?array
    {
        $repoDir = $this->resourcesDir . '/skills';
        if (!is_dir($repoDir . '/.git')) {
            return null;
        }

        // The resources bind mount can be owned by a different uid than the git process
        // (see phpdocker/php-fpm/Entrypoint.sh) - scope the "safe.directory" exception to
        // this one invocation instead of trusting it globally.
        $process = new Process([
            'git',
            '-C', $repoDir,
            '-c', 'safe.directory=' . $repoDir,
            'log',
            '--follow',
            '--format=%H%x1f%an%x1f%aI',
            '--',
            $slug . '/skill.md',
        ]);
        $process->run();

        if (!$process->isSuccessful()) {
            return null;
        }

        $output = trim($process->getOutput());
        if ($output === '') {
            return null;
        }

        $versions = [];
        foreach (explode("\n", $output) as $commit) {
            $fields = explode("\x1f", $commit);
            if (\count($fields) !== 3) {
                continue;
            }
            [$hash, $author, $isoDate] = $fields;

            $date = new DateTimeImmutable($isoDate);
            $versions[] = [
                'commit' => $hash,
                // A commit hash, not a sequential number: skills can be edited from
                // multiple places (and history could be rewritten), so there's no safe
                // "next version" counter to hand out.
                'version' => substr($hash, 0, 7),
                'author' => $author,
                'authorInitials' => self::initials($author),
                'date' => $date->format('M j, Y \a\t H:i'),
                'relative' => RelativeTime::describe($date),
            ];
        }

        if ($versions === []) {
            return null;
        }

        foreach ($versions as $index => &$version) {
            $version['current'] = $index === 0;
        }
        unset($version);

        return [
            'totalVersions' => \count($versions),
            'currentVersion' => $versions[0]['version'],
            'lastEditedBy' => $versions[0]['author'],
            'versions' => $versions,
        ];
    }

    private static function initials(string $author): string
    {
        $words = array_values(array_filter(preg_split('/\s+/', trim($author)) ?: []));

        if ($words === []) {
            return '?';
        }

        if (\count($words) === 1) {
            return mb_strtoupper(mb_substr($words[0], 0, 1));
        }

        return mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[\count($words) - 1], 0, 1));
    }
}
