<?php

namespace Jaeger\Sampler;

class ConstSampler implements Sampler
{
    private $decision = false;

    private $tags = [];

    public function __construct($decision = true)
    {
        $this->decision = $decision;
        $this->tags[self::SAMPLER_TYPE_TAG_KEY] = 'const';
        $this->tags[self::SAMPLER_PARAM_TAG_KEY] = $decision;
    }

    public function isSampled()
    {
        return $this->decision;
    }

    public function getTags()
    {
        return $this->tags;
    }
}
