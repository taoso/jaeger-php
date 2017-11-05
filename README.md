# jaeger-php

Jaeger UDP client for PHP OpenTracing API.

Forked from [jukylin/jaeger-php](https://github.com/jukylin/jaeger-php).

## Why fork?

Jukylin's work is awesome. But it doese not work with the latest [opentracing/opentracing-php](https://github.com/opentracing/opentracing-php).

This repo fix its issue and make a huge refactor for simplicity and psr.

Feel free to choose this repo or Jukylin's.

## Install

```
composer config minimum-stability dev
composer config prefer-stable
composer require lvht/jaeger
composer update
```

## Usage

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
