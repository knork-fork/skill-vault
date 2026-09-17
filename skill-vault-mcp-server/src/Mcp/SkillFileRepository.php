<?php

declare(strict_types=1);

namespace App\Mcp;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads skills stored as `{resourcesDir}/skills/<slug>/{metadata.yaml,skill.md}`,
 * per the format documented in docs/Skills_vs_Tools.md.
 */
final class SkillFileRepository
{
    public function __construct(
        #[Autowire(param: 'app.resources_dir')]
        private readonly string $resourcesDir,
    ) {
    }

    /**
     * @return list<array{slug: string, name: string, description: string, requires: list<string>, content: string}>
     */
    public function findAll(): array
    {
        $skillsDir = $this->resourcesDir . '/skills';
        if (!is_dir($skillsDir)) {
            return [];
        }

        $skills = [];
        foreach (glob($skillsDir . '/*', \GLOB_ONLYDIR) ?: [] as $dir) {
            $skill = $this->loadDirectory($dir);
            if ($skill !== null) {
                $skills[] = $skill;
            }
        }

        usort($skills, static fn (array $a, array $b): int => $a['name'] <=> $b['name']);

        return $skills;
    }

    /**
     * @return array{slug: string, name: string, description: string, requires: list<string>, content: string}|null
     */
    public function findByName(string $name): ?array
    {
        foreach ($this->findAll() as $skill) {
            if ($skill['name'] === $name) {
                return $skill;
            }
        }

        return null;
    }

    /**
     * @return array{slug: string, name: string, description: string, requires: list<string>, content: string}|null
     */
    private function loadDirectory(string $dir): ?array
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

        $requires = [];
        $requiresList = \is_array($metadata['requires'] ?? null) ? $metadata['requires'] : [];
        foreach ($requiresList as $tool) {
            if (\is_string($tool)) {
                $requires[] = $tool;
            }
        }

        return [
            'slug' => $slug,
            'name' => \is_string($metadata['name'] ?? null) ? $metadata['name'] : $slug,
            'description' => \is_string($metadata['description'] ?? null) ? $metadata['description'] : '',
            'requires' => $requires,
            'content' => (string) file_get_contents($contentFile),
        ];
    }
}
