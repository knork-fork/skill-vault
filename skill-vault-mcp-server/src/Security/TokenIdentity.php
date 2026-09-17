<?php

declare(strict_types=1);

namespace App\Security;

final readonly class TokenIdentity
{
    public function __construct(
        public int $userId,
        public string $username,
    ) {
    }
}
