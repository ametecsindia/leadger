<?php

namespace App\Models\Concerns;

/**
 * Add to your User model:  use \App\Models\Concerns\HasClick2Call;
 * Same rules as LeadGER's User::click2CallAgentNumber / canClick2Call / setClick2Call.
 *
 * ADAPT: c2cTeamIds() and isC2cAdmin() to your project's teams and roles.
 */
trait HasClick2Call
{
    /** The agent's own phone the provider rings first: click_agent_number, else mobile (digits only). */
    public function click2CallAgentNumber(): ?string
    {
        $digits = preg_replace('/\D/', '', (string) ($this->click_agent_number ?: ($this->mobile ?? '')));
        return $digits !== '' ? $digits : null;
    }

    /** Click2Call switched on for the user and an agent number exists. */
    public function canClick2Call(): bool
    {
        return (bool) $this->c2c_channel && $this->click2CallAgentNumber() !== null;
    }

    /**
     * Turn Click2Call on (with the agent number) or off. Enforces config('click2call.max_users').
     * Does not save; returns an error message, or null on success.
     */
    public function setClick2Call(bool $enabled, ?string $agentNumber = null): ?string
    {
        if (!$enabled) {
            $this->c2c_channel = false;
            return null;
        }
        $number = preg_replace('/\D/', '', (string) ($agentNumber ?: $this->click_agent_number ?: ($this->mobile ?? '')));
        if ($number === '') {
            return "Click2Call needs the agent's mobile number. Add a mobile number for this user.";
        }
        $limit = (int) config('click2call.max_users');
        if (!$this->c2c_channel && $limit > 0) {
            $inUse = static::where('c2c_channel', true)->when($this->exists, fn ($q) => $q->whereKeyNot($this->getKey()))->count();
            if ($inUse + 1 > $limit) {
                return "Click2Call user limit reached ({$limit} licensed). Disable Click2Call for another user first.";
            }
        }
        $this->c2c_channel = true;
        $this->click_agent_number = $number;
        return null;
    }

    /** Teams of the user (VMNs are assigned to teams). ADAPT. Default: a team_id column (single id or "1,8"). */
    public function c2cTeamIds(): array
    {
        $raw = $this->team_id ?? null;
        return array_values(array_filter(array_map('intval', is_array($raw) ? $raw : explode(',', (string) $raw))));
    }

    /** Admins use every VMN and manage vouchers. ADAPT. Default: an is_admin column. */
    public function isC2cAdmin(): bool
    {
        return (bool) ($this->is_admin ?? false);
    }
}
