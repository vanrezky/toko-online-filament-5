<?php

namespace App\Jobs;

use App\Models\EmailTemplate;
use App\Models\NewsletterSubscriber;
use App\Services\EmailTemplateService;
use App\Settings\GeneralSettings;
use App\Enums\EmailTemplateCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNewsletterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public NewsletterSubscriber $subscriber,
        public string $type = 'welcome'
    ) {}

    public function handle(EmailTemplateService $emailService): void
    {
        if (! $this->subscriber->is_active) {
            return;
        }

        $template = EmailTemplate::getByCode(EmailTemplateCode::NEWSLETTER->value);

        if (! $template) {
            return;
        }

        $generalSettings = app(GeneralSettings::class);
        $websiteName = $generalSettings?->site_name ?? config('app.name');
        $logoUrl = $generalSettings?->getLogo() ?? asset('images/logo.png');

        $unsubscribeUrl = route('frontend.newsletter.unsubscribe', ['token' => $this->subscriber->token]);

        $placeholders = [
            'subscriber_email' => $this->subscriber->email,
            'website_name' => $websiteName,
            'logo_url' => $logoUrl,
            'unsubscribe_url' => $unsubscribeUrl,
            'current_year' => now()->year,
        ];

        if ($this->type === 'welcome') {
            $placeholders['newsletter_title'] = 'Selamat Datang di Newsletter ' . $websiteName;
            $placeholders['newsletter_content'] = $this->getWelcomeContent($websiteName, $unsubscribeUrl);
        } elseif ($this->type === 'test') {
            $placeholders['newsletter_title'] = 'Test Newsletter - ' . $websiteName;
            $placeholders['newsletter_content'] = $this->getTestContent($websiteName, $unsubscribeUrl);
        } else {
            $placeholders['newsletter_title'] = 'Newsletter ' . $websiteName;
            $placeholders['newsletter_content'] = $this->getDefaultContent($websiteName, $unsubscribeUrl);
        }

        $emailService->send(EmailTemplateCode::NEWSLETTER->value, $this->subscriber->email, $placeholders, true, 'default');
    }

    private function getWelcomeContent(string $websiteName, string $unsubscribeUrl): string
    {
        return '<p class="email-greeting">Halo <strong>{{subscriber_email}}</strong>,</p>
            <p class="email-paragraph">Selamat datang di newsletter <strong>' . $websiteName . '</strong>! Terima kasih telah berlangganan.</p>
            <div class="email-success">
                <p class="text-center">Anda sekarang akan menerima update terbaru tentang:</p>
                <ul style="margin: 16px 0; padding-left: 24px; color: #065F46;">
                    <li>Produk baru dan koleksi terbaru</li>
                    <li>Promo dan diskon eksklusif</li>
                    <li>Tips dan informasi menarik</li>
                </ul>
            </div>
            <p class="text-center mt-6">Stay tuned untuk update menarik dari kami!</p>
            <p class="text-muted text-sm mt-6 text-center">Jika Anda tidak ingin menerima email ini lagi, Anda dapat <a href="' . $unsubscribeUrl . '" style="color: #4F46E5;">unsubscribe di sini</a>.</p>';
    }

    private function getTestContent(string $websiteName, string $unsubscribeUrl): string
    {
        return '<p class="email-greeting">Halo <strong>{{subscriber_email}}</strong>,</p>
            <p class="email-paragraph">Ini adalah email test dari newsletter <strong>' . $websiteName . '</strong>.</p>
            <div class="email-box">
                <p><strong>Tujuan:</strong> Memastikan sistem newsletter berfungsi dengan baik</p>
                <p><strong>Waktu Kirim:</strong> ' . now()->format('d M Y, H:i') . '</p>
                <p><strong>Status:</strong> <span style="color: #059669;">Berhasil</span></p>
            </div>
            <p class="text-center mt-6">Jika Anda menerima email ini, berarti sistem newsletter sudah berjalan dengan baik!</p>
            <p class="text-muted text-sm mt-6 text-center">Jika Anda tidak ingin menerima email ini lagi, Anda dapat <a href="' . $unsubscribeUrl . '" style="color: #4F46E5;">unsubscribe di sini</a>.</p>';
    }

    private function getDefaultContent(string $websiteName, string $unsubscribeUrl): string
    {
        return '<p class="email-greeting">Halo <strong>{{subscriber_email}}</strong>,</p>
            <p class="email-paragraph">Berikut adalah newsletter terbaru dari <strong>' . $websiteName . '</strong>.</p>
            <p class="text-muted text-sm mt-6 text-center">Jika Anda tidak ingin menerima email ini lagi, Anda dapat <a href="' . $unsubscribeUrl . '" style="color: #4F46E5;">unsubscribe di sini</a>.</p>';
    }
}
