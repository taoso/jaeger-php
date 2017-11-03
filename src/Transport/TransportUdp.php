<?php

namespace Jaeger\Transport;

use Jaeger\Helper;
use Jaeger\Jaeger;
use Jaeger\ThriftGen\Agent\AgentClient;
use Jaeger\ThriftGen\Agent\Process;
use Jaeger\ThriftGen\Agent\Span;
use Jaeger\ThriftGen\Agent\TStruct;
use Thrift\Transport\TMemoryBuffer;
use Thrift\Protocol\TCompactProtocol;

class TransportUdp implements Transport
{
    const EMITBATCHOVERHEAD = 30;

    /**
     * @var TMemoryBuffer
     */
    private $tranpsort;

    private $sock;

    // sizeof(Span) * numSpans + processByteSize + emitBatchOverhead <= maxPacketSize
    public static $maxSpanBytes = 0;

    public static $batchs = [];

    private $host = '127.0.0.1';

    private $port = 6831;

    private $thriftProtocol;

    private $bufferSize = 0;

    public function __construct(string $host = '127.0.0.1', int $port = 6831, int $maxPacketSize = 0)
    {
        $this->host = $host;
        $this->port = $port;

        if ($maxPacketSize == 0) {
            $maxPacketSize = Helper::UDP_PACKET_MAX_LENGTH;
        }

        self::$maxSpanBytes = $maxPacketSize - self::EMITBATCHOVERHEAD;

        $this->tranpsort = new TMemoryBuffer();
        $this->thriftProtocol = new TCompactProtocol($this->tranpsort);
    }

    /**
     * 收集将要发送的追踪信息
     * @param Jaeger $jaeger
     * @return bool
     */
    public function append(Jaeger $jaeger)
    {
        $processThrift = $jaeger->buildProcessThrift();
        $process = new Process($processThrift);
        $procesSize = $this->getAndCalcSizeOfSerializedThrift($process, $processThrift);
        $this->bufferSize += $procesSize;

        $thriftSpans = [];
        foreach ($jaeger->getSpans() as $span) {
            $spanThrift = $span->buildThrift();

            $agentSpan = Span::getInstance();
            $agentSpan->setThriftSpan($spanThrift);
            $spanSize = $this->getAndCalcSizeOfSerializedThrift($agentSpan, $spanThrift);

            if ($spanSize > self::$maxSpanBytes) {
                //throw new Exception("Span is too large");
                continue;
            }

            $this->bufferSize += $spanSize;
            if ($this->bufferSize > self::$maxSpanBytes) {
                $thriftSpans[] = $spanThrift;
                self::$batchs[] = ['thriftProcess' => $processThrift, 'thriftSpans' => $thriftSpans];

                $this->flush();
            } else {
                $thriftSpans[] = $spanThrift;
            }
        }

        self::$batchs[] = ['thriftProcess' => $processThrift, 'thriftSpans' => $thriftSpans];

        return true;
    }

    public function resetBuffer()
    {
        $this->bufferSize = 0;
        self::$batchs = [];
    }

    /**
     * 获取和计算序列化后的thrift字符长度
     * @param TStruct $ts
     * @param $serializedThrift
     * @return mixed
     */
    private function getAndCalcSizeOfSerializedThrift(TStruct $ts, &$serializedThrift)
    {
        $ts->write($this->thriftProtocol);
        $len = $this->tranpsort->available();
        //获取后buf清空
        $serializedThrift['wrote'] = $this->tranpsort->read(Helper::UDP_PACKET_MAX_LENGTH);

        return $len;
    }

    /**
     * @return int
     */
    public function flush()
    {
        $spanNum = 0;
        foreach (self::$batchs as $batch) {
            $spanNum += count($batch['thriftSpans']);
            $this->emitBatch($batch);
        }

        $this->resetBuffer();
        return $spanNum;
    }

    private function emitBatch($batch)
    {
        $buildThrift = (new AgentClient())->buildThrift($batch);

        $len = $buildThrift['len'] ?? 0;

        if (!$len) {
            return false;
        }

        $enitThrift = $buildThrift['thriftStr'];

        if (!$this->sock) {
            $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            socket_connect($sock, $this->host, $this->port);
            $this->sock = $sock;
        }

        return socket_send($this->sock, $enitThrift, $len, 0);
    }
}
