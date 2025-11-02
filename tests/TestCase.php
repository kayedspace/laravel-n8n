<?php

namespace Tests;

use Illuminate\Support\Facades\Http;
use KayedSpace\N8n\N8nServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            N8nServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }
}
