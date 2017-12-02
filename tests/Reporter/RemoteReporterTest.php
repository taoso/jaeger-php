<?php
namespace Jaeger\Reporter;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use Jaeger\Transport\Transport;
use Jaeger\Jaeger;

class RemoteReporterTest extends TestCase
{
    public function testReport()
    {
        $jaeger = m::mock(Jaeger::class);

        $tranpsort = m::mock(Transport::class);
        $tranpsort->shouldReceive('append')
                  ->once()
                  ->andReturnUsing(function ($jaeger2) use($jaeger) {
                      self::assertSame($jaeger2, $jaeger);
                  });
        $tranpsort->shouldReceive('flush')->once();

        $reporter = new RemoteReporter($tranpsort);

        $reporter->report($jaeger);
        $reporter->close();
    }
}
