<?php

namespace Jaeger;

use OpenTracing\Span;
use OpenTracing\SpanContext;
use Jaeger\Thrift\Log;
use Jaeger\Thrift\Span as SpanThrift;
use Jaeger\Thrift\SpanRef;
use Jaeger\Thrift\SpanRefType;

class JSpan implements Span
{
    use ThriftTags;

    private $operationName;

    private $startTime;

    private $finishTime;

    private $spanKind;

    /**
     * @var SpanContext
     */
    private $spanContext;

    private $duration = 0;

    private $logs = [];

    private $tags = [];

    public function __construct($operationName, SpanContext $spanContext)
    {
        $this->operationName = $operationName;
        $this->startTime = self::microtimeToInt();
        $this->spanContext = $spanContext;
    }

    public function getOperationName()
    {
        return $this->operationName;
    }

    public function getContext()
    {
        return $this->spanContext;
    }

    public function finish($finishTime = null, array $logRecords = [])
    {
        $this->finishTime = $finishTime ?: self::microtimeToInt();
        $this->duration = $this->finishTime - $this->startTime;
    }

    public function overwriteOperationName($newOperationName)
    {
        $this->operationName = $newOperationName;
    }

    public function setTags(array $tags)
    {
        $this->tags = array_merge($this->tags, $tags);
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function log(array $fields = [], $timestamp = null)
    {
        $log['timestamp'] = $timestamp ? $timestamp : self::microtimeToInt();
        $log['fields'] = $fields;

        $this->logs[] = $log;
    }

    private function buildLogThrift()
    {
        $thriftLogs = [];
        foreach ($this->logs as $log) {
            $thriftLogs[] = new Log([
                'timestamp' => $log['timestamp'],
                'fields' => $this->buildTags($log['fields']),
            ]);
        }

        return $thriftLogs;
    }

    public function addBaggageItem($key, $value)
    {
        $this->spanContext = $this->spanContext->withBaggageItem($key, $value);
    }

    public function getBaggageItem($key)
    {
        return $this->spanContext->getBaggageItem($key);
    }

    public function buildThrift() : SpanThrift
    {
        $context = $this->spanContext;
        $span = [
            'traceIdLow' => hexdec($context->traceId),
            'traceIdHigh' => 0,
            'spanId' => hexdec($context->spanId),
            'parentSpanId' => hexdec($context->parentId),
            'operationName' => $this->getOperationName(),
            'flags' => intval($context->flags),
            'startTime' => $this->startTime,
            'duration' => $this->duration,
            'tags' => $this->buildTags($this->tags),
            'logs' => $this->buildLogThrift(),
        ];

        if ($context->parentId != 0) {
            $span['references'] = [
                new SpanRef([
                    'refType' => SpanRefType::CHILD_OF,
                    'traceIdLow' => hexdec($context->traceId),
                    'traceIdHigh' => 0, // TODO support 128bit trace id
                    'spanId' => hexdec($context->parentId),
                ]),
            ];
        }

        return new SpanThrift($span);
    }

    private static function microtimeToInt()
    {
        return intval(microtime(true) * 1000000);
    }
}
