<?php

namespace Jaeger;

use OpenTracing\Span;
use OpenTracing\SpanContext;
use OpenTracing\SpanOptions;
use OpenTracing\Formats;
use OpenTracing\Tracer;
use Jaeger\Sampler\Sampler;
use Jaeger\Reporter\Reporter;
use Jaeger\ThriftGen\Agent\Tags;

class Jaeger implements Tracer
{
    /**
     * @var Reporter
     */
    private $reporter;

    /**
     * @var Sampler
     */
    private $sampler;

    private $spans = [];

    private $tags = [];

    private $serverName;

    private $processThrift;

    public function __construct(string $serverName, Reporter $reporter, Sampler $sampler)
    {
        $this->serverName = $serverName;
        $this->reporter = $reporter;
        $this->sampler = $sampler;

        $this->tags = array_merge($this->tags, $this->sampler->getTags());
    }

    public function getServerName()
    {
        return $this->serverName;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function startSpan($operationName, $options = [])
    {
        if (is_array($options)) {
            $options = SpanOptions::create($options);
        }

        $references = $options->getReferences();

        if ($references) { // TODO support multiple references
            $parent = $references[0]->getContext();
            $id = Helper::toHex(Helper::identifier());

            $context = new JSpanContext($parent->traceId, $id, $parent->spanId, $parent->flags);
        } else {
            $traceId = $spanId = Helper::toHex(Helper::identifier());
            $flags = (int)$this->sampler->IsSampled();

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
        if ($format == Formats\TEXT_MAP) {
            $carrier = $spanContext->buildString();
        } else {
            throw new Exception("not support format");
        }
    }

    public function extract($format, $carrier)
    {
        if ($format == Formats\TEXT_MAP && is_string($carrier)) {
            list($traceId, $spanId, $parentId, $flags) = explode(':', $carrier);
            return new JSpanContext($traceId, $spanId, $parentId, $flags);

            return new JSpanContext(0, 0, 0, 0);
        } else {
            throw new Exception("not support format");
        }
    }

    /**
     * @var JSpan[]
     */
    public function getSpans()
    {
        return $this->spans;
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

    public function buildProcessThrift()
    {
        if ($this->processThrift) {
            return $this->processThrift;
        }

        $tagsObj = Tags::getInstance();
        $tagsObj->setTags($this->getTags());
        $thriftTags = $tagsObj->buildTags();

        $this->processThrift = [
            'serverName' => $this->serverName,
            'tags' => $thriftTags,
        ];

        return $this->processThrift;
    }
}
