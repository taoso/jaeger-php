<?php
namespace Jaeger\Transport;

use PHPUnit\Framework\TestCase;

class TEmptyMemoryBufferTest extends TestCase
{
    public function test()
    {
        $buf = new TEmptyMemoryBuffer;
        $buf->write('foo');
        self::assertEquals(3, $buf->flush());

        $buf->write('foo');
        $buf->write('bar');
        self::assertEquals(6, $buf->flush());

        self::assertTrue($buf->isOpen());
        self::assertEquals('', $buf->read(1));
    }
}
