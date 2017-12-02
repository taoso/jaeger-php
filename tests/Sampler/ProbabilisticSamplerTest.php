<?php
namespace Jaeger\Sampler;

use PHPUnit\Framework\TestCase;

function mt_rand($min, $max)
{
    if (1 / $max == 0.5) {
        return 1;
    }

    if (1 / $max == 0.4) {
        return 2;
    }
}

class ProbabilisticSamplerTest extends TestCase
{
    public function testTags()
    {
        $sampler = new ProbabilisticSampler(0.1);
        $tags = $sampler->getTags();

        self::assertEquals([
            'sampler.type' => 'probabilistic',
            'sampler.param' => 0.1,
        ], $tags);
    }

    public function testRate()
    {
        $sampler = new ProbabilisticSampler(0.5);
        self::assertTrue($sampler->isSampled());

        $sampler = new ProbabilisticSampler(0.4);
        self::assertFalse($sampler->isSampled());
    }
}
