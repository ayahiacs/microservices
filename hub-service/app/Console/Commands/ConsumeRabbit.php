<?php

namespace App\Console\Commands;

use App\Services\RabbitConsumer;
use Illuminate\Console\Command;

class ConsumeRabbit extends Command
{
    protected $signature = 'rabbit:consume {queue?}';

    protected $description = 'Consume messages from RabbitMQ and dispatch to processors';

    public function handle(): int
    {
        $queue = $this->argument('queue');

        $this->info('Starting RabbitMQ consumer...');

        $consumer = new RabbitConsumer;
        $consumer->consume($queue);

        return 0;
    }
}
