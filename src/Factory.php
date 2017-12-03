<?php

namespace Jaeger;

use OpenTracing\Tracer;
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
     * @var Tracer[]
     */
    private $tracers = [];

    private static $instance;

    private $disabled = false;

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
    public function initTracer(string $serverName, string $host, int $port) : Tracer
    {
        if (!$serverName) {
            throw new \InvalidArgumentException("\$serverName is required");
        }

        if (!$host) {
            throw new \InvalidArgumentException("\$host is required");
        }

        if ($port <= 0) {
            throw new \InvalidArgumentException("\$port must greater than zero");
        }

        if (isset($this->tracers[$serverName])) {
            return $this->tracers[$serverName];
        }

        if (!$this->transport) {
            $this->transport = new TransportUdp($host, $port);
        }

        if (!$this->reporter) {
            $this->reporter = new RemoteReporter($this->transport);
        }

        if ($this->disabled) {
            $this->sampler = new ConstSampler(false);
        } elseif (!$this->sampler) {
            $this->sampler = new ConstSampler(true);
        }

        $tracer = new Jaeger($serverName, $this->reporter, $this->sampler);

        $this->tracers[$serverName] = $tracer;

        return $tracer;
    }

    public function setDisabled($disabled) : self
    {
        $this->disabled = $disabled;

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

    public function setReporter(Reporter $reporter)
    {
        $this->reporter = $reporter;

        return $this;
    }
}
