<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Jobs\SendNewsletterJob;
use App\Models\EmailTemplate;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $existingSubscriber = NewsletterSubscriber::where('email', $validated['email'])->first();

        if ($existingSubscriber) {
            if ($existingSubscriber->is_active) {
                return redirect()->back()->with('info', __('messages.info.already_subscribed'));
            }

            $existingSubscriber->resubscribe();

            // Send welcome newsletter via queue
            SendNewsletterJob::dispatch($existingSubscriber, 'welcome');

            return redirect()->back()->with('success', __('messages.success.resubscribed'));
        }

        $subscriber = NewsletterSubscriber::create([
            'email' => $validated['email'],
        ]);

        // Send welcome/test newsletter via queue
        SendNewsletterJob::dispatch($subscriber, 'welcome');

        return redirect()->back()->with('success', __('messages.success.subscribed'));
    }

    public function unsubscribe(Request $request, string $token)
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->firstOrFail();

        if (! $subscriber->is_active) {
            return Inertia::render('Newsletter/Unsubscribe', [
                'status' => 'already_unsubscribed',
                'email' => $subscriber->email,
            ]);
        }

        $subscriber->unsubscribe();

        return Inertia::render('Newsletter/Unsubscribe', [
            'status' => 'unsubscribed',
            'email' => $subscriber->email,
        ]);
    }

    public function sendTest(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $template = EmailTemplate::getByCode('newsletter');

        if (! $template) {
            return redirect()->back()->with('error', __('messages.error.newsletter_template_not_found'));
        }

        $subscriber = NewsletterSubscriber::firstOrCreate(
            ['email' => $validated['email']],
            ['subscribed_at' => now()]
        );

        SendNewsletterJob::dispatch($subscriber, 'test');

        return redirect()->back()->with('success', __('messages.success.test_newsletter_sent', ['email' => $validated['email']]));
    }
}