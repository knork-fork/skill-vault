<?php

declare(strict_types=1);

namespace App\Mcp;

/**
 * Replaces `{{NAME}}` placeholders in skill content with the user's environment variable
 * values. A placeholder with no value is left as written.
 */
final class PlaceholderSubstituter
{
    private const PATTERN = '/\{\{\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}/';

    /**
     * @param array<string, string> $values placeholder name => value
     */
    public function substitute(string $content, array $values): string
    {
        if ($values === []) {
            return $content;
        }

        return (string) preg_replace_callback(
            self::PATTERN,
            static fn (array $match): string => $values[$match[1]] ?? $match[0],
            $content,
        );
    }
}
