<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(private readonly ContactService $contactService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Contact/Index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10|max:5000',
        ]);

        $this->contactService->createMessage([
            'name' => (string) $validated['name'],
            'email' => (string) $validated['email'],
            'subject' => (string) $validated['subject'],
            'message' => (string) $validated['message'],
        ]);

        return redirect()->back()->with('success', __('messages.success.contact_sent'));
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $messages = $this->contactService->paginateMessages();

        return response()->json([
            'success' => true,
            'data' => ContactMessageResource::collection($messages),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    public function apiShow(ContactMessage $contactMessage): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new ContactMessageResource($contactMessage),
        ]);
    }

    public function apiMarkAsRead(ContactMessage $contactMessage): JsonResponse
    {
        $contactMessage = $this->contactService->markAsRead($contactMessage);

        return response()->json([
            'success' => true,
            'message' => __('messages.success.message_marked_read'),
            'data' => new ContactMessageResource($contactMessage),
        ]);
    }

    public function apiDestroy(ContactMessage $contactMessage): JsonResponse
    {
        $this->contactService->deleteMessage($contactMessage);

        return response()->json([
            'success' => true,
            'message' => __('messages.success.message_deleted'),
        ]);
    }
}
