<?php
namespace Jaeger;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use Jaeger\Sampler\Sampler;
use Jaeger\Reporter\Reporter;
use OpenTracing\Tracer;
use OpenTracing\Formats;

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
}
