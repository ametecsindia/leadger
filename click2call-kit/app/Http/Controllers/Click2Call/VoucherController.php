<?php

namespace App\Http\Controllers\Click2Call;

use App\Http\Controllers\Controller;
use App\Models\C2c\Vmn;
use App\Models\C2c\Voucher;
use App\Services\Click2Call\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Add Voucher page (admin): add a voucher, see its numbers, assign numbers to teams, set their usage, delete.
 * LeadGER: ObdController::did / did_add / did_update / did_bulk_update / did_delete (Configuration -> Add Voucher).
 */
class VoucherController extends Controller
{
    public function index()
    {
        $teamModel = config('click2call.team_model');
        $teams = class_exists($teamModel) ? $teamModel::query()->orderBy('name')->get(['id', 'name']) : collect();

        return view('click2call.vouchers', [
            'vmns' => Vmn::orderBy('virtual_number')->get(),
            'teams' => $teams,
            'vouchers' => Voucher::latest('id')->limit(10)->get(),
            'smartGroups' => DB::table('c2c_smart31_numbers')
                ->select('group_name', DB::raw('COUNT(DISTINCT vmn) as numbers'), DB::raw('MAX(usage_date) as last_usage'))
                ->groupBy('group_name')->orderBy('group_name')->get(),
        ]);
    }

    /** Add a voucher: validated with the license server, then its numbers replace the current ones. */
    public function add(Request $request, VoucherService $vouchers)
    {
        $request->validate(['voucher_code' => ['required', 'string', 'max:100']], ['voucher_code.required' => 'Enter the voucher code.']);
        $result = $vouchers->add($request->input('voucher_code'), (int) $request->user()->getKey(), (string) $request->ip());

        return back()->with($result['ok'] ? 'c2c_done' : 'c2c_error', $result['message']);
    }

    /** One number: its teams and usage (click2call / obd / both / webrtc). */
    public function update(Request $request, Vmn $vmn)
    {
        $data = $request->validate([
            'team_ids' => ['nullable', 'array'], 'team_ids.*' => ['integer'],
            'usage' => ['nullable', 'in:click2call,obd,both,webrtc'],
        ]);
        $vmn->update(['team_ids' => array_values(array_map('intval', $data['team_ids'] ?? []))] + $this->usage($data['usage'] ?? null));
        return back()->with('c2c_done', 'Number +91' . $vmn->virtual_number . ' updated.');
    }

    /** Several numbers at once: teams / usage, or delete. */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer'],
            'action' => ['required', 'in:update,delete'],
            'team_ids' => ['nullable', 'array'], 'team_ids.*' => ['integer'],
            'usage' => ['nullable', 'in:click2call,obd,both,webrtc'],
        ], ['ids.required' => 'No numbers selected for bulk update.']);

        if ($data['action'] === 'delete') {
            Vmn::whereIn('id', $data['ids'])->delete();
            return back()->with('c2c_done', 'Selected numbers deleted.');
        }
        $changes = $this->usage($data['usage'] ?? null);
        if (!empty($data['team_ids'])) {
            $changes['team_ids'] = array_values(array_map('intval', $data['team_ids']));
        }
        Vmn::whereIn('id', $data['ids'])->get()->each(fn (Vmn $v) => $v->update($changes));
        return back()->with('c2c_done', 'Selected numbers updated.');
    }

    public function destroy(Vmn $vmn)
    {
        $vmn->delete();
        return back()->with('c2c_done', 'Number deleted.');
    }

    /** LeadGER usage values -> flags (webrtc only switches WebRTC on). */
    private function usage(?string $usage): array
    {
        return match ($usage) {
            'click2call' => ['click2call' => true, 'obd' => false],
            'obd' => ['click2call' => false, 'obd' => true],
            'both' => ['click2call' => true, 'obd' => true],
            'webrtc' => ['webrtc' => true],
            default => [],
        };
    }
}
