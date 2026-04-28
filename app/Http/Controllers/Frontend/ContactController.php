<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $settings = $request->attributes->get('settings');

        return Inertia::render('Contact/Index', [
            'settings' => $settings,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10|max:5000',
        ]);

        $contactMessage = ContactMessage::create($validated);

        return redirect()->back()->with('success', 'Pesan berhasil dikirim! Kami akan menghubungi Anda segera.');
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $messages = ContactMessage::latest()->paginate(15);

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
        $contactMessage->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read',
            'data' => new ContactMessageResource($contactMessage),
        ]);
    }

    public function apiDestroy(ContactMessage $contactMessage): JsonResponse
    {
        $contactMessage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully',
        ]);
    }
}
