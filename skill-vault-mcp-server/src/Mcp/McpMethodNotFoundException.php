<?php

declare(strict_types=1);

namespace App\Mcp;

use RuntimeException;

final class McpMethodNotFoundException extends RuntimeException
{
    public function __construct(string $method)
    {
        parent::__construct(\sprintf('Method not found: "%s".', $method));
    }
}
