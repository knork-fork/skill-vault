<?php

declare(strict_types=1);

namespace App\Mcp;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads tools stored as `{resourcesDir}/tools/<slug>/tool.yaml`, per the format
 * documented in docs/Skills_vs_Tools.md.
 */
final class ToolFileRepository
{
    public function __construct(
        #[Autowire(param: 'app.resources_dir')]
        private readonly string $resourcesDir,
    ) {
    }

    /**
     * @return list<array{slug: string, name: string, description: string, inputSchema: array<string, mixed>}>
     */
    public function findAll(): array
    {
        $toolsDir = $this->resourcesDir . '/tools';
        if (!is_dir($toolsDir)) {
            return [];
        }

        $tools = [];
        foreach (glob($toolsDir . '/*', \GLOB_ONLYDIR) ?: [] as $dir) {
            $tool = $this->loadDirectory($dir);
            if ($tool !== null) {
                $tools[] = $tool;
            }
        }

        usort($tools, static fn (array $a, array $b): int => $a['name'] <=> $b['name']);

        return $tools;
    }

    /**
     * @return array{slug: string, name: string, description: string, inputSchema: array<string, mixed>}|null
     */
    public function findBySlug(string $slug): ?array
    {
        foreach ($this->findAll() as $tool) {
            if ($tool['slug'] === $slug) {
                return $tool;
            }
        }

        return null;
    }

    /**
     * @return array{slug: string, name: string, description: string, inputSchema: array<string, mixed>}|null
     */
    private function loadDirectory(string $dir): ?array
    {
        $toolFile = $dir . '/tool.yaml';
        if (!is_file($toolFile)) {
            return null;
        }

        $metadata = Yaml::parseFile($toolFile);
        if (!\is_array($metadata)) {
            return null;
        }

        $slug = basename($dir);

        $inputSchema = \is_array($metadata['inputSchema'] ?? null)
            ? $metadata['inputSchema']
            : ['type' => 'object', 'properties' => (object) []];

        return [
            'slug' => $slug,
            'name' => \is_string($metadata['name'] ?? null) ? $metadata['name'] : $slug,
            'description' => \is_string($metadata['description'] ?? null) ? $metadata['description'] : '',
            'inputSchema' => $inputSchema,
        ];
    }
}
