<?php
namespace Jaeger;

use PHPUnit\Framework\TestCase;

class JSpanContextTest extends TestCase
{
    public function test()
    {
        $context = new JSpanContext(1, 1, 1, 1);

        self::assertTrue($context->isSampled());
        self::assertEquals('1:1:1:1', $context->buildString());
        self::assertEquals([
            'traceId'  => 1,
            'spanId'   => 1,
            'parentId' => 1,
            'flags'    => 1,
        ], iterator_to_array($context->getIterator()));

        $context2 = $context->withBaggageItem('a', 1);
        self::assertNotSame($context2, $context);
        self::assertSame(1, $context2->getBaggageItem('a'));
        self::assertSame(null, $context->getBaggageItem('a'));

        $context3 = $context2->withBaggageItem('a', 1);
        self::assertSame($context2, $context3);
    }
}
