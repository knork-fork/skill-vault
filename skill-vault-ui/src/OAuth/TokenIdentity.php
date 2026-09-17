<?php

declare(strict_types=1);

namespace App\OAuth;

final readonly class TokenIdentity
{
    public function __construct(
        public int $userId,
        public string $username,
    ) {
    }
}
