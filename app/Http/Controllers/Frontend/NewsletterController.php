<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\NewsletterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewsletterController extends Controller
{
    public function __construct(private readonly NewsletterService $newsletterService) {}

    public function subscribe(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        return match ($this->newsletterService->subscribe((string) $validated['email'])) {
            'already_subscribed' => redirect()->back()->with('info', __('messages.info.already_subscribed')),
            'resubscribed' => redirect()->back()->with('success', __('messages.success.resubscribed')),
            default => redirect()->back()->with('success', __('messages.success.subscribed')),
        };
    }

    public function unsubscribe(Request $request, string $token): Response
    {
        $result = $this->newsletterService->unsubscribe($token);

        return Inertia::render('Newsletter/Unsubscribe', [
            'status' => $result['status'],
            'email' => $result['email'],
        ]);
    }

    public function sendTest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        if (! $this->newsletterService->sendTest((string) $validated['email'])) {
            return redirect()->back()->with('error', __('messages.error.newsletter_template_not_found'));
        }

        return redirect()->back()->with('success', __('messages.success.test_newsletter_sent', ['email' => $validated['email']]));
    }
}
