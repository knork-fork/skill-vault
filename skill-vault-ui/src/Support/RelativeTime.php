<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;

final class RelativeTime
{
    public static function daysAgo(DateTimeImmutable $since, DateTimeImmutable $now = new DateTimeImmutable()): int
    {
        return (int) $since->diff($now)->days;
    }

    public static function describe(DateTimeImmutable $since, DateTimeImmutable $now = new DateTimeImmutable()): string
    {
        $diff = $since->diff($now);
        $days = (int) $diff->days;

        if ($days === 0) {
            $hours = $diff->h;
            if ($hours === 0) {
                return 'just now';
            }

            return $hours === 1 ? '1 hour ago' : \sprintf('%d hours ago', $hours);
        }

        if ($days < 7) {
            return $days === 1 ? '1 day ago' : \sprintf('%d days ago', $days);
        }

        if ($days < 30) {
            $weeks = intdiv($days, 7);

            return $weeks === 1 ? '1 week ago' : \sprintf('%d weeks ago', $weeks);
        }

        $months = intdiv($days, 30);

        return $months <= 1 ? '1 month ago' : \sprintf('%d months ago', $months);
    }
}
