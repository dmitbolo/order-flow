<?php

namespace App\Jobs\Concerns;

use Illuminate\Support\Facades\Log;
use Throwable;

trait TracksJobExecution
{
    private ?float $executionStartedAt = null;

    protected function startTracking(): void
    {
        $this->executionStartedAt = microtime(true);
    }

    /** @param array<string, mixed> $context */
    protected function logJobSucceeded(array $context): void
    {
        $finishedAt = microtime(true);

        Log::info('queue_job_succeeded', [
            ...$context,
            'execution_ms' => $this->executionDurationMs($finishedAt),
            'total_duration_ms' => $this->elapsedMs(
                (float) $context['dispatched_at'],
                $finishedAt,
            ),
            'attempt' => $this->currentAttempt(),
        ]);
    }

    /** @param array<string, mixed> $context */
    protected function logJobFailed(Throwable $exception, array $context): void
    {
        Log::error('queue_job_failed', [
            ...$context,
            'total_duration_ms' => $this->elapsedMs((float) $context['dispatched_at']),
            'attempt' => $this->currentAttempt(),
            'exception_class' => $exception::class,
            'exception_message' => $exception->getMessage(),
            'exception' => $exception,
        ]);
    }

    private function currentAttempt(): int
    {
        return $this->job?->attempts() ?? 1;
    }

    private function executionDurationMs(float $finishedAt): int
    {
        return $this->elapsedMs($this->executionStartedAt ?? $finishedAt, $finishedAt);
    }

    private function elapsedMs(float $startedAt, ?float $finishedAt = null): int
    {
        return max(0, (int) round((($finishedAt ?? microtime(true)) - $startedAt) * 1000));
    }
}
