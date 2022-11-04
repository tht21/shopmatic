<?php

namespace App\Listeners;

use App\Models\UserActivity;
use IlluminateAuthEventsLogin;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LogSuccessfulLogin
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Handle the event.
     *
     * @param  IlluminateAuthEventsLogin  $event
     * @return void
     */
    public function handle($event)
    {
        $user = $event->user;
        if (!is_null($user)) {
            try {
                UserActivity::create([
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'ip_address' => $this->request->getClientIp(),
                    'login_timestamp' => Carbon::now(),
                    'session_id' => session()->getId()
                ]);
            } catch (\Throwable $th) {
                \Log::error("LogSuccessfulLogin Error: " .$th->getMessage());
            }
            $this->request->session()->put('old_session_id', session()->getId());
        }
    }
}
