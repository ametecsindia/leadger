<?php

namespace App\Services;

use App\Jobs\GenerateTranscriptResultJob;
use App\Models\CallAiAnalysis;
use App\Models\TranscriptResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Transcript options of a call recording (Call Recordings > Transcript > Transcript Option):
 *  - summary:        a short call summary written by the AI model from the saved transcript;
 *  - agent_customer: what each speaker said / asked / explained, from the saved speaker-separated transcript;
 *  - description:    the complete saved conversation (speakers, timestamps, original language + English) - built
 *                    from the transcript itself, no AI call, nothing invented.
 *
 * The audio is transcribed once (existing TranscriptionBillingService pipeline, existing billing); every option is
 * generated from that saved transcript. A completed result is stored per recording and option (transcript_results)
 * and shown again when reopened - never generated or charged again. A failed attempt never replaces a completed one.
 */
class TranscriptOptionService
{
    /** A request still pending / processing after this long is treated as stopped (it may be started again). */
    private const STALE_MINUTES = 20;

    /** The saved transcript of a recording, when there is one usable for the options. */
    public static function transcriptOf(int $sourceCode, int $logId): ?CallAiAnalysis
    {
        $a = CallAiAnalysis::findFor($sourceCode, $logId);
        if (!$a || $a->status !== 'completed') {
            return null;
        }
        $hasText = !empty($a->conversation) || trim((string)$a->transcript_english) !== '' || trim((string)$a->transcript) !== '';
        return $hasText ? $a : null;
    }

    /**
     * Ask for one option on these recordings. Returns, per key, what happens:
     *   completed (already saved, shown again), in_progress, queued (transcript saved: only the option runs) or
     *   needs_transcript (the audio is transcribed first; the option follows).
     */
    public function request(array $keys, string $option, int $userId): array
    {
        $out = [];
        foreach ($keys as $key) {
            [$sc, $logId] = array_map('intval', explode('_', $key));
            [$result, $state] = $this->claim($sc, $logId, $option, $userId);
            if ($state !== 'claimed') {
                $out[$key] = ['state' => $state, 'id' => $result->id];   // completed (shown again) or in_progress
                continue;
            }
            if (self::transcriptOf($sc, $logId)) {
                GenerateTranscriptResultJob::dispatch($result->id);
                $out[$key] = ['state' => 'queued', 'id' => $result->id];
            } else {
                $out[$key] = ['state' => 'needs_transcript', 'id' => $result->id];
            }
        }
        return $out;
    }

    /**
     * The result row of a recording and option, made "pending" for a new request - under a row lock, so two clicks
     * never create two rows or two AI calls. Returns [row, completed | in_progress | claimed].
     */
    private function claim(int $sc, int $logId, string $option, int $userId): array
    {
        return DB::transaction(function () use ($sc, $logId, $option, $userId) {
            $r = TranscriptResult::where(['source_code' => $sc, 'log_id' => $logId, 'option' => $option])->lockForUpdate()->first();
            if ($r && $r->status === 'completed') {
                return [$r, 'completed'];
            }
            if ($r && in_array($r->status, ['pending', 'processing'], true) && $r->updated_at && $r->updated_at->gt(now()->subMinutes(self::STALE_MINUTES))) {
                return [$r, 'in_progress'];
            }
            try {
                $r = $r ?: new TranscriptResult(['source_code' => $sc, 'log_id' => $logId, 'option' => $option]);
                $r->fill(['company_id' => 1, 'status' => 'pending', 'error_message' => null, 'requested_by' => $userId]);
                $r->save();
            } catch (\Illuminate\Database\QueryException $e) {
                // the same request came in at the same moment: use that one
                return [TranscriptResult::where(['source_code' => $sc, 'log_id' => $logId, 'option' => $option])->firstOrFail(), 'in_progress'];
            }
            return [$r, 'claimed'];
        });
    }

