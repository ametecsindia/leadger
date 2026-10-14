<?php

namespace Tests\Feature;

use App\Helpers\Helper;
use App\Http\Controllers\Dashboard\LeadsController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/** Lead IDs: digits only, clickable (shared popup), and the popup endpoint never resolves a non-digit id. */
class LeadIdDisplayTest extends TestCase
{
    public function test_lead_id_link_is_digits_only_and_safe()
    {
        $html = Helper::leadIdLink(24000043);
        $this->assertStringContainsString('data-lead-id="24000043"', $html);
        $this->assertStringContainsString('>24000043</a>', $html);
        $this->assertStringNotContainsString('#', strip_tags($html));
        $this->assertStringNotContainsString('LD', $html);
        $this->assertSame('&lt;b&gt;x&lt;/b&gt;', Helper::leadIdLink('<b>x</b>'), 'not a lead id: escaped text, no link');
        $this->assertSame('', Helper::leadIdLink(null));
    }

    public function test_popup_endpoint_rejects_ids_that_are_not_plain_digits()
    {
        Auth::login(User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail());
        $req = Request::create('/x', 'GET', [], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        app()->instance('request', $req);
        // "3000001abc" would be cast by MySQL to lead 3000001 - it must be "not found" instead
        $res = app(LeadsController::class)->view('3000001abc');
        $this->assertSame(404, $res->getStatusCode());
    }
}
