<?php

namespace Jaeger\Sampler;

interface Sampler
{
    public function isSampled();

    public function getTags();
}
