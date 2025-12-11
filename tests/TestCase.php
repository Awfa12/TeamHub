<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'session.driver' => 'array',
            'cache.default' => 'array',
            'queue.default' => 'sync',
        ]);

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }
}
