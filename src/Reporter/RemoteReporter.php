<?php

namespace Jaeger\Reporter;

use Jaeger\Jaeger;
use Jaeger\JSpan;
use Jaeger\Transport\Transport;

class RemoteReporter implements Reporter
{
    /**
     * @var Transport
     */
    private $transport;

    public function __construct(Transport $transport)
    {
        $this->transport= $transport;
    }

    public function report(Jaeger $jaeger)
    {
        $this->transport->append($jaeger);
    }

    public function close()
    {
        $this->transport->flush();
    }
}
