<?php

namespace Jaeger;

use Jaeger\Thrift\Tag;
use Jaeger\Thrift\TagType;

trait ThriftTags
{
    private function buildTags($tags)
    {
        $thriftTags = [];

        foreach ($tags as $k => $v) {
            switch (gettype($v)) {
                case "boolean":
                    $thriftTags[] = new Tag([
                        'key' => $k,
                        'vType' => TagType::BOOL,
                        'vBool' => $v,
                    ]);
                    break;
                case "double":
                    $thriftTags[] = new Tag([
                        'key' => $k,
                        'vType' => TagType::DOUBLE,
                        'vDouble' => $v,
                    ]);
                    break;
                case "integer":
                    $thriftTags[] = new Tag([
                        'key' => $k,
                        'vType' => TagType::LONG,
                        'vLong' => $v,
                    ]);
                    break;
                case "array":
                    $v = json_encode($v, JSON_UNESCAPED_UNICODE);
                default:
                    $thriftTags[] = new Tag([
                        'key' => $k,
                        'vType' => TagType::STRING,
                        'vStr' => (string)$v,
                    ]);
            }
        }

        return $thriftTags;
    }
}
