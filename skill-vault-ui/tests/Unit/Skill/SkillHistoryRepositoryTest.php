<?php

declare(strict_types=1);

namespace App\Tests\Unit\Skill;

use App\Skill\SkillHistoryRepository;
use App\Tests\Common\UnitTestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
final class SkillHistoryRepositoryTest extends UnitTestCase
{
    private string $resourcesDir = '';

    protected function setUp(): void
    {
        $this->resourcesDir = sys_get_temp_dir() . '/skill-history-test-' . uniqid();
        mkdir($this->resourcesDir . '/skills/demo_skill', 0o775, true);

        $this->git(['init']);
        $this->git(['config', 'user.name', 'Test Author']);
        $this->git(['config', 'user.email', 'test@example.com']);

        // First commit touches both files.
        file_put_contents($this->resourcesDir . '/skills/demo_skill/metadata.yaml', "name: Demo\n");
        file_put_contents($this->resourcesDir . '/skills/demo_skill/skill.md', "# Demo v1\n");
        $this->commit('Initial version');

        // Second commit touches only skill.md.
        file_put_contents($this->resourcesDir . '/skills/demo_skill/skill.md', "# Demo v2\n");
        $this->commit('Update skill.md only');
    }

    protected function tearDown(): void
    {
        $process = new Process(['rm', '-rf', $this->resourcesDir]);
        $process->run();
    }

    public function testFindDiffForCommitOnlyReturnsTouchedFiles(): void
    {
        $repository = new SkillHistoryRepository($this->resourcesDir);
        $commit = $this->lastCommitHash();

        $diff = $repository->findDiffForCommit('demo_skill', $commit);

        self::assertNull($diff['metadataYaml']);
        self::assertNotNull($diff['skillMd']);
        self::assertStringContainsString('-# Demo v1', $diff['skillMd']);
        self::assertStringContainsString('+# Demo v2', $diff['skillMd']);
    }

    public function testFindFileContentsAtCommitReturnsBothFilesEvenWhenOnlyOneChanged(): void
    {
        $repository = new SkillHistoryRepository($this->resourcesDir);
        $commit = $this->lastCommitHash();

        $contents = $repository->findFileContentsAtCommit('demo_skill', $commit);

        self::assertSame("name: Demo\n", $contents['metadataYaml']);
        self::assertSame("# Demo v2\n", $contents['skillMd']);
    }

    public function testFindFileContentsAtCommitReadsEarlierVersion(): void
    {
        $repository = new SkillHistoryRepository($this->resourcesDir);
        $commits = $this->allCommitHashes();
        $firstCommit = $commits[\count($commits) - 1];

        $contents = $repository->findFileContentsAtCommit('demo_skill', $firstCommit);

        self::assertSame("# Demo v1\n", $contents['skillMd']);
    }

    public function testFindFileContentsAtHeadMatchesCurrentState(): void
    {
        $repository = new SkillHistoryRepository($this->resourcesDir);

        $contents = $repository->findFileContentsAtCommit('demo_skill', 'HEAD');

        self::assertSame("# Demo v2\n", $contents['skillMd']);
    }

    /** @param list<string> $args */
    private function git(array $args): void
    {
        $process = new Process(['git', '-C', $this->resourcesDir . '/skills', ...$args]);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new RuntimeException('git command failed: ' . $process->getErrorOutput());
        }
    }

    private function commit(string $message): void
    {
        $this->git(['add', '-A']);
        $this->git(['commit', '-m', $message]);
    }

    private function lastCommitHash(): string
    {
        return $this->allCommitHashes()[0];
    }

    /** @return list<string> */
    private function allCommitHashes(): array
    {
        $process = new Process(['git', '-C', $this->resourcesDir . '/skills', 'log', '--format=%H']);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new RuntimeException('git log failed: ' . $process->getErrorOutput());
        }

        return explode("\n", trim($process->getOutput()));
    }
}
