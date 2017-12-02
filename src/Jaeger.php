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

    public function startSpan($operationName, $options = [])
    {
        if (is_array($options)) {
            $options = SpanOptions::create($options);
        }

        $references = $options->getReferences();

        if ($references) { // TODO support multiple references
            $parent = $references[0]->getContext();
            $id = self::toHex(self::identifier());

            $context = new JSpanContext($parent->traceId, $id, $parent->spanId, $parent->flags);
        } else {
            $traceId = $spanId = self::toHex(self::identifier());
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
        if ($format == Formats\BINARY) {
            $carrier = $spanContext->buildString();
        } else {
            throw new \Exception("not support format $format");
        }
    }

    public function extract($format, $carrier)
    {
        if ($format == Formats\BINARY && is_string($carrier)) {
            list($traceId, $spanId, $parentId, $flags) = explode(':', $carrier);
            return new JSpanContext($traceId, $spanId, $parentId, $flags);

            return new JSpanContext(0, 0, 0, 0);
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
        $tagsObj->setTags($this->tags);
        $thriftTags = $tagsObj->buildTags();

        $this->processThrift = [
            'serverName' => $this->serverName,
            'tags' => $thriftTags,
        ];

        return $this->processThrift;
    }

    private static function identifier()
    {
        return strrev(microtime(true) * 10000 . rand(1000, 9999));
    }

    private static function toHex($string1, $string2 = "")
    {
        if ($string2 == "") {
            return sprintf("%x", $string1);
        } else {
            return sprintf("%x%016x", $string1, $string2);
        }
    }
}
