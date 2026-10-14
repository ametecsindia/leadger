<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CompanySarvamSetting;
use App\Services\SarvamKeyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Configuration -> Profile -> License Info -> "Transcription · Sarvam AI": this company's own Sarvam API key and
 * transcription settings. Admin only (403 for every other role). The key is never returned: responses carry only
 * the status, the last 4 characters and the settings. Every change is recorded in sarvam_key_audit_logs.
 */
class SarvamKeyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** GET: status, masked key, settings, last verified time and this month's usage. */
    public function show()
    {
        if ($deny = $this->denyUnlessAdmin()) return $deny;
        return response()->json(['success' => true] + self::state(CompanySarvamSetting::current()));
    }

    /** POST: test a typed key (not saved) or, without one, the saved key. */
    public function test(Request $request)
    {
        if ($deny = $this->denyUnlessAdmin()) return $deny;
        $typed = $this->cleanKey($request->input('key'));
        $settings = CompanySarvamSetting::current();

        if ($typed !== null) {
            if ($error = $this->keyFormatError($typed)) {
                return response()->json(['success' => false, 'errors' => ['key' => $error], 'message' => $error], 422);
            }
            $result = SarvamKeyService::test($typed);
            SarvamKeyService::audit('tested', substr($typed, -4), 'Typed key: ' . $result['state']);
            return $this->testResponse($result, $settings);
        }

        $saved = $settings->apiKey();
        if ($saved === null) {
            return response()->json(['success' => false, 'errors' => ['key' => 'Paste a key to test.'], 'message' => 'No key is saved yet.'], 422);
        }
        $result = SarvamKeyService::test($saved);
        // The saved key's status follows the result (temporary problems leave it as it is)
        if ($result['state'] === 'valid') {
            $settings->forceFill(['sarvam_key_status' => CompanySarvamSetting::STATUS_CONNECTED, 'sarvam_key_verified_at' => now()])->save();
            SarvamKeyService::resumeWaiting();
        } elseif ($result['state'] === 'invalid') {
            SarvamKeyService::markInvalid('The key failed "Test key"');
        }
        SarvamKeyService::audit('tested', $settings->sarvam_key_last4, 'Saved key: ' . $result['state']);
        return $this->testResponse($result, $settings->fresh());
    }

    /**
     * POST: save the key (only one that passes the test; when Sarvam cannot be reached it is saved unverified, with
     * a warning) and the settings. The latest save wins (row lock).
     */
    public function save(Request $request)
    {
        if ($deny = $this->denyUnlessAdmin()) return $deny;
        $key = $this->cleanKey($request->input('key'));
        $v = Validator::make($request->all(), [
            'language' => 'required|in:' . implode(',', array_keys(CompanySarvamSetting::LANGUAGES)),
            'scope' => 'required|in:' . implode(',', array_keys(CompanySarvamSetting::SCOPES)),
            'auto_transcribe' => 'required|boolean',
        ], [
            'language.in' => 'Choose a language from the list.',
            'scope.in' => 'Choose which recordings to transcribe.',
        ]);
        $errors = $v->fails() ? array_map(fn ($m) => $m[0], $v->errors()->toArray()) : [];
        if ($key !== null && ($e = $this->keyFormatError($key))) {
            $errors['key'] = $e;
        }
        if ($errors) {
            return response()->json(['success' => false, 'errors' => $errors, 'message' => reset($errors)], 422);
        }

        $warning = null;
        $verified = false;
        if ($key !== null) {
            $result = SarvamKeyService::test($key);
            if ($result['state'] === 'invalid') {
                SarvamKeyService::audit('tested', substr($key, -4), 'Save refused: key invalid');
                return response()->json(['success' => false, 'errors' => ['key' => 'Key is invalid'], 'message' => 'Key is invalid. Nothing was saved.'], 422);
            }
            // Sarvam busy / unreachable: the key could not be checked; saved unverified (a job that is refused
            // marks it invalid and pauses, so a wrong key is never used silently)
            $verified = $result['state'] === 'valid';
            if (!$verified) {
                $warning = 'Saved without verification: ' . $result['message'];
            }
        }

        $settings = DB::transaction(function () use ($request, $key, $verified) {
            $row = CompanySarvamSetting::current();
            $row = CompanySarvamSetting::whereKey($row->id)->lockForUpdate()->first();
            $before = ['lang' => $row->transcription_language, 'scope' => $row->transcription_scope, 'auto' => (bool)$row->auto_transcribe];
            $hadKey = $row->hasKey();
            if ($key !== null) {
                $row->sarvam_api_key = $key;                    // encrypted cast
                $row->sarvam_key_last4 = substr($key, -4);
                $row->sarvam_key_status = CompanySarvamSetting::STATUS_CONNECTED;
                $row->sarvam_key_verified_at = $verified ? now() : null;
            }
            $row->transcription_language = $request->input('language');
            $row->transcription_scope = $request->input('scope');
            $row->auto_transcribe = $request->boolean('auto_transcribe') && $row->hasKey();
            $row->updated_by = Auth::id();
            $row->save();

            if ($key !== null) {
                SarvamKeyService::audit($hadKey ? 'replaced' : 'added', $row->sarvam_key_last4, $verified ? 'Verified' : 'Saved without verification');
            }
            $after = ['lang' => $row->transcription_language, 'scope' => $row->transcription_scope, 'auto' => (bool)$row->auto_transcribe];
            if ($after !== $before) {
                SarvamKeyService::audit('settings_changed', $row->sarvam_key_last4,
                    "language {$after['lang']}, scope {$after['scope']}, auto " . ($after['auto'] ? 'on' : 'off'));
            }
            return $row;
        });

        $resumed = $key !== null ? SarvamKeyService::resumeWaiting() : 0;
        $message = $key !== null ? ($verified ? 'Key verified and saved' : 'Saved without verification') : 'Settings saved';
        return response()->json(['success' => true, 'message' => $message, 'warning' => $warning, 'resumed' => $resumed]
            + self::state($settings->fresh()));
    }

    /** DELETE: remove the key (transcription stops; automatic transcription off). */
    public function destroy()
    {
        if ($deny = $this->denyUnlessAdmin()) return $deny;
        $settings = DB::transaction(function () {
            $row = CompanySarvamSetting::current();
            $row = CompanySarvamSetting::whereKey($row->id)->lockForUpdate()->first();
            $last4 = $row->sarvam_key_last4;
            $row->forceFill([
                'sarvam_api_key' => null, 'sarvam_key_last4' => null, 'sarvam_key_verified_at' => null,
                'sarvam_key_status' => CompanySarvamSetting::STATUS_NOT_CONFIGURED, 'auto_transcribe' => false,
                'updated_by' => Auth::id(),
            ])->save();
            SarvamKeyService::audit('removed', $last4, 'Key removed; transcription stopped');
            return $row;
        });
        SarvamKeyService::pauseQueued();
        return response()->json(['success' => true, 'message' => 'Sarvam key removed. Transcription has stopped for this company.'] + self::state($settings->fresh()));
    }

    /* ------------------------------------------------------------------ */

    /** What the card shows. Never the key. */
    public static function state(CompanySarvamSetting $s): array
    {
        return [
            'status' => $s->hasKey() ? $s->sarvam_key_status : CompanySarvamSetting::STATUS_NOT_CONFIGURED,
            'masked_key' => $s->maskedKey(),
            'last4' => $s->sarvam_key_last4,
            'language' => $s->transcription_language,
            'scope' => $s->transcription_scope,
            'auto_transcribe' => (bool)$s->auto_transcribe,
            'verified_at' => $s->sarvam_key_verified_at ? $s->sarvam_key_verified_at->format('d M Y, h:i A') : null,
            'usage' => SarvamKeyService::usage(),
        ];
    }

    private function testResponse(array $result, CompanySarvamSetting $settings)
    {
        $ok = $result['state'] === 'valid';
        $code = $ok ? 200 : ($result['state'] === 'invalid' ? 422 : 503);
        return response()->json([
            'success' => $ok,
            'result' => $result['state'],
            'message' => $result['message'],
            'errors' => $result['state'] === 'invalid' ? ['key' => 'Key is invalid'] : null,
        ] + self::state($settings), $code);
    }

    private function denyUnlessAdmin()
    {
        if (optional(optional(Auth::user())->permissionsGroup)->name !== 'Admin') {
            return response()->json(['success' => false, 'message' => 'Only an admin can manage the Sarvam API key.'], 403);
        }
        return null;
    }

    /** The pasted key without surrounding spaces / line breaks; null when nothing was typed. */
    private function cleanKey($value): ?string
    {
        if (!is_string($value)) return null;
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    private function keyFormatError(string $key): ?string
    {
        if (preg_match('/\s/', $key)) return 'The key cannot contain spaces.';
        $len = strlen($key);
        if ($len < 20 || $len > 200) return 'The key must be 20 to 200 characters long.';
        return null;
    }
}
