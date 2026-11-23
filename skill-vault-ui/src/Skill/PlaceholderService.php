<?php

declare(strict_types=1);

namespace App\Skill;

use App\Entity\User;
use App\Entity\UserEnvVar;
use App\Repository\UserEnvVarRepository;

/**
 * Finds `{{PLACEHOLDER}}` tokens in skill content and checks them against a user's
 * environment variables (see UserEnvVar).
 */
final class PlaceholderService
{
    private const PATTERN = '/\{\{\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}/';

    public function __construct(
        private readonly UserEnvVarRepository $envVars,
        private readonly SkillFileRepository $skills,
        private readonly SkillAccessService $skillAccess,
    ) {
    }

    /**
     * @return list<string> unique placeholder names, in order of first appearance
     */
    public function extract(string $content): array
    {
        preg_match_all(self::PATTERN, $content, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * @return array<string, string> placeholder name => value
     */
    public function valuesForUser(User $user): array
    {
        $values = [];
        foreach ($this->envVars->findAllByUser($user) as $envVar) {
            $values[$envVar->getPlaceholder()] = $envVar->getValue();
        }

        return $values;
    }

    /**
     * @param array<string, string> $values
     *
     * @return list<string>
     */
    public function missingIn(string $content, array $values): array
    {
        return array_values(array_filter(
            $this->extract($content),
            static fn (string $name): bool => !\array_key_exists($name, $values),
        ));
    }

    /**
     * Skill slug => unset placeholder names, for enabled skills that have at least one.
     *
     * @return array<string, list<string>>
     */
    public function missingForEnabledSkills(User $user): array
    {
        $values = $this->valuesForUser($user);
        $context = $this->skillAccess->contextForUser($user);

        $missing = [];
        foreach ($this->skills->findAll() as $skill) {
            $slug = \is_string($skill['slug']) ? $skill['slug'] : '';
            $group = \is_string($skill['group'] ?? null) ? $skill['group'] : null;
            if (!$context->isSkillEnabled($slug, $group)) {
                continue;
            }

            $names = $this->missingIn(\is_string($skill['content'] ?? null) ? $skill['content'] : '', $values);
            if ($names !== []) {
                $missing[$slug] = $names;
            }
        }

        return $missing;
    }

    /**
     * Returns HTML-escaped content with each placeholder wrapped in a span: `ph-set` (title =
     * value) when the user has a value for it, otherwise a `ph-missing` link to the
     * environment variables page that prefills the placeholder name.
     *
     * @param array<string, string> $values
     */
    public function highlight(string $content, array $values, string $missingUrl): string
    {
        $parts = preg_split(self::PATTERN, $content, -1, \PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return htmlspecialchars($content, \ENT_QUOTES);
        }

        $html = '';
        foreach ($parts as $i => $part) {
            // With DELIM_CAPTURE, odd indexes are the captured placeholder names.
            if ($i % 2 === 0) {
                $html .= htmlspecialchars($part, \ENT_QUOTES);
                continue;
            }

            $token = htmlspecialchars('{{' . $part . '}}', \ENT_QUOTES);
            if (\array_key_exists($part, $values)) {
                $html .= \sprintf('<span class="ph-set" title="%s">%s</span>', htmlspecialchars($values[$part], \ENT_QUOTES), $token);
            } else {
                $href = $missingUrl . (str_contains($missingUrl, '?') ? '&' : '?') . 'placeholder=' . rawurlencode($part);
                $html .= \sprintf('<a class="ph-missing" href="%s" title="not set">%s</a>', htmlspecialchars($href, \ENT_QUOTES), $token);
            }
        }

        return $html;
    }
}
