<?php

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\RedisQueue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class RedisQueueDeletionProbe implements ShouldQueue
{
    use Queueable;

    public function handle(): void {}
}

test('processed redis jobs are removed from the reserved queue', function () {
    Event::fake();

    $queueName = 'test-reserved-deletion-'.Str::uuid();
    $queue = Queue::connection('redis');

    expect($queue)->toBeInstanceOf(RedisQueue::class);

    try {
        $queue->push(new RedisQueueDeletionProbe, queue: $queueName);
        $job = $queue->pop($queueName);

        expect($job)->not->toBeNull();

        $job->delete();

        expect(Redis::zcard('queues:'.$queueName.':reserved'))->toBe(0);
    } finally {
        $queue->clear($queueName);
    }
});
