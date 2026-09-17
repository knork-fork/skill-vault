<?php

declare(strict_types=1);

namespace App\Skill;

use App\Entity\SkillGroup;
use App\Entity\User;
use App\Entity\UserSkillGroupState;
use App\Entity\UserSkillState;
use App\Repository\SkillGroupRepository;
use App\Repository\UserSkillGroupStateRepository;
use App\Repository\UserSkillStateRepository;
use Doctrine\ORM\EntityManagerInterface;

final class SkillAccessService
{
    public function __construct(
        private readonly UserSkillStateRepository $skillStates,
        private readonly UserSkillGroupStateRepository $groupStates,
        private readonly SkillGroupRepository $skillGroups,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function contextForUser(User $user): SkillAccessContext
    {
        $skillStateMap = [];
        foreach ($this->skillStates->findAllByUser($user) as $state) {
            $skillStateMap[$state->getSkillSlug()] = $state->isEnabled();
        }

        $groupStateMap = [];
        foreach ($this->groupStates->findAllByUser($user) as $state) {
            $groupId = $state->getSkillGroup()->getId();
            if ($groupId !== null) {
                $groupStateMap[$groupId] = $state->isEnabled();
            }
        }

        $groupsByName = [];
        foreach ($this->skillGroups->findAll() as $group) {
            $groupsByName[$group->getName()] = $group;
        }

        return new SkillAccessContext($skillStateMap, $groupStateMap, $groupsByName);
    }

    /**
     * @param list<string> $skillSlugsInGroup
     */
    public function setSkillGroupEnabled(User $user, SkillGroup $group, bool $enabled, array $skillSlugsInGroup): void
    {
        $state = $this->groupStates->findOneByUserAndGroup($user, $group);
        if ($state === null) {
            $this->entityManager->persist(new UserSkillGroupState($user, $group, $enabled));
        } else {
            $state->setEnabled($enabled);
        }

        foreach ($skillSlugsInGroup as $slug) {
            $skillState = $this->skillStates->findOneByUserAndSlug($user, $slug);
            if ($skillState !== null) {
                $this->entityManager->remove($skillState);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @param list<string> $siblingSkillSlugsInGroup every other skill slug in $group (excluding $skillSlug itself)
     */
    public function setSkillEnabled(User $user, string $skillSlug, bool $enabled, ?SkillGroup $group, array $siblingSkillSlugsInGroup = []): void
    {
        $state = $this->skillStates->findOneByUserAndSlug($user, $skillSlug);
        if ($state === null) {
            $this->entityManager->persist(new UserSkillState($user, $skillSlug, $enabled));
        } else {
            $state->setEnabled($enabled);
        }

        if ($group !== null) {
            $groupState = $this->groupStates->findOneByUserAndGroup($user, $group);
            if ($groupState !== null) {
                // The group's saved state is about to disappear, so its other skills — which
                // until now were only enabled/disabled by inheriting it — would otherwise
                // silently flip to "no entry at all" -> disabled. Freeze their current
                // effective state into an explicit entry of their own first.
                foreach ($siblingSkillSlugsInGroup as $siblingSlug) {
                    if ($siblingSlug === $skillSlug || $this->skillStates->findOneByUserAndSlug($user, $siblingSlug) !== null) {
                        continue;
                    }

                    $this->entityManager->persist(new UserSkillState($user, $siblingSlug, $groupState->isEnabled()));
                }

                $this->entityManager->remove($groupState);
            }
        }

        $this->entityManager->flush();
    }
}
