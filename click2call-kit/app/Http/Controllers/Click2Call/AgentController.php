<?php

namespace App\Http\Controllers\Click2Call;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Click2Call\VmnService;
use Illuminate\Http\Request;

/**
 * Click2Call users (admin): switch Click2Call on/off per user and set the agent's own phone number
 * (the provider rings it first). LeadGER: Click2CallController::store + User::setClick2Call (with the licensed limit).
 */
class AgentController extends Controller
{
    public function index()
    {
        return view('click2call.agents', [
            'users' => User::orderBy('name')->get(),
            'limit' => (int) config('click2call.max_users'),
            'inUse' => User::where('c2c_channel', true)->count(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'click_agent_number' => ['nullable', 'regex:/^\+?[0-9 ]{10,15}$/'],
            'c2c_default_number' => ['nullable', 'string', 'max:20'],
        ], ['click_agent_number.regex' => 'Enter the agent\'s 10-digit mobile number.']);

        if ($error = $user->setClick2Call((bool) $data['enabled'], $data['click_agent_number'] ?? null)) {
            return back()->with('c2c_error', $error);
        }
        $user->c2c_default_number = !empty($data['c2c_default_number']) ? VmnService::normalize($data['c2c_default_number']) : null;
        $user->save();
        return back()->with('c2c_done', 'Click2Call updated for ' . $user->name . '.');
    }
}
