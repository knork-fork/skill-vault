<?php

declare(strict_types=1);

namespace App\Changelog;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ChangelogTwigExtension extends AbstractExtension
{
    public function __construct(private readonly ChangelogService $changelog)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('changelog_all', $this->changelog->entries(...)),
            new TwigFunction('changelog_unseen', $this->changelog->unseenFor(...)),
        ];
    }
}
