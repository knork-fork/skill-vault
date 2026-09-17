<?php

declare(strict_types=1);

namespace App\Support;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Reads the hero quotes from config/quotes.json.
 */
final class QuoteRepository
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return array{quote: string, author: string}
     */
    public function random(): array
    {
        $quotes = $this->findAll();
        if ($quotes === []) {
            return ['quote' => 'Small skills. Bigger possibilities.', 'author' => 'Skill Vault'];
        }

        return $quotes[array_rand($quotes)];
    }

    /**
     * @return list<array{quote: string, author: string}>
     */
    private function findAll(): array
    {
        $file = $this->projectDir.'/config/quotes.json';
        if (!is_file($file)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($file), true);
        if (!\is_array($data)) {
            return [];
        }

        $quotes = [];
        foreach ($data as $entry) {
            if (\is_array($entry) && \is_string($entry['quote'] ?? null) && \is_string($entry['author'] ?? null)) {
                $quotes[] = ['quote' => $entry['quote'], 'author' => $entry['author']];
            }
        }

        return $quotes;
    }
}
