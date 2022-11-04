<?php

namespace App\Listeners;

use App\Models\UserActivity;
use IlluminateAuthEventsLogout;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LogSuccessfulLogout
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
     * @param  IlluminateAuthEventsLogout  $event
     * @return void
     */
    public function handle($event)
    {
        $user = $event->user;
        $session_id = $this->request->session()->get('old_session_id');
        if (!empty($session_id) && !empty($user)) {
            $user_activity = UserActivity::where([
                'user_id' => $user->id,
                'session_id' => $session_id
            ])->first();
            if (!is_null($user_activity)) {
                $user_activity->logout_timestamp = Carbon::now();
                $user_activity->save();
            } else {
                \Log::error("Cannot find user user activity with | user_id: " . $user->id . "| session_id: " . $session_id);
            }
            $this->request->session()->forget('old_session_id');
        }
    }
}
