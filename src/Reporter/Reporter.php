<?php

namespace Jaeger\Reporter;

use Jaeger\Jaeger;

interface Reporter
{
    function report(Jaeger $jaeger);

    function close();
}
