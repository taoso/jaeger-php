# jaeger-php

[![Build Status](https://travis-ci.org/lvht/jaeger-php.svg?branch=master)](https://travis-ci.org/lvht/jaeger-php)
[![Coverage Status](https://coveralls.io/repos/github/lvht/jaeger-php/badge.svg?branch=master)](https://coveralls.io/github/lvht/jaeger-php?branch=master)

Jaeger UDP client for PHP OpenTracing API.

Forked from [jukylin/jaeger-php](https://github.com/jukylin/jaeger-php).

## Why fork?

Jukylin's work is awesome. But it doese not work with the latest [opentracing/opentracing-php](https://github.com/opentracing/opentracing-php).

This repo fix its issue and make a huge refactor for simplicity and psr.

Feel free to choose this repo or Jukylin's.

## Install

```
composer config minimum-stability dev
composer config prefer-stable 1
composer require lvht/jaeger
```

## Usage
```php
<?php
use Jaeger\Factory;
use OpenTracing\Formats;

// init factory
$factory = Factory::getInstance();
// make OpenTracing\Tracer instance
$tracer = $factory->initTracer('user', '127.0.0.1', 6831);

// extract parent infomation from http header
$carrier = $_SERVER['HTTP_UBER_TRACE_ID'];
// extract the infomation and generate a new context
// only support binary carrier now
$context = $tracer->extract(Formats\BINARY, $carrier);

// make a new span
$span = $tracer->startSpan('foo', ['child_of' => $context]);

// do your job here

// finish the span
$span->finish();

// report infomation to jaeger
$tracer->flush();
```

## Example

Run jaeger in docker

```
docker run --rm -d -p 6831:6831/udp -p 16686:16686 jaegertracing/all-in-one:latest
```

Start demo http server

```
cd example
php -S 0.0.0.0:8080 HTTP.php
```

Run Hprose.php

```
cd example
php Hprose.php
```

So you can see the Jaeger UI in http://127.0.0.1:16686

Good luck :)

## Features

- Transports
    - via Thrift over UDP

- Sampling
    - ConstSampler
    - ProbabilisticSampler

## Reference

[OpenTracing](http://opentracing.io/)

[Jaeger](https://uber.github.io/jaeger/)
