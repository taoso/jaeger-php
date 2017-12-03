<?php

namespace Jaeger;

use OpenTracing\Span;
use OpenTracing\SpanContext;
use OpenTracing\SpanOptions;
use OpenTracing\Formats;
use OpenTracing\Tracer;
use Jaeger\Thrift\Process;
use Jaeger\Thrift\Batch;
use Jaeger\Sampler\Sampler;
use Jaeger\Reporter\Reporter;

class Jaeger implements Tracer
{
    use ThriftTags;

    /**
     * @var Reporter
     */
    private $reporter;

    /**
     * @var Sampler
     */
    private $sampler;

    private $spans = [];

    private $tags;

    private $serviceName;

    public function __construct(string $serviceName, Reporter $reporter, Sampler $sampler)
    {
        $this->serviceName = $serviceName;
        $this->reporter = $reporter;
        $this->sampler = $sampler;

        $this->tags = $this->sampler->getTags();
    }

    public function buildProcessThrift() : Process
    {
        return new Process([
            'serviceName' => $this->serviceName,
            'tags' => $this->buildTags($this->tags),
        ]);
    }

    public function startSpan($operationName, $options = [])
    {
        if (is_array($options)) {
            $options = SpanOptions::create($options);
        }

        $references = $options->getReferences();

        if ($references) { // TODO support multiple references
            $parent = $references[0]->getContext();
            $id = self::generateId();

            $context = new JSpanContext($parent->traceId, $id, $parent->spanId, $parent->flags);
        } else {
            $traceId = $spanId = self::generateId();
            $flags = (int) $this->sampler->isSampled();

            $context = new JSpanContext($traceId, $spanId, 0, $flags);
        }

        $span = new JSpan($operationName, $context);
        $span->setTags($options->getTags());

        if ($context->isSampled()) {
            $this->spans[] = $span;
        }

        return $span;
    }

    public function inject(SpanContext $spanContext, $format, &$carrier)
    {
        if ($format == Formats\BINARY) {
            $carrier = $spanContext->buildString();
        } else {
            throw new \Exception("not support format $format");
        }
    }

    public function extract($format, $carrier)
    {
        if ($format == Formats\BINARY && is_string($carrier)) {
            $parts = explode(':', $carrier);
            if (count($parts) !== 4) {
                return new JSpanContext(0, 0, 0, 0);
            }

            list($traceId, $spanId, $parentId, $flags) = $parts;
            return new JSpanContext($traceId, $spanId, $parentId, $flags);

        } else {
            throw new \Exception("not support format $format");
        }
    }

    /**
     * @var JSpan[]
     */
    public function getSpans()
    {
        return $this->spans;
    }

    public function buildThrift() : Batch
    {
        $spans = [];
        foreach ($this->spans as $span) {
            $spans[] = $span->buildThrift();
        }

        return new Batch(['process' => $this->buildProcessThrift(), 'spans' => $spans]);
    }

    public function reportSpan()
    {
        if ($this->spans) {
            $this->reporter->report($this);
            $this->spans = [];
        }
    }

    public function flush()
    {
        $this->reportSpan();
        $this->reporter->close();
    }

    private static function generateId()
    {
        $id = strrev(microtime(true) * 10000 . rand(1000, 9999));
        return sprintf("%x", $id);
    }
}
