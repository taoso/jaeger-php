<?php

namespace Jaeger\Transport;

use Jaeger\Jaeger;

interface Transport
{
    function append(Jaeger $jaeger);

    function flush();
}
