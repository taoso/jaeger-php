<?php
namespace Jaeger\Transport;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use Jaeger\Thrift\Batch;
use Jaeger\Thrift\Agent\AgentIf;
use Jaeger\Factory;

class TransportUdpTest extends TestCase
{
    public function testGetBufferSize()
    {
        $thrift = m::mock('Thrift');
        $thrift->shouldReceive('write')
               ->once()
               ->andReturnUsing(function ($protocol) {
                   $protocol->writeString('hello');
               });

        $transport = new TransportUdp('127.0.0.1', 1024);
        $size = $transport->getBufferSize($thrift);
        self::assertEquals(6, $size); // size + string
    }

    public function testFlush()
    {
        $batch = m::mock(Batch::class);
        $agent = m::mock(AgentIf::class);
        $agent->shouldReceive('emitBatch')
              ->once()
              ->andReturnUsing(function ($_batch) use($batch) {
                  self::assertSame($batch, $_batch);
              });

        $transport = new TransportUdp('127.0.0.1', 1024);
        $this->setPrivateValue($transport, 'batch', $batch);
        $this->setPrivateValue($transport, 'agent', $agent);
        $transport->flush();
    }

    public function testNew()
    {
        $transport = new TransportUdp('127.0.0.1', 1024);
        $agent = $this->getPrivateValue($transport, 'agent');
        $protocol = $this->getPrivateValue($agent, 'output_');
        /* @var \Thrift\Transport\TSocket $transport */
        $transport = $protocol->getTransport();

        self::assertEquals('udp://127.0.0.1', $transport->getHost());
        self::assertEquals(1024, $transport->getPort());
    }

    public function testAppend()
    {
        $agent = m::mock(AgentIf::class);
        $agent->shouldReceive('emitBatch')
              ->once()
              ->andReturnUsing(function ($batch)
              {
                  self::assertEquals('demo', $batch->process->serviceName);
                  self::assertEquals(1, count($batch->spans));
                  self::assertEquals('foo', $batch->spans[0]->operationName);
              });
        $transport = new TransportUdp('127.0.0.1', 1024);
        $this->setPrivateValue($transport, 'agent', $agent);

        $factory = new Factory;
        $factory->setTransport($transport);

        $jaeger = $factory->initTracer('demo', '127.0.0.1', 1024);

        $span = $jaeger->startSpan('foo');
        $span->setTags(['a' => 1]);
        $span->finish();
        $jaeger->flush();
    }

    private function setPrivateValue($object, $name, $value)
    {
        $r = new \ReflectionClass(get_class($object));
        $p = $r->getProperty($name);
        $p->setAccessible(true);
        $p->setValue($object, $value);
    }

    private function getPrivateValue($object, $name)
    {
        $r = new \ReflectionClass(get_class($object));
        $p = $r->getProperty($name);
        $p->setAccessible(true);

        return $p->getValue($object);
    }
}
