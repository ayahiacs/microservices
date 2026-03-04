<?php

namespace App\Console\Commands;

use App\Services\RabbitPublisher;
use Illuminate\Console\Command;

class PublishTestEmployeeEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:publish-test-employee-event {--type=EmployeeCreated : The event type to send}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish a sample employee event to RabbitMQ for testing.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');

        // create or find a dummy employee record
        $employee = \App\Models\Employee::first() ?? \App\Models\Employee::factory()->create();

        $publisher = app(RabbitPublisher::class);
        $publisher->publish([
            'event_type' => $type,
            'event_id' => (string) \Illuminate\Support\Str::uuid(),
            'timestamp' => now()->toIso8601String(),
            'country' => $employee->country,
            'data' => [
                'employee_id' => $employee->id,
                'changed_fields' => [],
                'employee' => $employee->toArray(),
            ],
        ], env('RABBITMQ_QUEUE', 'hr.events'));

        $this->info("Published {$type} for employee {$employee->id}");

        return 0;
    }
}
