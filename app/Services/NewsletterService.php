<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendNewsletterJob;
use App\Repositories\NewsletterRepository;

final class NewsletterService
{
    public function __construct(private readonly NewsletterRepository $newsletterRepository) {}

    /** @return 'already_subscribed'|'resubscribed'|'subscribed' */
    public function subscribe(string $email): string
    {
        $subscriber = $this->newsletterRepository->findByEmail($email);

        if ($subscriber?->is_active) {
            return 'already_subscribed';
        }

        if ($subscriber !== null) {
            $subscriber->resubscribe();
            SendNewsletterJob::dispatch($subscriber, 'welcome');

            return 'resubscribed';
        }

        $subscriber = $this->newsletterRepository->createSubscriber($email);
        SendNewsletterJob::dispatch($subscriber, 'welcome');

        return 'subscribed';
    }

    /** @return array{status: 'already_unsubscribed'|'unsubscribed', email: string} */
    public function unsubscribe(string $token): array
    {
        $subscriber = $this->newsletterRepository->findByToken($token);

        if (! $subscriber->is_active) {
            return ['status' => 'already_unsubscribed', 'email' => (string) $subscriber->email];
        }

        $subscriber->unsubscribe();

        return ['status' => 'unsubscribed', 'email' => (string) $subscriber->email];
    }

    public function sendTest(string $email): bool
    {
        if (! $this->newsletterRepository->hasNewsletterTemplate()) {
            return false;
        }

        $subscriber = $this->newsletterRepository->firstOrCreateSubscriber($email);
        SendNewsletterJob::dispatch($subscriber, 'test');

        return true;
    }
}
