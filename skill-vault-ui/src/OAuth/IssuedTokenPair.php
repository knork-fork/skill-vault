<?php

declare(strict_types=1);

namespace App\OAuth;

final readonly class IssuedTokenPair
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
    ) {
    }
}
