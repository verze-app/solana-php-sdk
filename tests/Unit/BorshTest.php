<?php

namespace Tighten\SolanaPhpSdk\Tests\Unit;

use Tighten\SolanaPhpSdk\Borsh\Borsh;
use Tighten\SolanaPhpSdk\Tests\TestCase;

class Test {
    public $x;
    public $y;
    public $z;
    public $a;
    public $b;
    public $c;
    public $q;

    public function __construct() {}
}

class BorshTest extends TestCase
{
    /** @test */
    public function it_serialize_object()
    {
        $value = new Test();
        $value->x = 255;
        $value->y = 20;
        $value->z = '123';
        $value->a = 12.987;
        $value->b = -121;
        $value->c = -20;
        $value->q = [1, 2, 3];

        $schema = [
            Test::class => [
                'kind' => 'struct',
                'fields' => [
                    ['x', 'u8'],
                    ['y', 'u64'],
                    ['z', 'string'],
                    ['a', 'f64'],
                    ['b', 'i32'],
                    ['c', 'i8'],
                    ['q', [3]],
                ],
            ],
        ];

        $buffer = Borsh::serialize($schema, $value);
        $newValue = Borsh::deserialize($schema, Test::class, $buffer);

        $this->assertInstanceOf(Test::class, $newValue);
        $this->assertEquals(255, $newValue->x);
        $this->assertEquals(20, $newValue->y);
        $this->assertEquals('123', $newValue->z);
        $this->assertEquals(12.987, $newValue->a);
        $this->assertEquals(-121, $newValue->b);
        $this->assertEquals(-20, $newValue->c);
        $this->assertEquals([1, 2, 3], $newValue->q);
    }

    /** @test */
    public function it_serialize_optional_field()
    {
        $schema = [
            Test::class => [
                'kind' => 'struct',
                'fields' => [
                    ['x', [
                        'kind' => 'option',
                        'type' => 'string',
                    ]],
                ],
            ],
        ];

        $value = new Test();
        $value->x = 'bacon';
        $buffer = Borsh::serialize($schema, $value);
        $newValue = Borsh::deserialize($schema, Test::class, $buffer);
        $this->assertEquals('bacon', $newValue->x);

        $value = new Test();
        $value->x = null;
        $buffer = Borsh::serialize($schema, $value);
        $newValue = Borsh::deserialize($schema, Test::class, $buffer);
        $this->assertNull($newValue->x);
    }

    /** @test */
    public function it_serialize_deserialize_fixed_array()
    {
        $schema = [
            Test::class => [
                'kind' => 'struct',
                'fields' => [
                    ['x', ['string', 2]],
                ],
            ],
        ];

        $value = new Test();
        $value->x = ['hello', 'world'];

        $buffer = Borsh::serialize($schema, $value);
        $newValue = Borsh::deserialize($schema, Test::class, $buffer);

        $this->assertEquals([5, 0, 0, 0, 104, 101, 108, 108, 111, 5, 0, 0, 0, 119, 111, 114, 108, 100], $buffer);
        $this->assertEquals(['hello', 'world'], $newValue->x);
    }
}
