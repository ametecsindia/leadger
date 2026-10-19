<?php

namespace App\Services;

use App\Http\Controllers\Dashboard\CampaignsController;
use Illuminate\Support\Facades\DB;

/**
 * Where a customer number of the Inbound Calls report opens for calling back:
 *  - campaign: the number is a lead of an active campaign (not "Manual Dial") that the user may work
 *    (the calling page's own CampaignsController::canOpenCampaignLead rule) -> that lead's calling page;
 *  - choose:   several such leads and no clear favourite -> the agent picks one (or Manual Dial);
 *  - manual:   anything else -> Manual Dial with the number filled in. "hidden" = the number is a lead the user may
 *    not work: the lead is not revealed, the dialer only says "Opened in Manual Dial".
 * Read only. Every URL is built from internal routes.
 */
class InboundCallDestination
{
    private $user;
    private $allowedCampaigns = null;
    private ?int $inboundId;

    /** @param int|null $inboundId the inbound call opened (the calling page falls back to its number) */
    public function __construct($user, ?int $inboundId = null)
    {
        $this->user = $user;
        $this->inboundId = $inboundId;
    }

    /** Decisions for many numbers (one page of the table) with one lead query. @return array number => decision */
    public function forNumbers(array $numbers): array
    {
        $numbers = array_values(array_unique(array_filter(array_map('strval', $numbers))));
        $leads = $this->candidates($numbers);
        $out = [];
        foreach ($numbers as $n) {
            $out[$n] = $this->decide($n, $leads->filter(fn ($l) => $l->phone_normalized === $n || $l->phone2_normalized === $n)->values());
        }
        return $out;
    }

    public function forNumber(string $number): array
    {
        return $this->forNumbers([$number])[$number] ?? $this->manual($number, false);
    }

    /** @return array decision + url: type campaign|choose|manual, title (tooltip), options (choose) */
    public function decide(string $number, $leads): array
    {
        if ($number === '') {
            return $this->manual($number, false);
        }
        $allowed = $leads->filter(fn ($l) => CampaignsController::canOpenCampaignLead($this->user, $l, $l->campaign_id, $this->allowedCampaigns()))->values();
        if ($allowed->isEmpty()) {
            return $this->manual($number, $leads->isNotEmpty());
        }

        $pick = null;
        if ($allowed->count() === 1) {
            $pick = $allowed->first();
        } else {
            // the user's own lead, then the lead worked most recently; a tie (or none worked) -> the agent chooses
            $own = $allowed->filter(fn ($l) => (int)$l->assigned_to === (int)$this->user->id)->values();
            $pool = $own->isNotEmpty() ? $own : $allowed;
            if ($pool->count() === 1) {
                $pick = $pool->first();
            } else {
                $latest = $pool->max(fn ($l) => (string)$l->last_actioned_date);
                $top = $pool->filter(fn ($l) => (string)$l->last_actioned_date === $latest);
                if ($latest !== '' && $top->count() === 1) {
                    $pick = $top->first();
                }
            }
        }
        if ($pick) {
            return ['type' => 'campaign', 'url' => $this->campaignUrl($pick), 'title' => 'Call in campaign: ' . $pick->campaign_name, 'options' => []];
        }

        $options = $pool->sortBy('campaign_name')->map(fn ($l) => ['label' => 'Open in ' . $l->campaign_name, 'url' => $this->campaignUrl($l)])->values()->all();
        $options[] = ['label' => 'Call in Manual Dial', 'url' => self::manualUrl($number)];
        return ['type' => 'choose', 'url' => null, 'title' => 'Choose: ' . $pool->pluck('campaign_name')->sort()->implode(', ') . ' or Manual Dial', 'options' => $options];
    }

    public function campaignUrl($lead): string
    {
        return route('leadCallingView', ['campaignId' => (int)$lead->campaign_id, 'leadId' => (int)$lead->id]) . '?inbound=' . (int)$this->inboundId;
    }

    public static function manualUrl(string $number): string
    {
        return route('dialer', array_filter(['tab' => 'manual', 'number' => $number]));
    }

    private function manual(string $number, bool $hidden): array
    {
        return ['type' => 'manual', 'url' => self::manualUrl($number), 'title' => 'Call in Manual Dial', 'options' => [], 'hidden' => $hidden];
    }

    /** Leads with these numbers (main or second number) in active campaigns other than "Manual Dial". */
    private function candidates(array $numbers)
    {
        if (!$numbers) {
            return collect();
        }
        $tp = DB::getTablePrefix();
        return DB::table('leads')
            ->join('campaigns', 'campaigns.id', '=', 'leads.campaign_id')
            ->where(fn ($q) => $q->whereIn('leads.phone_normalized', $numbers)->orWhereIn('leads.phone2_normalized', $numbers))
            ->where('campaigns.status', '1')
            ->whereDate(DB::raw("DATE({$tp}campaigns.expiry_date)"), '>', date('Y-m-d'))
            ->whereRaw("LOWER(TRIM({$tp}campaigns.name)) <> 'manual dial'")
            ->get(['leads.id', 'leads.campaign_id', 'leads.assigned_to', 'leads.sales_person_id', 'leads.is_public', 'leads.last_actioned_date',
                'leads.phone_normalized', 'leads.phone2_normalized', 'campaigns.name as campaign_name']);
    }

    private function allowedCampaigns()
    {
        return $this->allowedCampaigns ??= CampaignsController::getAllowedCampaignsForUser($this->user);
    }
}
