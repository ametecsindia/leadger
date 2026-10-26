<?php

namespace Tests\Feature;

use App\Http\Middleware\TelecallerMiddleware;
use App\Models\CompanySarvamSetting;
use App\Models\User;
use App\Services\SarvamCreditService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Licence Info > Sarvam credits: Total / Remaining are not available from the provider (Sarvam has no balance API),
 * Credits Used is LeadGER's tracked usage, Refresh Credits asks Sarvam (free key check) and never fakes success.
 */
class SarvamCreditTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TelecallerMiddleware::class);
        URL::forceRootUrl('http://127.0.0.1');   // "localhost" is a tenants-table company on its own connection
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        Cache::forget('sarvam_credit_check');
        DB::table('transcription_jobs')->delete();   // rolled back after each test
        CompanySarvamSetting::current()->forceFill(['sarvam_api_key' => 'GOOD_sk_1234567890abcdef1234', 'sarvam_key_last4' => '1234',
            'sarvam_key_status' => CompanySarvamSetting::STATUS_CONNECTED])->save();
    }

    private function job(string $status, float $actual, float $estimated, int $seconds = 60): void
    {
        DB::table('transcription_jobs')->insert(['batch_id' => 'trb_' . uniqid(), 'client_id' => 1, 'requested_by' => $this->admin->id,
            'call_id' => random_int(1000, 9999), 'source_code' => 2, 'duration_seconds' => $seconds, 'billable_minutes' => $seconds / 60,
            'provider' => 'sarvam', 'unit_rate' => 0.1, 'estimated_cost' => $estimated, 'actual_cost' => $actual, 'currency' => 'INR',
            'status' => $status, 'billing_status' => 'PENDING', 'completed_at' => $status === 'COMPLETED' ? now() : null,
            'created_at' => now(), 'updated_at' => now()]);
    }

    private function refresh(): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->postJson(route('sarvamCreditsRefresh'));
    }

    public function test_totals_are_never_invented_and_used_is_the_tracked_usage()
    {
        $this->job('COMPLETED', 0.12, 0.10, 72);
        $this->job('COMPLETED', 0.05, 0.05, 30);
        $this->job('PROCESSING', 0, 0.20);
        $this->job('FAILED', 0, 0.07);   // a failed transcript is not charged

        $c = SarvamCreditService::summary();
        $this->assertNull($c['total']);
        $this->assertNull($c['remaining']);
        $this->assertSame('Not available from provider', $c['provider_note']);
        $this->assertEqualsWithDelta(0.17, $c['tracked']['all_time'], 0.0001);
        $this->assertSame([2, 2, 'INR'], [$c['tracked']['transcripts'], $c['tracked']['minutes'], $c['tracked']['currency']]);
        $this->assertEqualsWithDelta(0.20, $c['tracked']['pending_estimate'], 0.0001, 'estimate kept apart');
        $this->assertNull($c['provider'], 'never refreshed');
    }

    public function test_refresh_reports_what_sarvam_answered_and_never_fakes_success()
    {
        Http::fakeSequence()
            ->push(['error' => ['message' => 'job not found']], 404)            // key works
            ->push(['error' => ['message' => 'Insufficient credits']], 402)     // out of credits
            ->pushFailedConnection()                                             // Sarvam unreachable
            ->push(['error' => ['message' => 'Invalid key']], 403);             // key rejected

        $ok = $this->refresh()->assertOk()->json();
        $this->assertTrue($ok['success']);
        $this->assertSame('valid', $ok['credits']['provider']['state']);
        $synced = $ok['credits']['provider']['synced_at'];
        $this->assertNotNull($synced);

        $empty = $this->refresh()->assertOk()->json();
        $this->assertSame('out_of_credits', $empty['credits']['provider']['state']);
        $this->assertStringContainsString('402', $empty['message']);

        $down = $this->refresh()->assertStatus(503)->json();
        $this->assertFalse($down['success'], 'no fake success');
        $this->assertSame('unreachable', $down['credits']['provider']['state']);
        $this->assertSame($synced, $down['credits']['provider']['synced_at'], 'last successful sync kept');

        $this->assertSame('invalid', $this->refresh()->assertStatus(200)->json('credits.provider.state'));
        Http::assertSentCount(4);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/speech-to-text/job/v1/') && $r->method() === 'GET');   // the free check only
    }

    public function test_one_refresh_at_a_time_admin_only_and_no_key()
    {
        Http::fake(['*' => Http::response([], 404)]);
        $lock = Cache::lock('sarvam_credit_refresh', 30);
        $this->assertTrue($lock->get());
        $this->refresh()->assertStatus(429)->assertJson(['success' => false]);
        $lock->release();
        Http::assertNothingSent();

        $tele = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Telecaller'))->firstOrFail();
        $this->actingAs($tele)->postJson(route('sarvamCreditsRefresh'))->assertForbidden();

        CompanySarvamSetting::current()->forceFill(['sarvam_api_key' => null, 'sarvam_key_last4' => null])->save();
        $this->refresh()->assertStatus(422)->assertJson(['message' => 'Add a Sarvam API key first.']);

        $state = $this->actingAs($this->admin)->getJson(route('sarvamKeyShow'))->assertOk()->json();
        $this->assertArrayHasKey('credits', $state);
        $this->assertStringNotContainsString('GOOD_sk_', json_encode($state), 'the key never reaches the page');
    }
}
