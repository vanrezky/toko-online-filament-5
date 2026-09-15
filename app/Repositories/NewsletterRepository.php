<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\EmailTemplateCode;
use App\Models\EmailTemplate;
use App\Models\NewsletterSubscriber;

final class NewsletterRepository
{
    public function findByEmail(string $email): ?NewsletterSubscriber
    {
        return NewsletterSubscriber::query()->where('email', $email)->first();
    }

    public function createSubscriber(string $email): NewsletterSubscriber
    {
        return NewsletterSubscriber::query()->create(['email' => $email]);
    }

    public function findByToken(string $token): NewsletterSubscriber
    {
        return NewsletterSubscriber::query()->where('token', $token)->firstOrFail();
    }

    public function hasNewsletterTemplate(): bool
    {
        return EmailTemplate::getByCode(EmailTemplateCode::NEWSLETTER->value) !== null;
    }

    public function firstOrCreateSubscriber(string $email): NewsletterSubscriber
    {
        return NewsletterSubscriber::query()->firstOrCreate(
            ['email' => $email],
            ['subscribed_at' => now()],
        );
    }
}
