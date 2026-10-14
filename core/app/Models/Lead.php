<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lead extends Model
{
    use HasFactory;

    /**
     * Follow-ups still to do: a scheduled follow_up_on that the lead has not been worked on since.
     * (Working the lead at/after its follow-up time makes it "Done"; see CampaignsController::leadsList.)
     */
    public function scopeOpenFollowups($query)
    {
        return $query->whereNotNull('follow_up_on')->where(function ($q) {
            $q->whereNull('last_actioned_date')->orWhereColumn('last_actioned_date', '<', 'follow_up_on');
        });
    }

    /** Statuses whose "Requires follow-up date" box is ticked in Disposition Settings (statuses.lead_pool). */
    public static function statusRequiresFollowup($statusId): bool
    {
        return !empty($statusId) && (int)Status::where('id', $statusId)->value('lead_pool') === 1;
    }

    /**
     * Schedule (or move) this lead's follow-up. One follow-up per lead: resubmitting updates it, never duplicates.
     * Also keeps one calendar event per lead + agent, as the dialer's quick follow-up already did.
     */
    public function scheduleFollowup(\Carbon\Carbon $at, User $by, ?string $notes = null): void
    {
        $this->follow_up_on = $at->format('Y-m-d H:i:s');
        if (empty($this->assigned_to)) {
            $this->assigned_to = $by->id;   // unassigned lead: the agent who booked it owns the follow-up
        }
        if ($notes !== null && trim($notes) !== '') {
            $this->remarks = trim($notes);
        }
        $this->last_actioned_by = $by->id;
        $this->last_actioned_date = now()->format('Y-m-d H:i:s');
        $this->save();

        $title = "Follow-up: {$this->name} (Lead {$this->id})";
        // also finds events saved before Lead IDs dropped the "#" ("(Lead #123)"), so a lead keeps one event
        $event = Event::where('user_id', $by->id)->where('type', 3)
            ->whereIn('title', [$title, "Follow-up: {$this->name} (Lead #{$this->id})"])->first() ?? new Event();
        $event->user_id = $by->id;
        $event->type = 3;
        $event->title = $title;
        $event->details = $this->remarks;
        $event->start_date = $at;
        $event->end_date = $at->copy()->addMinutes(30);
        $event->created_by = $event->created_by ?: $by->id;
        $event->save();
    }
}
