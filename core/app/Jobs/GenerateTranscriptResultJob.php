<?php

namespace App\Jobs;

use App\Models\TranscriptResult;
use App\Services\TranscriptOptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Generate one transcript option (summary / agent_customer / description) from the recording's saved transcript.
 * Runs for the company that queued it (the queue payload carries the company). A completed result is left alone.
 */
class GenerateTranscriptResultJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(public int $resultId)
    {
    }

    public function handle(TranscriptOptionService $service): void
    {
        $result = TranscriptResult::find($this->resultId);
        if ($result && $result->status !== 'completed') {
            $service->generate($result);
        }
    }

    public function failed(\Throwable $e): void
    {
        TranscriptResult::where('id', $this->resultId)->where('status', '!=', 'completed')
            ->update(['status' => 'failed', 'error_message' => mb_substr('Generation stopped: ' . $e->getMessage(), 0, 500), 'updated_at' => now()]);
    }
}
