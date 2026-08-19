<?php

namespace Tests\Support;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class CorrelationTestChannel
{
    public static TestHandler $handler;

    /** @param  array<string, mixed>  $config */
    public function __invoke(array $config): LoggerInterface
    {
        self::$handler = new TestHandler;

        $logger = new Logger('correlation-test');
        $logger->pushHandler(self::$handler);

        return $logger;
    }
}
