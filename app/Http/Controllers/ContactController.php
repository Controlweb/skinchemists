<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    public function show()
    {
        return view('contact', [
            'subjects' => ContactMessage::SUBJECTS,
        ]);
    }

    public function store(ContactRequest $request)
    {
        // Stored first, then emailed. Mail is synchronous on shared hosting
        // and can fail; an enquiry that only ever existed in an SMTP timeout
        // is a customer who thinks they were ignored.
        // except('website'): a browser posts the honeypot as an empty string, and
        // validated() keeps a present-but-empty field. Passing it straight to
        // create() would try to insert a column that does not exist.
        $message = ContactMessage::create($request->safe()->except('website'));

        try {
            $storeEmail = Setting::get('store_email');

            if ($storeEmail) {
                Mail::to($storeEmail)->send(new ContactMessageReceived($message));
            }
        } catch (Throwable $e) {
            Log::error("Contact notification failed for message {$message->id}: ".$e->getMessage());
        }

        return redirect()
            ->route('contact')
            ->with('sent', true);
    }
}
