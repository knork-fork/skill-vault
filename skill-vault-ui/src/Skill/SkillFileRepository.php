<?php

declare(strict_types=1);

namespace App\Skill;

use App\Entity\SkillGroup;
use App\Repository\SkillGroupRepository;
use App\Support\RelativeTime;
use DateTimeImmutable;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads and writes skills stored as `{resourcesDir}/skills/<slug>/{metadata.yaml,skill.md}`,
 * per the format documented in docs/Skills_vs_Tools.md.
 */
final class SkillFileRepository
{
    public function __construct(
        #[Autowire(param: 'app.resources_dir')]
        private readonly string $resourcesDir,
        private readonly SkillGroupRepository $skillGroups,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findAll(): array
    {
        $skillsDir = $this->resourcesDir . '/skills';
        if (!is_dir($skillsDir)) {
            return [];
        }

        $validGroupNames = array_map(
            static fn (SkillGroup $group): string => $group->getName(),
            $this->skillGroups->findAll(),
        );

        $skills = [];
        foreach (glob($skillsDir . '/*', \GLOB_ONLYDIR) ?: [] as $dir) {
            $skill = $this->loadDirectory($dir, $validGroupNames);
            if ($skill !== null) {
                $skills[] = $skill;
            }
        }

        usort($skills, static fn (array $a, array $b): int => $a['daysAgo'] <=> $b['daysAgo']);

        return $skills;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(string $slug): ?array
    {
        foreach ($this->findAll() as $skill) {
            if ($skill['slug'] === $slug) {
                return $skill;
            }
        }

        return null;
    }

    /**
     * @param array{name: string, description: string, group: string|null, icon: string, color: string, content: string} $data
     */
    public function save(string $slug, array $data): void
    {
        $dir = $this->resourcesDir . '/skills/' . $slug;
        if (!is_dir($dir) && !mkdir($dir, 0o775, true) && !is_dir($dir)) {
            throw new RuntimeException(\sprintf('Could not create skill directory "%s".', $dir));
        }

        $metadata = [
            'name' => $data['name'],
            'description' => $data['description'],
            'icon' => $data['icon'],
            'color' => $data['color'],
            'requires' => [],
        ];
        if ($data['group'] !== null) {
            $metadata['group'] = $data['group'];
        }

        file_put_contents($dir . '/metadata.yaml', Yaml::dump($metadata));
        file_put_contents($dir . '/skill.md', $data['content']);
    }

    public function delete(string $slug): void
    {
        $dir = $this->resourcesDir . '/skills/' . $slug;
        if (!is_dir($dir)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            if (!$file instanceof SplFileInfo) {
                continue;
            }
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($dir);
    }

    /**
     * @param list<string> $validGroupNames
     *
     * @return array<string, mixed>|null
     */
    private function loadDirectory(string $dir, array $validGroupNames): ?array
    {
        $metadataFile = $dir . '/metadata.yaml';
        $contentFile = $dir . '/skill.md';

        if (!is_file($metadataFile) || !is_file($contentFile)) {
            return null;
        }

        $metadata = Yaml::parseFile($metadataFile);
        if (!\is_array($metadata)) {
            return null;
        }

        $slug = basename($dir);

        $group = \is_string($metadata['group'] ?? null) ? $metadata['group'] : null;
        if ($group !== null && !\in_array($group, $validGroupNames, true)) {
            $group = null;
        }

        $requires = [];
        $requiresList = \is_array($metadata['requires'] ?? null) ? $metadata['requires'] : [];
        foreach ($requiresList as $tool) {
            if (\is_string($tool)) {
                $requires[] = $tool;
            }
        }

        $mtime = filemtime($contentFile);
        if ($mtime === false) {
            $mtime = filemtime($metadataFile) ?: time();
        }
        $modifiedAt = new DateTimeImmutable()->setTimestamp($mtime);

        return [
            'slug' => $slug,
            'name' => \is_string($metadata['name'] ?? null) ? $metadata['name'] : $slug,
            'description' => \is_string($metadata['description'] ?? null) ? $metadata['description'] : '',
            'group' => $group,
            'icon' => \is_string($metadata['icon'] ?? null) ? $metadata['icon'] : 'folder',
            'color' => \is_string($metadata['color'] ?? null) ? $metadata['color'] : 'blue',
            'requires' => $requires,
            'daysAgo' => RelativeTime::daysAgo($modifiedAt),
            'updated' => RelativeTime::describe($modifiedAt),
            'content' => (string) file_get_contents($contentFile),
        ];
    }
}
