<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class HomeController extends Controller
{

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('welcome');
    }

    /**
     * Show the about us page
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function aboutUs()
    {
        return view('about-us');
    }

    /**
     * Show the pricing page
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function pricing()
    {
        return view('pricing');
    }

    /**
     * Show the contact us page
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function contact()
    {
        return view('contact');
    }

    /**
     * Sends the email for contact us
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function contactSend(Request $request)
    {
        $data = $request->input();
        Mail::send('emails.contact', $data, function($message) use ($data) {
            $message->to('sales@combinesell.com', 'CombineSell')->subject($data['subject'] ?? 'Contact Us Form');
            $message->from($data['email'], $data['name']);
        });
        return response()->json(['message' => 'Successfully sent the message!']);
    }

    /**
     * Sends the email for contact us
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function endToEndPost(Request $request)
    {
        $data = $request->input();
        Mail::send('emails.endtoend', $data, function($message) use ($data) {
            $message->to('sales@combinesell.com', 'CombineSell')->subject('Request Consultation');
            $message->from($data['work_email'], $data['first_name'] . ' ' . $data['last_name']);
        });
        return response()->json(['message' => 'Successfully sent the message!']);
    }

    /**
     * Show the integrations page
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function integrations()
    {
        return view('integrations');
    }

    /**
     * Show the privacy policy page
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function privacy()
    {
        return view('privacy');
    }

    /**
     * Show the terms of service page
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function terms()
    {
        return view('terms');
    }

    /**
     * Show the enterprise page
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function enterprise()
    {
        return view('enterprise');
    }

    /**
     * Show the end to end page
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function endToEnd()
    {
        return view('end-to-end');
    }
}
