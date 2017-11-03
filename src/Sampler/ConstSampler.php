<?php

namespace Jaeger\Sampler;

use Jaeger\Helper;

class ConstSampler implements Sampler
{
    private $decision = '';

    private $tags = [];

    public function __construct($decision = true)
    {
        $this->decision = $decision;
        $this->tags[Helper::SAMPLER_TYPE_TAG_KEY] = 'const';
        $this->tags[Helper::SAMPLER_PARAM_TAG_KEY] = $decision;
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
