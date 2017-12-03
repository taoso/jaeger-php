<?php

namespace Jaeger\Transport;

use Jaeger\Jaeger;
use Jaeger\Thrift\Batch;
use Jaeger\Thrift\Agent\AgentIf;
use Jaeger\Thrift\Agent\AgentClient;
use Thrift\Transport\TMemoryBuffer;
use Thrift\Protocol\TCompactProtocol;
use Thrift\Protocol\TProtocol;

class TransportUdp implements Transport
{
    const EMITBATCHOVERHEAD = 30;
    const PACKET_MAX_LENGTH = 65000;

    // sizeof(Span) * numSpans + processByteSize + emitBatchOverhead <= maxPacketSize
    private $maxSpanBytes;

    private $host;

    private $port;

    private $bufferSize = 0;

    private $socket;

    /**
     * @var TMemoryBuffer
     */
    private $transport;

    /**
     * @var TProtocol
     */
    private $protocol;

    /**
     * @var AgentIf
     */
    private $agent;

    /**
     * @var Batch
     */
    private $batch;

    public function __construct(string $host, int $port, int $maxPacketSize = 0)
    {
        if (!inet_pton($host)) {
            throw new \InvalidArgumentException('$host is invalid');
        }

        if ($port <= 0) {
            throw new \InvalidArgumentException('$port is invalid');
        }

        $this->host = $host;
        $this->port = $port;

        $this->socket = fsockopen("udp://$host", $port);

        if ($maxPacketSize == 0) {
            $maxPacketSize = self::PACKET_MAX_LENGTH;
        }

        $this->maxSpanBytes = $maxPacketSize - self::EMITBATCHOVERHEAD;
        if ($this->maxSpanBytes <= 0) {
            throw new \InvalidArgumentException('$maxPacketSize must be greater than '.self::EMITBATCHOVERHEAD);
        }

        $this->transport = new TMemoryBuffer;
        $this->agent = new AgentClient(null, new TCompactProtocol($this->transport));

        $this->protocol = new TCompactProtocol(new TEmptyMemoryBuffer());
    }

    public function append(Jaeger $jaeger)
    {
        $batch = $jaeger->buildThrift();

        $this->bufferSize += $this->getBufferSize($batch->process);

        $spans = [];
        foreach ($batch->spans as $span) {
            $spanSize = $this->getBufferSize($span);

            if ($spanSize > $this->maxSpanBytes) {
                continue;
            }

            $this->bufferSize += $spanSize;
            if ($this->bufferSize > $this->maxSpanBytes) {
                $this->batch = new Batch(['process' => $batch->process, 'spans' => $spans]);
                $this->flush();
                $spans = [];
            }

            $spans[] = $span;
        }

        if ($spans) {
            $this->batch = new Batch(['process' => $batch->process, 'spans' => $spans]);
        }
    }

    public function flush()
    {
        if (!$this->batch) {
            return;
        }

        $this->agent->emitBatch($this->batch);
        $this->batch = null;
        $this->bufferSize = 0;

        $buf = $this->transport->read(self::PACKET_MAX_LENGTH);
        fwrite($this->socket, $buf);
    }

    public function getBufferSize($thrift)
    {
        $thrift->write($this->protocol);

        return $this->protocol->getTransport()->flush();
    }
}
