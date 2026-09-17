<?php

declare(strict_types=1);

namespace App\Skill;

use App\Entity\SkillGroup;

/**
 * Resolves per-user skill/skill-group enablement from a snapshot of that user's saved
 * opt-in states:
 *
 * - A skill with its own saved state uses that state, regardless of its group.
 * - A skill with no saved state of its own falls back to its group's saved state, if any.
 * - A skill with neither is disabled.
 * - A group with its own saved state uses that state.
 * - A group with no saved state is derived from its skills: all enabled -> enabled, none
 *   enabled (including no skills) -> disabled, otherwise -> mixed.
 */
final class SkillAccessContext
{
    /**
     * @param array<string, bool>       $skillStates  skill slug => enabled
     * @param array<int, bool>          $groupStates  skill group id => enabled
     * @param array<string, SkillGroup> $groupsByName skill group name => entity
     */
    public function __construct(
        private readonly array $skillStates,
        private readonly array $groupStates,
        private readonly array $groupsByName,
    ) {
    }

    public function isSkillEnabled(string $skillSlug, ?string $groupName): bool
    {
        if (\array_key_exists($skillSlug, $this->skillStates)) {
            return $this->skillStates[$skillSlug];
        }

        $groupId = null;
        if ($groupName !== null && \array_key_exists($groupName, $this->groupsByName)) {
            $groupId = $this->groupsByName[$groupName]->getId();
        }

        if ($groupId !== null && \array_key_exists($groupId, $this->groupStates)) {
            return $this->groupStates[$groupId];
        }

        return false;
    }

    /**
     * @param list<string> $skillSlugsInGroup
     *
     * @return 'enabled'|'disabled'|'mixed'
     */
    public function groupState(SkillGroup $group, array $skillSlugsInGroup): string
    {
        $groupId = $group->getId();
        if ($groupId !== null && \array_key_exists($groupId, $this->groupStates)) {
            return $this->groupStates[$groupId] ? 'enabled' : 'disabled';
        }

        if ($skillSlugsInGroup === []) {
            return 'disabled';
        }

        $enabledCount = 0;
        foreach ($skillSlugsInGroup as $slug) {
            if ($this->skillStates[$slug] ?? false) {
                ++$enabledCount;
            }
        }

        if ($enabledCount === 0) {
            return 'disabled';
        }

        return $enabledCount === \count($skillSlugsInGroup) ? 'enabled' : 'mixed';
    }
}
