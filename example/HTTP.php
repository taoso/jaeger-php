<?php

require __DIR__.'/../vendor/autoload.php';

use Jaeger\Factory;
use GuzzleHttp\Client;
use OpenTracing\Carriers\TextMap;

//init server span start
$factory = Factory::getInstance();

$tracer = $factory->initTracer('gift');

$injectTarget = [\Jaeger\Helper::TRACE_HEADER_NAME => $_SERVER['HTTP_UBER_TRACE_ID']];
$textMap = TextMap::fromArray($injectTarget);
$spanContext = $tracer->extract('text_map', $textMap);
$serverSpan = $tracer->startSpan('bar HTTP', ['child_of' => $spanContext]);

$injectTarget1 = [];
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
