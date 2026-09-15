<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ContactMessage;
use App\Repositories\ContactRepository;
use Illuminate\Pagination\LengthAwarePaginator;

final class ContactService
{
    public function __construct(private readonly ContactRepository $contactRepository) {}

    /** @param array{name: string, email: string, subject: string, message: string} $attributes */
    public function createMessage(array $attributes): ContactMessage
    {
        return $this->contactRepository->create($attributes);
    }

    public function paginateMessages(int $perPage = 15): LengthAwarePaginator
    {
        return $this->contactRepository->paginate($perPage);
    }

    public function markAsRead(ContactMessage $message): ContactMessage
    {
        return $this->contactRepository->markAsRead($message);
    }

    public function deleteMessage(ContactMessage $message): bool
    {
        return $this->contactRepository->delete($message);
    }
}
