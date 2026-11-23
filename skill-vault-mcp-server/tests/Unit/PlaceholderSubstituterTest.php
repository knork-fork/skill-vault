<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Mcp\PlaceholderSubstituter;
use App\Tests\Common\UnitTestCase;

/**
 * @internal
 */
final class PlaceholderSubstituterTest extends UnitTestCase
{
    public function testReplacesSetPlaceholdersAndKeepsUnsetOnes(): void
    {
        $result = new PlaceholderSubstituter()->substitute(
            'Board {{BOARD_ID}} / {{ BOARD_ID }} / {{MISSING}} / {not}',
            ['BOARD_ID' => 'abc123'],
        );

        self::assertSame('Board abc123 / abc123 / {{MISSING}} / {not}', $result);
    }

    public function testValuesContainingPlaceholdersAreNotRescanned(): void
    {
        $result = new PlaceholderSubstituter()->substitute('{{A}}', ['A' => '{{B}}', 'B' => 'x']);

        self::assertSame('{{B}}', $result);
    }

    public function testNoValuesReturnsContentUnchanged(): void
    {
        self::assertSame('{{A}}', new PlaceholderSubstituter()->substitute('{{A}}', []));
    }
}
