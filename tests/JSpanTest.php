<?php
namespace Jaeger;

use PHPUnit\Framework\TestCase;

function microtime()
{
    static $i = 0;
    return $i++;
}

class JSpanTest extends TestCase
{
    public function test()
    {
        $context = new JSpanContext(1, 1, 1, 1);

        $span = new JSpan('foo', $context);

        self::assertEquals('foo', $span->getOperationName());
        self::assertSame($context, $span->getContext());

        $span->overwriteOperationName('bar');
        self::assertEquals('bar', $span->getOperationName());

        $span->addBaggageItem('a', 1);
        self::assertNotSame($context, $span->getContext());
        self::assertEquals(1, $span->getBaggageItem('a'));

        $span->setTags(['a' => 1]);
        $span->log(['b' => 2]);

        $span->finish();

        self::assertEquals([
            'traceIdLow' => 1,
            'traceIdHigh' => 0,
            'spanId' => 1,
            'parentSpanId' => 1,
            'operationName' => 'bar',
            'startTime' => 0,
            'duration' => 2000000,
            'flags' => 1,
            'references' => [
                [
                    'refType' => 1,
                    'traceIdLow' => 1,
                    'traceIdHigh' => 0,
                    'spanId' => 1,
                ]
            ],
            'tags' => [
                [
                    'key' => 'a',
                    'vType' =>  'DOUBLE',
                    'vDouble' => 1,
                ],
            ],
            'logs' => [
                [
                    'timestamp' => 1000000,
                    'fields' => [
                        [
                            'key' => 'b',
                            'vType' =>  'DOUBLE',
                            'vDouble' => 2,
                        ]
                    ]
                ],
            ],
        ], $span->buildThrift());
    }
}
