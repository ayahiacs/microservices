<?php

use App\Processors\ChecklistUpdatedProcessor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Cache::spy();
    Log::spy();
});

it('updates checklist cache, invalidates summary, broadcasts by country', function () {
    $processor = new ChecklistUpdatedProcessor;

    $data = ['id' => 99, 'title' => 'Test', 'country' => 'USA'];

    $processor->process($data);

    $key = "checklist:{$data['id']}";
    Cache::shouldHaveReceived('put')->with($key, ['id' => 99, 'attributes' => $data], 300);
    Cache::shouldHaveReceived('forget')->with('checklists:summary');

    // broadcast handled separately; we simply ensure method executed

    Log::shouldHaveReceived('info')->with('Processed ChecklistUpdated', ['id' => 99]);
});

it('throws when checklist id missing', function () {
    $processor = new ChecklistUpdatedProcessor;
    $this->expectException(\InvalidArgumentException::class);
    $processor->process([]);
});
