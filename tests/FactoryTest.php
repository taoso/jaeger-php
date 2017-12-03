<?php
namespace Jaeger;

use PHPUnit\Framework\TestCase;
use OpenTracing\Tracer;
use Jaeger\Sampler\ConstSampler;

class FactoryTest extends TestCase
{
    public function testInitTracerServerName()
    {
        $factory = Factory::getInstance();
        $this->expectExceptionMessage('$serviceName is required');
        $tracer = $factory->initTracer('', '', 0);
    }

    public function testInitTracerHost()
    {
        $factory = Factory::getInstance();
        $this->expectExceptionMessage('$host is required');
        $tracer = $factory->initTracer('foo', '', 0);
    }

    public function testInitTracerPort()
    {
        $factory = Factory::getInstance();
        $this->expectExceptionMessage('$port must greater than zero');
        $tracer = $factory->initTracer('foo', '127.0.0.1', 0);
    }

    public function testInitTracer()
    {
        $factory = Factory::getInstance();
        $tracer = $factory->initTracer('foo', '127.0.0.1', 1);
        self::assertInstanceOf(Tracer::class, $tracer);
    }

    public function testInitTracerSingleton()
    {
        $factory = Factory::getInstance();
        $tracer = $factory->initTracer('foo', '127.0.0.1', 1);
        $tracer2 = $factory->initTracer('foo', '127.0.0.1', 1);
        self::assertSame($tracer, $tracer2);
    }

    public function testDisabled()
    {
        $factory = Factory::getInstance();
        $factory->setDisabled(true);
        $tracer = $factory->initTracer('baz', '127.0.0.1', 1);

        $sampler = $this->getPrivateProperty($tracer, 'sampler');

        self::assertInstanceOf(ConstSampler::class, $sampler);
        self::assertFalse($sampler->isSampled());
    }

    private function getPrivateProperty($object, $name)
    {
        $r = new \ReflectionClass(get_class($object));
        $p = $r->getProperty($name);
        $p->setAccessible(true);

        return $p->getValue($object);
    }
}
