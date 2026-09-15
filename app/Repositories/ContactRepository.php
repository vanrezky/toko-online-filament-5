<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ContactMessage;
use Illuminate\Pagination\LengthAwarePaginator;

final class ContactRepository
{
    /** @param array{name: string, email: string, subject: string, message: string} $attributes */
    public function create(array $attributes): ContactMessage
    {
        return ContactMessage::query()->create($attributes);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return ContactMessage::query()->latest()->paginate($perPage);
    }

    public function markAsRead(ContactMessage $message): ContactMessage
    {
        $message->markAsRead();

        return $message->refresh();
    }

    public function delete(ContactMessage $message): bool
    {
        return (bool) $message->delete();
    }
}
