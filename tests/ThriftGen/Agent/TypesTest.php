<?php
namespace Jaeger\ThriftGen\Agent;

use PHPUnit\Framework\TestCase;

class TypesTest extends TestCase
{
    public function testTagTypeToString()
    {
        self::assertEquals(
            [
                'STRING',
                'DOUBLE',
                'BOOL',
                'LONG',
                'BINARY',
                'UNSET',
            ],
            [
                Types::tagTypeToString(0),
                Types::tagTypeToString(1),
                Types::tagTypeToString(2),
                Types::tagTypeToString(3),
                Types::tagTypeToString(4),
                Types::tagTypeToString(5),
            ]
        );
    }

    public function testStringToTagType()
    {
        self::assertEquals(
            [
                0,
                1,
                2,
                3,
                4,
            ],
            [
                Types::stringToTagType('STRING'),
                Types::stringToTagType('DOUBLE'),
                Types::stringToTagType('BOOL'),
                Types::stringToTagType('LONG'),
                Types::stringToTagType('BINARY'),
            ]
        );
    }
}
