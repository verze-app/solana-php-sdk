<?php

namespace Tighten\SolanaPhpSdk\Tests\ActualCrypto;

use PHPUnit\Framework\TestCase;
use Tighten\SolanaPhpSdk\Utils\TweetNacl;

class TweetNaclTest extends TestCase
{
    /** @test */
    public function it_can_perform_unsigned_right_shift()
    {
        $ioc = new TweetNacl();

        // Take these values, run in JavaScript using the >>> operator, and compare
        $values = [
            // Left side, right side, output
            [10, 3, 1],
            [-10, 3, 536870910],
            [33, 33, 16],
            [-10, -30, 1073741821],
            [-14, 5, 134217727],
            [99, 200, 0],
            [-519521, 123, 31],
            [12, 12, 0],
            [-9, -27, 134217727],
            [-14, 14, 262143],
        ];

        foreach ($values as $value) {
            $this->assertEquals($value[2], $ioc->unsigned_shift_right($value[0], $value[1]));
        }
    }
}
