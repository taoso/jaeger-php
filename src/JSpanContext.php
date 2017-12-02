<?php

namespace Jaeger;

use OpenTracing\SpanContext;

class JSpanContext implements SpanContext
{
    // traceID represents globally unique ID of the trace.
    // Usually generated as a random number.
    public $traceId;

    // spanID represents span ID that must be unique within its trace,
    // but does not have to be globally unique.
    public $spanId;

    // parentID refers to the ID of the parent span.
    // Should be 0 if the current span is a root span.
    public $parentId;

    // flags is a bitmap containing such bits as 'sampled' and 'debug'.
    public $flags;

    public function __construct($traceId, $spanId, $parentId, $flags)
    {
        $this->traceId = $traceId;
        $this->spanId = $spanId;
        $this->parentId = $parentId;
        $this->flags = $flags;
    }

    public function getBaggageItem($key)
    {
        return $this->$key ?? null;
    }

    /**
     * @return $this
     */
    public function withBaggageItem($key, $value)
    {
        $ctx = clone $this;
        $ctx->$key = $value;

        return $ctx;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this);
    }

    public function buildString()
    {
        return $this->traceId.':'.$this->spanId.':'.$this->parentId.':'.$this->flags;
    }

    public function isSampled()
    {
        return $this->flags == 1;
    }
}
