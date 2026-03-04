<?php

namespace App\Services;

use App\Processors\ChecklistUpdatedProcessor;
use App\Processors\EmployeeCreatedProcessor;
use App\Processors\EmployeeDeletedProcessor;
use App\Processors\EmployeeUpdatedProcessor;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitConsumer
{
    protected AMQPStreamConnection $connection;

    public function __construct()
    {
        $host = config('rabbitmq.host', '127.0.0.1');
        $port = (int) config('rabbitmq.port', 5672);
        $user = config('rabbitmq.user', 'guest');
        $pass = config('rabbitmq.password', 'guest');

        $this->connection = new AMQPStreamConnection($host, $port, $user, $pass);
    }

    public function consume(?string $queue = null): void
    {
        $channel = $this->connection->channel();

        $queue = $queue ?: config('rabbitmq.queue', 'hr.events');

        $channel->queue_declare($queue, false, true, false, false);

        $callback = function (AMQPMessage $msg) use ($channel) {
            $body = (string) $msg->getBody();

            try {
                $payload = json_decode($body, true);

                if (! is_array($payload) || empty($payload['event_type'])) {
                    throw new \RuntimeException('Invalid message payload');
                }

                // Route by event type
                switch ($payload['event_type']) {
                    case 'EmployeeCreated':
                        $processor = new EmployeeCreatedProcessor;
                        break;
                    case 'EmployeeUpdated':
                        $processor = new EmployeeUpdatedProcessor;
                        break;
                    case 'EmployeeDeleted':
                        $processor = new EmployeeDeletedProcessor;
                        break;
                    case 'ChecklistUpdated':
                        $processor = new ChecklistUpdatedProcessor;
                        break;
                    default:
                        Log::warning('Unhandled event type', ['event_type' => $payload['event_type']]);
                        $channel->basic_ack($msg->getDeliveryTag());

                        return;
                }
                $processor->process($payload ?? []);
                $channel->basic_ack($msg->getDeliveryTag());
            } catch (\Throwable $e) {
                Log::error('Error processing RabbitMQ message', ['error' => $e->getMessage(), 'body' => $body]);

                // Simple retry logic based on header 'x-attempts'
                $headers = $msg->has('application_headers') ? $msg->get('application_headers')->getNativeData() : [];
                $attempts = intval($headers['x-attempts'] ?? 0) + 1;

                if ($attempts <= (int) env('RABBITMQ_MAX_RETRIES', 5)) {
                    // Re-publish to same queue with incremented attempts header
                    $newMsg = new AMQPMessage($msg->getBody(), array_merge($msg->get_properties(), [
                        'application_headers' => new \PhpAmqpLib\Wire\AMQPTable(['x-attempts' => $attempts]),
                    ]));
                    $channel->basic_publish($newMsg, '', $msg->get('routing_key') ?: env('RABBITMQ_QUEUE', 'hub.events'));
                    $channel->basic_ack($msg->getDeliveryTag());
                } else {
                    // Move to dead-letter (or just ack to remove)
                    Log::error('Message exceeded retry attempts, dropping', ['attempts' => $attempts]);
                    $channel->basic_ack($msg->getDeliveryTag());
                }
            }
        };

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume($queue, '', false, false, false, false, $callback);

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }
}
