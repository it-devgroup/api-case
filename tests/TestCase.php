<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    /**
     * Assert the response is a single-error JSON:API document matching the given attributes.
     */
    protected function assertJsonApiError(TestResponse $response, string $status, ?string $pointer = null, ?string $code = null): void
    {
        $response->assertJsonPath('errors.0.status', $status);

        if ($pointer !== null) {
            $response->assertJsonPath('errors.0.source.pointer', $pointer);
        }

        if ($code !== null) {
            $response->assertJsonPath('errors.0.code', $code);
        }
    }

    /**
     * Assert the response's JSON:API "errors" array contains an error for each given source pointer.
     *
     * @param  array<int, string>  $pointers
     */
    protected function assertJsonApiErrorPointers(TestResponse $response, array $pointers): void
    {
        $actual = collect($response->json('errors'))->pluck('source.pointer')->all();

        foreach ($pointers as $pointer) {
            $this->assertContains($pointer, $actual);
        }
    }
}
