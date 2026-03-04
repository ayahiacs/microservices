<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Simple wrapper around php-amqplib to publish messages to RabbitMQ.
 *
 * This keeps the details out of jobs/controllers and allows the connection
 * to be mocked in tests if necessary.
 */
class RabbitPublisher
{
    protected AMQPStreamConnection $connection;

    public function __construct()
    {
        $host = config('rabbitmq.host', '127.0.0.1');
        $port = config('rabbitmq.port', 5672);
        $user = config('rabbitmq.user', 'guest');
        $pass = config('rabbitmq.password', 'guest');

        $this->connection = new AMQPStreamConnection($host, $port, $user, $pass);
    }

    /**
     * Publish a payload to the specified queue (or default from env).
     */
    public function publish(array $payload, ?string $queue = null): void
    {
        $channel = $this->connection->channel();

        $queue = $queue ?? config('rabbitmq.queue', 'hr.events');
        // durable queue, not auto-deleted
        $channel->queue_declare($queue, false, true, false, false);

        $message = new AMQPMessage(
            json_encode($payload),
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]
        );

        $channel->basic_publish($message, '', $queue);
        Log::info('Published', ['event_type' => $payload['event_type'] ?? 'unknown', 'queue' => $queue]);

        $channel->close();
        $this->connection->close();
    }
}
