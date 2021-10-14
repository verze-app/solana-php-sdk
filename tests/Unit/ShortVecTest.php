<?php

namespace Tighten\SolanaPhpSdk\Tests\Unit;

use Tighten\SolanaPhpSdk\Tests\TestCase;
use Tighten\SolanaPhpSdk\Util\ShortVec;

class ShortVecTest extends TestCase
{
    /** @test */
    public function it_decodeLength()
    {
        $this->checkDecodedArray([], 0, 0);
        $this->checkDecodedArray([5], 1, 5);
        $this->checkDecodedArray([0x7F], 1, 0x7F);
        $this->checkDecodedArray([0x80, 0x01], 2, 0x80);
        $this->checkDecodedArray([0xFF, 0x01], 2, 0xFF);
        $this->checkDecodedArray([0x80, 0x02], 2, 0x100);
        $this->checkDecodedArray([0x80, 0x02], 2, 0x100);
        $this->checkDecodedArray([0xFF, 0xFF, 0x01], 3, 0x7FFF);
        $this->checkDecodedArray([0x80, 0x80, 0x80, 0x01], 4, 0x200000);
    }

    /** @test */
    public function it_encodeLength()
    {
        $array = [];
        $prevLength = 0;

        $expected = [0];
        $this->checkEncodedArray($array, 0, $prevLength, $expected);
        $prevLength += sizeof($expected);

        $expected = [5];
        $this->checkEncodedArray($array, 5, $prevLength, $expected);
        $prevLength += sizeof($expected);

        $expected = [0x7F];
        $this->checkEncodedArray($array, 0x7f, $prevLength, $expected);
        $prevLength += sizeof($expected);

        $expected = [0x80, 0x01];
        $this->checkEncodedArray($array, 0x80, $prevLength, $expected);
        $prevLength += sizeof($expected);

        $expected = [0xff, 0x01];
        $this->checkEncodedArray($array, 0xff, $prevLength, $expected);
        $prevLength += sizeof($expected);

        $expected = [0x80, 0x02];
        $this->checkEncodedArray($array, 0x100, $prevLength, $expected);
        $prevLength += sizeof($expected);

        $expected = [0xff, 0xff, 0x01];
        $this->checkEncodedArray($array, 0x7fff, $prevLength, $expected);
        $prevLength += sizeof($expected);

        $expected = [0x80, 0x80, 0x80, 0x01];
        $this->checkEncodedArray(
            $array,
            0x200000,
            $prevLength,
            $expected
        );
        $prevLength += sizeof($expected);

        $this->assertEquals(16, $prevLength);
        $this->assertEquals($prevLength, sizeof($array));
    }

    /**
     * @param array $array
     * @param int $expectedValue
     */
    protected function checkDecodedArray(array $array, int $expectedLength, int $expectedValue)
    {
        list($value, $length) = ShortVec::decodeLength($array);
        $this->assertEquals($expectedValue, $value);
        $this->assertEquals($expectedLength, $length);
    }

    /**
     * @param array $array
     * @param int $length
     * @param int $prevLength
     * @param array $expectedArray
     */
    protected function checkEncodedArray(array &$array, int $length, int $prevLength, array $expectedArray)
    {
        $this->assertEquals(sizeof($array), $prevLength);
        $actual = ShortVec::encodeLength($length);
        array_push($array, ...$actual);
        $this->assertEquals(sizeof($array), $prevLength + sizeof($expectedArray));
        $this->assertEquals($expectedArray, array_slice($array, -sizeof($expectedArray)));
    }
}
