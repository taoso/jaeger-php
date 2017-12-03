<?php
namespace Jaeger\Transport;

use Thrift\Transport\TTransport;
use Thrift\Factory\TStringFuncFactory;

class TEmptyMemoryBuffer extends TTransport
{
    private $length = 0;

    public function isOpen()
    {
        return true;
    }

    /**
     * @codeCoverageIgnore
     */
    public function open()
    {
    }

    /**
     * @codeCoverageIgnore
     */
    public function close()
    {
    }

    public function read($len)
    {
        return '';
    }

    public function write($buf)
    {
        $this->length += TStringFuncFactory::create()->strlen($buf);
    }

    public function flush()
    {
        $length = $this->length;
        $this->length = 0;

        return $length;
    }
}
