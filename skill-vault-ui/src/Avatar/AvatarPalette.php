<?php

declare(strict_types=1);

namespace App\Avatar;

/**
 * Canonical set of icon/color choices for the avatar picker used when creating
 * skill groups and skills. Kept in sync with the icon branches in
 * templates/components/_avatar.html.twig.
 */
final class AvatarPalette
{
    /**
     * @var list<array{key: string, label: string}>
     */
    public const ICONS = [
        ['key' => 'trello', 'label' => 'Trello'],
        ['key' => 'terminal', 'label' => 'Terminal'],
        ['key' => 'code', 'label' => 'Code'],
        ['key' => 'cube', 'label' => 'Package'],
        ['key' => 'user', 'label' => 'Person'],
        ['key' => 'users', 'label' => 'People'],
        ['key' => 'link', 'label' => 'Link'],
        ['key' => 'chart', 'label' => 'Chart'],
        ['key' => 'heart', 'label' => 'Heart'],
        ['key' => 'star', 'label' => 'Star'],
        ['key' => 'book', 'label' => 'Book'],
        ['key' => 'briefcase', 'label' => 'Briefcase'],
        ['key' => 'shield', 'label' => 'Shield'],
        ['key' => 'bolt', 'label' => 'Bolt'],
        ['key' => 'folder', 'label' => 'Folder'],
        ['key' => 'doc', 'label' => 'Document'],
        ['key' => 'bug', 'label' => 'Bug'],
        ['key' => 'message', 'label' => 'Message'],
        ['key' => 'calendar', 'label' => 'Calendar'],
        ['key' => 'mail', 'label' => 'Mail'],
    ];

    /**
     * @var list<array{key: string, hex: string}>
     */
    public const COLORS = [
        ['key' => 'blue', 'hex' => '#2563eb'],
        ['key' => 'green', 'hex' => '#16a34a'],
        ['key' => 'purple', 'hex' => '#7c3aed'],
        ['key' => 'orange', 'hex' => '#f97316'],
        ['key' => 'red', 'hex' => '#e11d48'],
        ['key' => 'teal', 'hex' => '#0d9488'],
        ['key' => 'indigo', 'hex' => '#4f46e5'],
        ['key' => 'yellow', 'hex' => '#ca8a04'],
        ['key' => 'slate', 'hex' => '#475569'],
        ['key' => 'cyan', 'hex' => '#0891b2'],
    ];
}
