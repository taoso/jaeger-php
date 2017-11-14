<?php

namespace Jaeger;

use OpenTracing\Tracer;
use OpenTracing\NoopTracer;
use Jaeger\Reporter\Reporter;
use Jaeger\Reporter\RemoteReporter;
use Jaeger\Transport\Transport;
use Jaeger\Transport\TransportUdp;
use Jaeger\Sampler\Sampler;
use Jaeger\Sampler\ConstSampler;

class Factory
{
    /**
     * @var Transport
     */
    private $transport;

    /**
     * @var Reporter
     */
    private $reporter;

    /**
     * @var Sampler
     */
    private $sampler;

    /**
     * @var Jaeger[]
     */
    private $trace = [];

    private static $instance;

    private static $disabled = false;

    public static function getInstance() : self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * init jaeger and set GlobalTracer, return can use flush  buffers
     *
     * @throws \InvalidArgumentException
     */
    public function initTracer(string $serverName,
        string $host = '127.0.0.1', int $port = 6831) : Tracer
    {
        if (self::$disabled) {
            return NoopTracer::create();
        }

        if (!$serverName) {
            throw new \InvalidArgumentException("serverName required");
        }

        if (!empty($this->trace[$serverName])) {
            return $this->trace[$serverName];
        }

        if (!$this->transport) {
            $this->transport = new TransportUdp($host, $port);
        }

        if (!$this->reporter) {
            $this->reporter = new RemoteReporter($this->transport);
        }

        if (!$this->sampler) {
            $this->sampler = new ConstSampler(true);
        }

        $trace = new Jaeger($serverName, $this->reporter, $this->sampler);

        $this->trace[$serverName] = $trace;

        return $trace;
    }

    public function setDisabled($disabled) : self
    {
        self::$disabled = $disabled;

        return $this;
    }

    public function setTransport(Transport\Transport $transport) : self
    {
        $this->transport = $transport;

        return $this;
    }

    public function setSampler(Sampler $sampler) : self
    {
        $this->sampler = $sampler;

        return $this;
    }
}
