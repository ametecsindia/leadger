<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\StatusesController;
use App\Models\Lead;
use App\Models\Status;
use App\Models\Substatus;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/** Add Status / Edit Status popup: Status -> Substatus -> Description, saved through StatusesController. */
class StatusPopupTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Auth::login(User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail());
    }

    private function send(string $method, array $data, $id = null): array
    {
        $req = Request::create('/x', 'POST', $data);
        $req->headers->set('Accept', 'application/json');
        $req->headers->set('X-Requested-With', 'XMLHttpRequest');
        $req->setUserResolver(fn () => Auth::user());
        app()->instance('request', $req);
        $res = $method === 'store' ? app(StatusesController::class)->store($req) : app(StatusesController::class)->update($req, $id);
        return [$res->getStatusCode(), json_decode($res->getContent(), true)];
    }

    public function test_add_status_saves_status_substatuses_description_and_next_order()
    {
        $nextOrder = (int)Status::max('seq_order') + 1;
        [$code, $res] = $this->send('store', ['name' => 'Popup Test Status', 'description' => 'Shown in popup',
            'substatuses' => ['First reason', 'Second reason', 'first REASON', '']]);

        $this->assertSame(200, $code);
        $this->assertTrue($res['success']);
        $s = Status::where('name', 'Popup Test Status')->firstOrFail();
        $this->assertSame('Shown in popup', $s->description);
        $this->assertSame($nextOrder, (int)$s->seq_order);
        $this->assertSame(['First reason', 'Second reason'], $s->substatuses()->pluck('name')->all(), 'case-insensitive duplicates and blanks skipped');
        $this->assertSame(['First reason', 'Second reason'], collect($res['status']['substatuses'])->pluck('name')->all());
    }

    public function test_validation_and_no_duplicate_status()
    {
        [$code, $res] = $this->send('store', ['name' => '  ']);
        $this->assertSame(422, $code);
        $this->assertArrayHasKey('name', $res['errors']);

        $existing = Status::firstOrFail();
        $count = Status::count();
        [$code, $res] = $this->send('store', ['name' => $existing->name]);
        $this->assertSame(422, $code);
        $this->assertSame('A status with this name already exists.', $res['errors']['name'][0]);
        $this->assertSame($count, Status::count());
    }

    public function test_edit_updates_the_same_record_adds_and_removes_substatuses()
    {
        $this->send('store', ['name' => 'Popup Edit Status', 'substatuses' => ['Keep me', 'Remove me']]);
        $s = Status::where('name', 'Popup Edit Status')->firstOrFail();
        $order = $s->seq_order;
        $lead = $s->lead_pool;
        $remove = $s->substatuses()->where('name', 'Remove me')->value('id');
        $count = Status::count();

        [$code, $res] = $this->send('update', ['name' => 'Popup Edit Status Renamed', 'description' => 'New text',
            'substatuses' => ['Added later', 'keep me'], 'remove_substatus_ids' => [$remove]], $s->id);

        $this->assertSame(200, $code, json_encode($res));
        $this->assertSame($count, Status::count(), 'edit does not create a status');
        $s->refresh();
        $this->assertSame('Popup Edit Status Renamed', $s->name);
        $this->assertSame('New text', $s->description);
        $this->assertSame($order, $s->seq_order, 'order unchanged');
        $this->assertSame($lead, $s->lead_pool, 'lead pool flag not reset by the popup');
        $this->assertSame(['Keep me', 'Added later'], $s->substatuses()->pluck('name')->all());
        $this->assertNull(Substatus::find($remove));
    }

    public function test_a_substatus_used_by_a_lead_is_kept()
    {
        $this->send('store', ['name' => 'Popup Used Status', 'substatuses' => ['In use']]);
        $s = Status::where('name', 'Popup Used Status')->firstOrFail();
        $sub = $s->substatuses()->firstOrFail();
        Lead::forceCreate(['name' => 'Popup Lead', 'phonenumber' => '9' . random_int(100000000, 999999999),
            'status' => $s->id, 'substatus' => $sub->id, 'created_by' => Auth::id()]);

        [$code, $res] = $this->send('update', ['name' => $s->name, 'remove_substatus_ids' => [$sub->id]], $s->id);
        $this->assertSame(200, $code);
        $this->assertNotNull(Substatus::find($sub->id), 'still referenced: not deleted');
        $this->assertStringContainsString('was kept', $res['message']);
    }

    public function test_old_add_page_url_opens_the_popup()
    {
        $res = app(StatusesController::class)->create();
        $this->assertStringContainsString('statuses?add=1', $res->getTargetUrl());
    }
}