    /** After the audio was transcribed (or failed): generate the waiting options of that recording, or fail them. */
    public static function afterTranscript(int $sourceCode, int $logId, bool $ok, ?string $error = null, ?int $jobId = null): void
    {
        // never in the way of the transcription itself (e.g. a company database not migrated yet)
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('transcript_results')) {
                return;
            }
            $waiting = TranscriptResult::where(['source_code' => $sourceCode, 'log_id' => $logId])->whereIn('status', ['pending', 'processing'])->get();
        } catch (\Throwable $e) {
            Log::warning('[TranscriptOption] waiting options not checked: ' . $e->getMessage());
            return;
        }
        foreach ($waiting as $r) {
            if ($ok) {
                $r->transcription_job_id = $jobId ?: $r->transcription_job_id;
                $r->save();
                GenerateTranscriptResultJob::dispatch($r->id);
            } else {
                $r->forceFill(['status' => 'failed', 'error_message' => 'Transcription failed: ' . ($error ?: 'unknown error'), 'transcription_job_id' => $jobId])->save();
            }
        }
    }

    /** Generate one result (queue worker). Never touches a completed result. */
    public function generate(TranscriptResult $r): void
    {
        if ($r->status === 'completed') {
            return;
        }
        $r->forceFill(['status' => 'processing'])->save();
        try {
            $a = self::transcriptOf((int)$r->source_code, (int)$r->log_id);
            if (!$a) {
                throw new \RuntimeException('This recording has no saved transcript yet.');
            }
            $meta = $this->meta((int)$r->source_code, (int)$r->log_id);
            [$data, $model, $tokens] = match ($r->option) {
                'summary' => $this->summary($a),
                'agent_customer' => $this->agentCustomer($a, $meta),
                'description' => [self::description($a), null, null],
                default => throw new \RuntimeException('Unknown transcript option.'),
            };
            $r->forceFill(['status' => 'completed', 'result' => $data, 'model' => $model, 'usage_tokens' => $tokens,
                'error_message' => null, 'completed_at' => now()])->save();
        } catch (\Throwable $e) {
            Log::warning('[TranscriptOption] ' . $r->option . ' failed for ' . $r->source_code . '_' . $r->log_id . ': ' . $e->getMessage());
            $r->forceFill(['status' => 'failed', 'error_message' => mb_substr($e->getMessage(), 0, 500)])->save();
        }
    }

    /* ------------------------------------------------------------------ options */

    /** The conversation as the AI reads it: speaker-separated English (Speaker 1 / 2 unless a user set the roles). */
    private static function conversationFor(CallAiAnalysis $a): string
    {
        if (!empty($a->conversation)) {
            return SarvamAiService::conversationText((array)$a->conversation, is_array($a->speaker_roles) ? $a->speaker_roles : null);
        }
        return trim((string)($a->transcript_english ?: $a->transcript));
    }

    private function summary(CallAiAnalysis $a): array
    {
        [$d, $err, $model, $tokens] = app(SarvamAiService::class)->chatJson(
            'You summarise a recorded phone call from its transcript. Use ONLY what is said in the transcript. Never invent names, '
            . 'products, prices, dates or outcomes. When something is not in the call, write "Not stated". Reply with one JSON object only.',
            "Call transcript:\n" . self::conversationFor($a) . "\n\nReturn JSON with keys: purpose (one sentence), customer_requirements "
            . '(array of short strings), key_points (array of short strings), interest_level (High|Medium|Low|Not stated), '
            . 'call_outcome (short), next_action (short).', 2500);
        if ($err) {
            throw new \RuntimeException($err);
        }
        $list = fn ($v) => array_values(array_filter(array_map(fn ($x) => trim((string)$x), (array)$v), 'strlen'));
        $text = fn ($v) => trim((string)$v) !== '' ? trim((string)$v) : 'Not stated';
        $level = ucfirst(strtolower(trim((string)($d['interest_level'] ?? ''))));
        return [[
            'purpose' => $text($d['purpose'] ?? ''),
            'customer_requirements' => $list($d['customer_requirements'] ?? []),
            'key_points' => $list($d['key_points'] ?? []),
            'interest_level' => in_array($level, ['High', 'Medium', 'Low'], true) ? $level : 'Not stated',
            'call_outcome' => $text($d['call_outcome'] ?? ''),
            'next_action' => $text($d['next_action'] ?? ''),
        ], $model, $tokens];
    }

    private function agentCustomer(CallAiAnalysis $a, array $meta): array
    {
        $turns = (array)$a->conversation;
        if (!$turns) {
            throw new \RuntimeException('The saved transcript has no separate speakers, so the agent and customer cannot be told apart.');
        }
        $speakers = array_values(array_unique(array_map(fn ($t) => (string)($t['speaker'] ?? 'S1'), $turns)));
        $known = is_array($a->speaker_roles) ? array_filter($a->speaker_roles, fn ($r) => in_array($r, ['agent', 'customer'], true)) : [];
        $roleNote = $known ? 'The speaker roles were set by a user and are certain: ' . json_encode($known) . '.'
            : 'Nobody has confirmed which speaker is the agent. Decide a role only when the transcript makes it clear (for example the speaker '
              . 'introduces the company or the product, or asks to buy); otherwise use "Unknown". Do not assume Speaker 1 is the agent.';
        [$d, $err, $model, $tokens] = app(SarvamAiService::class)->chatJson(
            'You analyse who said what in a recorded sales/support phone call. Use ONLY the transcript. Never invent statements, names, products '
            . 'or numbers. Quote or closely paraphrase what each speaker actually said. Reply with one JSON object only.',
            "Speakers: " . implode(', ', array_map(fn ($s) => 'S' . ltrim($s, 'Ss') . ' (shown as "Speaker ' . ltrim($s, 'Ss') . '")', $speakers)) . ". {$roleNote}\n"
            . "Call transcript:\n" . SarvamAiService::conversationText($turns, $known ?: null) . "\n\n"
            . 'Return JSON with keys: speakers (array, one per speaker: {speaker: "S1"/"S2", role: Agent|Customer|Unknown, statements: [], '
            . 'questions: [], information_given: [], requirements: [], objections: []}), customer_interest_level (High|Medium|Low|Not stated), '
            . 'role_basis (one short sentence: why the roles were decided, or that they could not be).', 4000);
        if ($err) {
            throw new \RuntimeException($err);
        }
        $list = fn ($v) => array_values(array_filter(array_map(fn ($x) => trim((string)$x), (array)$v), 'strlen'));
        $out = [];
        foreach ((array)($d['speakers'] ?? []) as $s) {
            $id = 'S' . ltrim((string)($s['speaker'] ?? ''), 'Ss');
            if (!in_array($id, array_map(fn ($x) => 'S' . ltrim($x, 'Ss'), $speakers), true)) {
                continue;   // only the speakers of this call
            }
            $role = $known[$id] ?? strtolower(trim((string)($s['role'] ?? '')));
            $role = in_array($role, ['agent', 'customer'], true) ? $role : 'unknown';
            $label = $role === 'agent' ? 'Agent' . ($meta['agent_name'] && $meta['agent_name'] !== '-' ? ' (' . $meta['agent_name'] . ')' : '')
                : ($role === 'customer' ? 'Customer' : 'Speaker ' . ltrim($id, 'S'));
            $out[] = ['speaker' => $id, 'role' => $role, 'label' => $label, 'statements' => $list($s['statements'] ?? []),
                'questions' => $list($s['questions'] ?? []), 'information_given' => $list($s['information_given'] ?? []),
                'requirements' => $list($s['requirements'] ?? []), 'objections' => $list($s['objections'] ?? [])];
        }
        if (!$out) {
            throw new \RuntimeException('The AI service did not return a usable speaker analysis.');
        }
        $level = ucfirst(strtolower(trim((string)($d['customer_interest_level'] ?? ''))));
        return [['speakers' => $out, 'roles_confirmed' => (bool)$known,
            'customer_interest_level' => in_array($level, ['High', 'Medium', 'Low'], true) ? $level : 'Not stated',
            'role_basis' => trim((string)($d['role_basis'] ?? '')) ?: ($known ? 'Roles set by a user.' : 'Roles could not be determined.')],
            $model, $tokens];
    }

    /** The complete saved conversation: no AI call, nothing added. */
    public static function description(CallAiAnalysis $a): array
    {
        $roles = is_array($a->speaker_roles) ? $a->speaker_roles : null;
        $turns = [];
        foreach ((array)$a->conversation as $t) {
            $text = trim((string)($t['text'] ?? ''));
            $sec = isset($t['start']) && $t['start'] !== null ? (int)$t['start'] : null;
            $turns[] = ['time' => $sec === null ? null : sprintf('%02d:%02d', intdiv($sec, 60), $sec % 60),
                'speaker' => SarvamAiService::speakerLabel((string)($t['speaker'] ?? 'S1'), $roles),
                'text' => preg_match('/[\p{L}\p{N}]/u', $text) ? $text : '[unclear speech]'];
        }
        return ['turns' => $turns, 'original' => trim((string)$a->transcript), 'english' => trim((string)$a->transcript_english),
            'language' => $a->source_language, 'speakers_separated' => (bool)$turns];
    }

    /* ------------------------------------------------------------------ reading */

    /** Recording details shown in the popup (no file paths). */
    public function meta(int $sourceCode, int $logId): array
    {
        $rec = (new TranscriptionBillingService())->getRecordingDetails($sourceCode, $logId) ?: [];
        return ['recording_id' => $sourceCode . '_' . $logId, 'agent_name' => $rec['agent_name'] ?? '-', 'customer_number' => $rec['phonenumber'] ?? '-',
            'customer_name' => $rec['customer_name'] ?? '-',
            'duration' => \App\Helpers\Helper::formatDuration((int)($rec['duration'] ?? 0))];
    }

    /** The three options of a recording as the popup shows them. */
    public function forRecording(int $sourceCode, int $logId): array
    {
        $rows = TranscriptResult::where(['source_code' => $sourceCode, 'log_id' => $logId])->get()->keyBy('option');
        $out = [];
        foreach (TranscriptResult::OPTIONS as $key => $label) {
            $r = $rows[$key] ?? null;
            $stale = $r && in_array($r->status, ['pending', 'processing'], true) && $r->updated_at && $r->updated_at->lt(now()->subMinutes(self::STALE_MINUTES));
            $out[$key] = ['option' => $key, 'label' => $label, 'title' => TranscriptResult::TITLES[$key],
                'status' => $r ? ($stale ? 'failed' : $r->status) : 'none',
                'result' => $r && $r->status === 'completed' ? $r->result : null,
                'error' => $stale ? 'Generation stopped (the background worker did not finish it). Generate it again.' : ($r->error_message ?? null),
                'model' => $r->model ?? null, 'usage_tokens' => $r->usage_tokens ?? null,
                'completed_at' => $r && $r->completed_at ? $r->completed_at->format('d M Y, h:i A') : null];
        }
        return $out;
    }

    /** A completed result as plain text (Copy / Download). */
    public static function asText(string $option, array $result, array $meta): string
    {
        $lines = [TranscriptResult::TITLES[$option] ?? 'Transcript', 'Recording ' . $meta['recording_id'] . ' | Agent: ' . $meta['agent_name']
            . ' | Customer: ' . $meta['customer_number'] . ' | Duration: ' . $meta['duration'], ''];
        $bullets = function (string $title, array $items) use (&$lines) {
            if (!$items) return;
            $lines[] = $title . ':';
            foreach ($items as $i) $lines[] = '- ' . $i;
        };
        if ($option === 'summary') {
            $lines[] = 'Purpose: ' . $result['purpose'];
            $bullets('Customer requirements', $result['customer_requirements']);
            $bullets('Key discussion points', $result['key_points']);
            $lines[] = 'Customer interest level: ' . $result['interest_level'];
            $lines[] = 'Call outcome: ' . $result['call_outcome'];
            $lines[] = 'Recommended next action: ' . $result['next_action'];
        } elseif ($option === 'agent_customer') {
            $lines[] = 'Roles: ' . $result['role_basis'];
            foreach ($result['speakers'] as $s) {
                $lines[] = '';
                $lines[] = strtoupper($s['label']);
                $bullets('Statements', $s['statements']);
                $bullets('Questions', $s['questions']);
                $bullets('Information explained', $s['information_given']);
                $bullets('Requirements', $s['requirements']);
                $bullets('Objections', $s['objections']);
            }
            $lines[] = '';
            $lines[] = 'Customer interest level: ' . $result['customer_interest_level'];
        } else {
            foreach ($result['turns'] as $t) {
                $lines[] = ($t['time'] ? '[' . $t['time'] . '] ' : '') . $t['speaker'] . ': ' . $t['text'];
            }
            if (!$result['turns'] && $result['english'] !== '') $lines[] = $result['english'];
            if ($result['original'] !== '') {
                $lines[] = '';
                $lines[] = 'Original language transcript' . ($result['language'] ? ' (' . $result['language'] . ')' : '') . ':';
                $lines[] = $result['original'];
            }
        }
        return implode("\n", $lines);
    }
}
