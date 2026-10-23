<?php

namespace Tests\Feature;

use App\Http\Middleware\TelecallerMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/** People > Call Channels > WebRTC: no Agent Number (WebRTC calls do not use it); Click2Call keeps it. */
class WebRTCChannelsPageTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TelecallerMiddleware::class);
        \Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1');
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
    }

    private function list(array $extra = []): array
    {
        return $this->actingAs($this->admin)->postJson(route('webrtcData'), $extra + [
            'draw' => 1, 'start' => 0, 'length' => 50,
            'columns' => [['data' => 'name'], ['data' => 'role'], ['data' => 'channel'], ['data' => 'options']],
            'order' => [['column' => 0, 'dir' => 'asc']],
        ])->assertOk()->json();
    }

    public function test_the_page_and_the_table_have_no_agent_number_column()
    {
        $this->actingAs($this->admin)->get(route('webrtc'))->assertOk()
            ->assertDontSee('Agent Mobile Number')->assertSee('Search user', false);

        $user = User::where('id', '!=', $this->admin->id)->firstOrFail();
        $user->forceFill(['webrtc_channel' => 1, 'click_agent_number' => '9876501234'])->save();
        $row = collect($this->list()['data'])->first(fn ($r) => str_contains($r['name'], e($user->name)));
        $this->assertNotNull($row);
        $this->assertSame(['name', 'role', 'channel', 'options'], array_keys(array_intersect_key($row, array_flip(['name', 'role', 'channel', 'options', 'click_agent_number']))));
    }

    public function test_search_and_sort_from_the_browser_cannot_change_the_query()
    {
        $res = $this->list(['find_q' => "x' OR 1=1 -- ", 'order' => [['column' => 0, 'dir' => 'asc; DROP TABLE x']],
            'columns' => [['data' => 'name, (select sleep(5))']]]);
        $this->assertSame(0, (int)$res['recordsTotal'], 'the quote is searched for literally');

        $user = User::where('id', '!=', $this->admin->id)->firstOrFail();
        $user->forceFill(['webrtc_channel' => 1, 'click_agent_number' => '9876501234'])->save();
        $this->assertGreaterThanOrEqual(1, (int)$this->list(['find_q' => '98765012'])['recordsTotal'], 'a number still finds its user');
    }

    public function test_saving_webrtc_without_an_agent_number_keeps_the_users_saved_number()
    {
        $user = User::where('id', '!=', $this->admin->id)->firstOrFail();
        $user->forceFill(['webrtc_channel' => 1, 'c2c_channel' => 0, 'click_agent_number' => '9876501234'])->save();

        $this->actingAs($this->admin)->postJson(route('webrtcStore'), ['user' => $user->id])
            ->assertOk()->assertJsonPath('status', 'success');
        $this->assertSame('9876501234', $user->fresh()->click_agent_number, 'Click2Call and inbound matching still use it');
        $this->assertSame(1, (int)$user->fresh()->webrtc_channel);
    }

    public function test_click2call_still_requires_its_agent_number()
    {
        $user = User::where('id', '!=', $this->admin->id)->firstOrFail();
        $this->actingAs($this->admin)->postJson(route('click2CallStore'), ['user' => $user->id])
            ->assertStatus(422)->assertJsonValidationErrors('click_agent_number');
    }
}
