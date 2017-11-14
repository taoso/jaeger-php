<?php
require __DIR__.'/../vendor/autoload.php';

use GuzzleHttp\Client;
use Jaeger\Factory;
use OpenTracing\Formats;

//init server span start
$factory = Factory::getInstance();

$tracer = $factory->initTracer('user');

$serverSpan = $tracer->startSpan('example HTTP', []);

$client = new Client;
$clientSapn = $tracer->startSpan('get', ['child_of' => $serverSpan]);

$tracer->inject($clientSapn->getContext(), Formats\TEXT_MAP, $trace_id);

$url = 'http://127.0.0.1:8080';
$result = $client->get($url, ['headers' => [ 'My-Trace-Id' => $trace_id ]]);

$clientSapn->setTags(['http.url' => $url]);
$clientSapn->setTags(['http.method' => 'GET']);
$clientSapn->setTags(['http.status' => $result->getStatusCode()]);

$clientSapn->setTags(['http.result' => $result->getBody()]);
$clientSapn->finish();

$clientSapn = $tracer->startSpan('get', ['child_of' => $serverSpan]);

$url = 'http://myip.ipip.net';
$result = $client->get($url);

$clientSapn->setTags(['http.url' => $url]);
$clientSapn->setTags(['http.method' => 'GET']);
$clientSapn->setTags(['http.status' => $result->getStatusCode()]);

$clientSapn->setTags(['http.result' => (string)$result->getBody()]);
$clientSapn->finish();

$serverSpan->finish();
$tracer->flush();

echo "success\r\n";
