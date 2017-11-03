<?php

namespace Jaeger\Sampler;

use Jaeger\Helper;

class ProbabilisticSampler implements Sampler
{
    // min 0, max 1
    private $rate = 0;

    private $tags = [];

    public function __construct($rate = 0.0001)
    {
        $this->rate = $rate;
        $this->tags[Helper::SAMPLER_TYPE_TAG_KEY] = 'probabilistic';
        $this->tags[Helper::SAMPLER_PARAM_TAG_KEY] = $rate;
    }

    public function isSampled()
    {
        if (mt_rand(1, 1 / $this->rate) == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function getTags()
    {
        return $this->tags;
    }
}
