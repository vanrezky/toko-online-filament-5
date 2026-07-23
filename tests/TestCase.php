<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LogicException;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function refreshApplication()
    {
        parent::refreshApplication();

        $connection = $this->app['config']->get('database.default');
        $database = $this->app['config']->get("database.connections.{$connection}.database");

        if ($database !== 'toko_online_testing') {
            throw new LogicException("Refusing to run tests against database [{$database}].");
        }
    }
}
