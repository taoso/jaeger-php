<?php

namespace Jaeger;

use OpenTracing\Span;
use OpenTracing\SpanContext;
use Jaeger\ThriftGen\Agent\Tags;

class JSpan implements Span
{
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

    public function log(array $fields = [], $timestamp = null)
    {
        $log['timestamp'] = $timestamp ? $timestamp : self::microtimeToInt();
        $log['fields'] = $fields;

        $this->logs[] = $log;
    }

    public function addBaggageItem($key, $value)
    {
        $this->spanContext = $this->spanContext->withBaggageItem($key, $value);
    }

    public function getBaggageItem($key)
    {
        return $this->spanContext->getBaggageItem($key);
    }

    public function buildThrift()
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
            'tags' => self::buildTags($this->tags),
            'logs' => self::buildLogs($this->logs),
        ];

        if ($context->parentId != 0) {
            $span['references'] = [
                [
                    'refType' => 1,
                    'traceIdLow' => hexdec($context->traceId),
                    'traceIdHigh' => 0, // TODO support 128bit trace id
                    'spanId' => hexdec($context->parentId),
                ],
            ];
        }

        return $span;
    }

    private static function buildTags($tags)
    {
        $resultTags = [];
        if ($tags) {
            $tagsObj = Tags::getInstance();
            $tagsObj->setTags($tags);
            $resultTags = $tagsObj->buildTags();
        }

        return $resultTags;
    }

    private static function buildLogs($logs)
    {
        $resultLogs = [];
        if ($logs) {
            $tagsObj = Tags::getInstance();
            foreach ($logs as $log) {
                $tagsObj->setTags($log['fields']);
                $fields = $tagsObj->buildTags();
                $resultLogs[] = [
                    "timestamp" => $log['timestamp'],
                    "fields" => $fields,
                ];
            }
        }

        return $resultLogs;
    }

    private static function microtimeToInt()
    {
        return intval(microtime(true) * 1000000);
    }
}
