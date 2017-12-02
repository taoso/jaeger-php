<?php
namespace Jaeger\Sampler;

use PHPUnit\Framework\TestCase;

class ConstSamplerTest extends TestCase
{
    public function testTags()
    {
        $sampler = new ConstSampler(true);
        $tags = $sampler->getTags();

        self::assertEquals([
            'sampler.type' => 'const',
            'sampler.param' => true,
        ], $tags);
    }

    public function testDecision()
    {
        self::assertTrue((new ConstSampler(true))->isSampled());
        self::assertFalse((new ConstSampler(false))->isSampled());
    }
}
