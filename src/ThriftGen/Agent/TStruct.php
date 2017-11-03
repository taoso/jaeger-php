<?php

namespace Jaeger\ThriftGen\Agent;

use Thrift\Protocol\TProtocol;

interface TStruct
{
    function write(TProtocol $t);

    function read(TProtocol $t);
}
