<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'photo',
        'permissions_id',
        'status',
        'permissions',
        'connect_email',
        'connect_password',
        'provider_id',
        'provider',
        'access_token'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function getProfilePhotoUrlAttribute()
    {
        return $this->photo ? asset('uploads/users/' . $this->photo) : asset('assets/dashboard/images/avatar.jpg');
    }

    // relation with Permissions
    public function permissionsGroup()
    {
        return $this->belongsTo('App\Models\Permissions', 'permissions_id');
    }
    public function click2callLogs()
    {
        return $this->hasMany(Click2CallLog::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_user', 'user_id', 'team_id')->withTimestamps();
    }

    /**
     * Click2Call is configured per user in users.c2c_channel (on/off) and users.click_agent_number
     * (the agent's own phone, which the provider rings first). Staff create/edit, the Click2Call page,
     * the Call Channels page, the Calling Workspace and the call endpoints all use these methods.
     */
    public function click2CallAgentNumber(): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', (string)($this->click_agent_number ?: $this->mobile));
        return $digits !== '' ? $digits : null;
    }

    public function canClick2Call(): bool
    {
        return (int)$this->c2c_channel === 1 && $this->click2CallAgentNumber() !== null;
    }

    /**
     * Turn Click2Call on (with the agent number) or off. Enforces the licensed Click2Call user limit.
     * Does not save; returns an error message, or null on success.
     */
    public function setClick2Call(bool $enabled, ?string $agentNumber = null): ?string
    {
        if (!$enabled) {
            $this->c2c_channel = 0;
            return null;
        }

        $number = preg_replace('/[^0-9]/', '', (string)($agentNumber ?: $this->click_agent_number ?: $this->mobile));
        if ($number === '') {
            return 'Click2Call needs the agent\'s mobile number. Add a mobile number for this user.';
        }

        if ((int)$this->c2c_channel !== 1) {
            $limit = \App\Helpers\Helper::licensedClick2CallUsers();
            $inUse = static::where('c2c_channel', 1)->when($this->exists, fn ($q) => $q->where('id', '!=', $this->id))->count();
            if ($limit > 0 && $inUse + 1 > $limit) {
                return "Click2Call user limit reached ({$limit} licensed). Disable Click2Call for another user first.";
            }
        }

        $this->c2c_channel = 1;
        $this->click_agent_number = $number;
        return null;
    }

    /**
     * Turn WebRTC (users.webrtc_channel) on or off. Enforces the licensed WebRTC channel limit, as the WebRTC page does.
     * Independent of Click2Call: a user may have both. Does not save; returns an error message, or null on success.
     */
    public function setWebRTC(bool $enabled): ?string
    {
        if (!$enabled) {
            $this->webrtc_channel = 0;
            return null;
        }

        if ((int)$this->webrtc_channel !== 1) {
            $limit = \App\Helpers\Helper::licensedWebRTCUsers();
            $inUse = static::where('webrtc_channel', 1)->when($this->exists, fn ($q) => $q->where('id', '!=', $this->id))->count();
            if ($limit > 0 && $inUse + 1 > $limit) {
                return "WebRTC channel limit reached ({$limit} licensed). Disable WebRTC for another user first.";
            }
        }

        $this->webrtc_channel = 1;
        return null;
    }

}
