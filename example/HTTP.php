<?php

require __DIR__.'/../vendor/autoload.php';

use Jaeger\Factory;
use GuzzleHttp\Client;
use OpenTracing\Formats;

//init server span start
$factory = Factory::getInstance();

$tracer = $factory->initTracer('gift');

$trace_id = $_SERVER['HTTP_MY_TRACE_ID'];
$spanContext = $tracer->extract(Formats\TEXT_MAP, $trace_id);
$serverSpan = $tracer->startSpan('bar HTTP', ['child_of' => $spanContext]);

$clientSapn1 = $tracer->startSpan('HTTP1', ['child_of' => $serverSpan->getContext()]);

$method = 'GET';
$url = 'http://myip.ipip.net';
$client = new Client();
$res = $client->request($method, $url);

$clientSapn1->setTags(['http.status_code' => $res->getStatusCode()
    , 'http.method' => 'GET', 'http.url' => $url]);
$clientSapn1->finish();
//client span1 end

//client span2 start
$clientSpan2 = $tracer->startSpan('HTTP2', ['child_of' => $serverSpan->getContext()]);

$method = 'GET';
$url = 'http://myip.ipip.net';
$client = new Client();
$res = $client->request($method, $url);

$clientSpan2->setTags(['http.status_code' => $res->getStatusCode()
    , 'http.method' => 'GET', 'http.url' => $url]);
$clientSpan2->finish();
//client span2 end

//server span end
$serverSpan->finish();
$tracer->flush();

echo "success\r\n";
