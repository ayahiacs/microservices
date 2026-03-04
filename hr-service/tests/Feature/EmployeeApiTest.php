<?php

use App\Models\Employee;
use App\Services\RabbitPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // migrate fresh database for each test
    $this->artisan('migrate');
});

it('can create a usa employee and publish event', function () {
    $publisher = Mockery::spy(RabbitPublisher::class);
    $this->instance(RabbitPublisher::class, $publisher);

    $payload = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'salary_per_annum' => 75000,
        'country' => 'USA',
        'country_data' => [
            'ssn' => '123-45-6789',
            'address' => '123 Main St, New York, NY',
        ],
    ];

    $response = $this->postJson('/api/employees', $payload);
    $response->assertStatus(201)
        ->assertJsonFragment(['first_name' => 'John']);

    $publisher->shouldHaveReceived('publish')->once()->withArgs(function ($payload) {
        return $payload['event_type'] === 'EmployeeCreated';
    });
});

it('can update an employee and publish update event', function () {
    // create record without the spy so we don't capture the creation message
    $employee = Employee::factory()->create(['country' => 'USA']);

    $publisher = Mockery::spy(RabbitPublisher::class);
    $this->instance(RabbitPublisher::class, $publisher);

    $response = $this->putJson("/api/employees/{$employee->id}", ['salary_per_annum' => 80000]);
    $response->assertOk()->assertJson(['salary_per_annum' => 80000]);

    $publisher->shouldHaveReceived('publish')->once()->withArgs(function ($payload) {
        return $payload['event_type'] === 'EmployeeUpdated'
            && in_array('salary_per_annum', $payload['data']['changed_fields']);
    });
});

it('can delete an employee and publish delete event', function () {
    // create record before spying to avoid catching the creation event
    $employee = Employee::factory()->create();

    $publisher = Mockery::spy(RabbitPublisher::class);
    $this->instance(RabbitPublisher::class, $publisher);

    $response = $this->deleteJson("/api/employees/{$employee->id}");
    $response->assertNoContent();

    $publisher->shouldHaveReceived('publish')->once()->withArgs(function ($payload) {
        return $payload['event_type'] === 'EmployeeDeleted';
    });
});

it('validates country-specific fields', function () {
    $response = $this->postJson('/api/employees', [
        'first_name' => 'Hans',
        'last_name' => 'Mueller',
        'salary_per_annum' => 65000,
        'country' => 'Germany',
        // missing tax_id and goal
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['country_data.tax_id', 'country_data.goal']);
});

// the direct publishing behaviour is already covered by the previous tests
