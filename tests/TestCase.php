<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if (! $app->environment('testing') || $database !== 'jamunasoft_site_test') {
            throw new \RuntimeException('Tests require the testing environment and jamunasoft_site_test database. Clear the configuration cache before running tests.');
        }

        return $app;
    }
}
