<?php
namespace Jaeger;

use PHPUnit\Framework\TestCase;
use Jaeger\Thrift\SpanRefType;
use Jaeger\Thrift\TagType;

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

        $thrift = (array) $span->buildThrift();

        $references = $thrift['references']; unset($thrift['references']);

        $tags = $thrift['tags']; unset($thrift['tags']);

        $logs = $thrift['logs']; unset($thrift['logs']);

        self::assertEquals([
            'traceIdLow' => 1,
            'traceIdHigh' => 0,
            'spanId' => 1,
            'parentSpanId' => 1,
            'operationName' => 'bar',
            'startTime' => 0,
            'duration' => 2000000,
            'flags' => 1,
        ], $thrift);

        self::assertEquals([
            'refType' => SpanRefType::CHILD_OF,
            'traceIdLow' => 1,
            'traceIdHigh' => 0,
            'spanId' => 1,
        ], (array)$references[0]);

        $tag = $tags[0];
        self::assertEquals('a', $tag->key);
        self::assertEquals(TagType::LONG, $tag->vType);
        self::assertEquals(1, $tag->vLong);

        $log = $logs[0];
        self::assertEquals(1000000, $log->timestamp);
        $tag = $log->fields[0];
        self::assertEquals('b', $tag->key);
        self::assertEquals(TagType::LONG, $tag->vType);
        self::assertEquals(2, $tag->vLong);
    }
}
