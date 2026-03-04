<?php

use App\Processors\EmployeeUpdatedProcessor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    // use spy so we can assert interactions without replacing store
    Cache::spy();
    Log::spy();
    Redis::spy();
});

it('updates cache, invalidates list, broadcasts and logs', function () {
    $processor = new EmployeeUpdatedProcessor;

    $event = [
        'data' => [
            'employee' => [
                'id' => 42,
                'name' => 'Alice',
                'country' => 'USA',
            ],
        ],
    ];

    $processor->process($event);

    // Verify Redis operations for country-specific indexing
    $hashKey = 'employees:map:USA';
    Redis::shouldHaveReceived('hset')->with($hashKey, 42, json_encode($event['data']['employee']));
    Redis::shouldHaveReceived('expire')->with($hashKey, 3600);

    // Verify checklist cache invalidation (via ChecklistService)
    Cache::shouldHaveReceived('forget')->with('checklists:USA');

    // we don't assert broadcast driver here, just ensure no exceptions were thrown

    Log::shouldHaveReceived('info')->with('Processed EmployeeUpdated', ['id' => 42]);
});

it('throws when id is missing', function () {
    $processor = new EmployeeUpdatedProcessor;
    $this->expectException(\InvalidArgumentException::class);
    $processor->process(['data' => ['employee' => []]]);
});
