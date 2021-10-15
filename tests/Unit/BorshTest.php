<?php

namespace Tighten\SolanaPhpSdk\Tests\Unit;

use Tighten\SolanaPhpSdk\Borsh\Borsh;
use Tighten\SolanaPhpSdk\Tests\TestCase;

class Test {
    public $x;
    public $y;
    public $z;
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
        $value->q = [1, 2, 3];

        $schema = [
            Test::class => [
                'kind' => 'struct',
                'fields' => [
                    ['x', 'u8'],
                    ['y', 'u64'],
                    ['z', 'string'],
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
        $this->assertEquals([1, 2, 3], $newValue->q);
    }

    /** @test */
    public function it_serialize_nested()
    {
        $child = new Test();
        $child->q = [1, 2, 4];

        $parent = new Test();
        $parent->x = 255;
        $parent->y = $child;

        $schema = [
            Test::class => [
                'kind' => 'struct',
                'fields' => [
                    ['x', 'u8'],
                    ['y', Test::class],
                    ['q', [3]],
                ],
            ],
        ];

        $buffer = Borsh::serialize($schema, $parent);
        $newValue = Borsh::deserialize($schema, Test::class, $buffer);

        $this->assertInstanceOf(Test::class, $newValue);
        $this->assertEquals(255, $newValue->x);
        $this->assertEquals(20, $newValue->y);
        $this->assertEquals('123', $newValue->z);
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
}
