<?php
namespace Jaeger;

use PHPUnit\Framework\TestCase;
use Jaeger\Thrift\TagType;

class Foo
{
    use ThriftTags;

    public function test($tags)
    {
        return $this->buildTags($tags);
    }
}

class ThriftTagsTest extends TestCase
{
    public function testBuildTags()
    {
        $tags = (new Foo)->test([
            'a' => 1,
            'b' => 1.1,
            'c' => true,
            'd' => 'foo',
            'e' => ['a' => '吕'],
            'f' => null,
        ]);

        $tag = $tags[0];
        self::assertEquals('a', $tag->key);
        self::assertEquals(TagType::LONG, $tag->vType);
        self::assertEquals(1, $tag->vLong);

        $tag = $tags[1];
        self::assertEquals('b', $tag->key);
        self::assertEquals(TagType::DOUBLE, $tag->vType);
        self::assertEquals(1.1, $tag->vDouble);

        $tag = $tags[2];
        self::assertEquals('c', $tag->key);
        self::assertEquals(TagType::BOOL, $tag->vType);
        self::assertEquals(true, $tag->vBool);

        $tag = $tags[3];
        self::assertEquals('d', $tag->key);
        self::assertEquals(TagType::STRING, $tag->vType);
        self::assertEquals('foo', $tag->vStr);

        $tag = $tags[4];
        self::assertEquals('e', $tag->key);
        self::assertEquals(TagType::STRING, $tag->vType);
        self::assertEquals('{"a":"吕"}', $tag->vStr);

        $tag = $tags[5];
        self::assertEquals('f', $tag->key);
        self::assertEquals(TagType::STRING, $tag->vType);
        self::assertEquals('', $tag->vStr);
    }
}
