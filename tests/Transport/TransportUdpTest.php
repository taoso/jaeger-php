<?php
namespace Jaeger\Transport;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use Thrift\Transport\TMemoryBuffer;
use Jaeger\Thrift\Batch;
use Jaeger\Thrift\Agent\AgentIf;
use Jaeger\Factory;

function fwrite($fp, $buf, $len = 0)
{
}

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

    public function testNew()
    {
        $transport = new TransportUdp('127.0.0.1', 1024);

        $socket = $this->getPrivateValue($transport, 'socket');
        self::assertEquals('127.0.0.1:1024', stream_socket_get_name($socket, true));
    }

    public function testAppend()
    {
        $buf = m::mock(TMemoryBuffer::class)->makePartial();
        $buf->shouldReceive('read')->once()->with(65000)->andReturn('hello');
        $agent = m::mock(AgentIf::class);
        $agent->shouldReceive('emitBatch')
              ->once()
              ->andReturnUsing(function ($batch) {
                  self::assertEquals('demo', $batch->process->serviceName);
                  self::assertEquals(1, count($batch->spans));
                  self::assertEquals('foo', $batch->spans[0]->operationName);
              });
        $transport = new TransportUdp('127.0.0.1', 1024);
        $this->setPrivateValue($transport, 'transport', $buf);
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
