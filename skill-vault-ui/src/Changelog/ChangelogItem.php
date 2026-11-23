<?php

declare(strict_types=1);

namespace App\Changelog;

final readonly class ChangelogItem
{
    public function __construct(
        public string $title,
        public string $description = '',
        public ?string $documentationUrl = null,
    ) {
    }

    public static function fromJson(mixed $raw): ?self
    {
        if (\is_string($raw)) {
            $raw = ['title' => $raw];
        }
        if (!\is_array($raw) || !\is_string($raw['title'] ?? null) || trim($raw['title']) === '') {
            return null;
        }

        $url = $raw['documentationUrl'] ?? null;
        // Same-site paths only.
        $url = \is_string($url) && preg_match('#^/(?!/)#', $url) === 1 ? $url : null;

        return new self(
            trim($raw['title']),
            \is_string($raw['description'] ?? null) ? trim($raw['description']) : '',
            $url,
        );
    }
}
