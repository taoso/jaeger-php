<?php
namespace Jaeger;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use Jaeger\Sampler\Sampler;
use Jaeger\Reporter\Reporter;
use OpenTracing\Tracer;
use OpenTracing\Formats;
use Jaeger\Thrift\TagType;

class JaegerTest extends TestCase
{
    /**
     * @return Jaeger
     */
    private function getTracer()
    {
        $reporter = m::mock(Reporter::class);
        $sampler = m::mock(Sampler::class);
        $sampler->shouldReceive('isSampled')
                ->once()
                ->andReturnTrue();

        $sampler->shouldReceive('getTags')
                ->once()
                ->andReturn([
                    'a' => 1,
                ]);

        $factory = new Factory;
        $factory->setSampler($sampler);
        $factory->setReporter($reporter);

        return $factory->initTracer('foo', '127.0.0.1', 1024);
    }

    public function testNew()
    {
        $tracer = $this->getTracer();
        self::assertInstanceOf(Tracer::class, $tracer);
    }

    public function testInject()
    {
        $context = new JSpanContext(1, 1, 1, 1);
        $tracer = $this->getTracer();
        $string = '';
        $tracer->inject($context, Formats\BINARY, $string);
        self::assertEquals('1:1:1:1', $string);
    }

    public function testInjectUnsupportFormat()
    {
        $context = new JSpanContext(1, 1, 1, 1);
        $tracer = $this->getTracer();
        $string = '';
        $this->expectExceptionMessage('not support format text_map');
        $tracer->inject($context, Formats\TEXT_MAP, $string);
    }

    public function testExtract()
    {
        $tracer = $this->getTracer();
        $context = $tracer->extract(Formats\BINARY, '1:1:1:1');

        $data = iterator_to_array($context->getIterator());
        self::assertEquals([
            'traceId'  => 1,
            'spanId'   => 1,
            'parentId' => 1,
            'flags'    => 1,
        ], $data);
    }

    public function testExtractUnsupportedFormat()
    {
        $tracer = $this->getTracer();
        $this->expectExceptionMessage('not support format text_map');
        $context = $tracer->extract(Formats\TEXT_MAP, '1:1:1:1');
    }

    public function testExtractEmpty()
    {
        $tracer = $this->getTracer();
        $context = $tracer->extract(Formats\BINARY, '');

        $data = iterator_to_array($context->getIterator());
        self::assertEquals([
            'traceId'  => 0,
            'spanId'   => 0,
            'parentId' => 0,
            'flags'    => 0,
        ], $data);
    }

    public function testStartSpan()
    {
        $tracer = $this->getTracer();

        $span = $tracer->startSpan('foo');
        self::assertNotEmpty($tracer->getSpans());
    }

    public function testStartSpanWithParent()
    {
        $tracer = $this->getTracer();

        $span = $tracer->startSpan('foo');
        $context = $span->getContext();

        $span2 = $tracer->startSpan('bar', ['child_of' => $span->getContext()]);
        $context2 = $span2->getContext();

        self::assertEquals($context->traceId, $context2->traceId);
        self::assertEquals($context->spanId, $context2->parentId);
    }

    public function testFlush()
    {
        static $tracer;

        $reporter = m::mock(Reporter::class);
        $reporter->shouldReceive('report')
                ->once()
                ->andReturnUsing(function ($jaeger) use(&$tracer) {
                    self::assertSame($tracer, $jaeger);
                });
        $reporter->shouldReceive('close')
                ->once()
                ->andReturnTrue();

        $sampler = m::mock(Sampler::class);
        $sampler->shouldReceive('isSampled')
                ->once()
                ->andReturnTrue();

        $sampler->shouldReceive('getTags')
                ->once()
                ->andReturn([ 'a' => 1 ]);

        $factory = new Factory;
        $factory->setSampler($sampler);
        $factory->setReporter($reporter);

        $tracer = $factory->initTracer('foo', '127.0.0.1', 1024);

        $span = $tracer->startSpan('foo');

        $tracer->flush();
        self::assertEmpty($tracer->getSpans());
    }

    public function testBuildProcessThrift()
    {
        $tracer = $this->getTracer();

        $span = $tracer->startSpan('foo', ['tags' => ['c' => 3]]);
        $span->finish();
        self::assertEquals(['c' => 3], $span->getTags());

        $process = $tracer->buildProcessThrift();

        self::assertEquals('foo', $process->serviceName);
        self::assertEquals(1, count($process->tags));

        $tag = $process->tags[0];
        self::assertEquals('a', $tag->key);
        self::assertEquals(TagType::LONG, $tag->vType);
        self::assertEquals(1, $tag->vLong);
    }
}
