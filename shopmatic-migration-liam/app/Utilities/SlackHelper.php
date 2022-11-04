<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SlackHelper {
	
	public static function sendMessage($message, $channel = '#general')
    {
        $client = new Client();
        $data = [
            "token" => config('services.slack.token'),
            "channel" => $channel,
            "text" => $message,
            "username" => "Support",
            'post_type' => 'json',
        ];
        $response = $client->request('POST', 'https://slack.com/api/chat.postMessage', ['form_params' => $data]);
        $data = json_decode($response->getBody()->getContents(), true);
        if (empty($data['ok']) || !$data['ok']) {
            set_log_extra('data', $data);
            Log::error('Error with sending message to Slack.');
        }
    }
	
}