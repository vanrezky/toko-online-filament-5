# Email Template Skill

## Overview

Project ini menggunakan sistem email template yang dinamis berbasis database melalui model `App\Models\EmailTemplate`. Semua email yang dikirim (termasuk newsletter, notifikasi order, reset password, dll) harus menggunakan sistem ini untuk konsistensi brand dan kemudahan pengelolaan.

## EmailTemplate Model

### Fields
- `code` (string, unique) - Identifier unik template, e.g., `newsletter`, `payment_success`
- `name` (string) - Nama display template
- `subject` (string) - Subjek email, support placeholder `{{placeholder}}`
- `header_title` (string, nullable) - Judul di header email
- `header_gradient` (string, nullable) - Warna gradient header, format: `#4F46E5 0%, #7C3AED 100%`
- `body` (text) - Konten email HTML, support placeholder `{{placeholder}}`
- `placeholders` (json) - Array nama placeholder yang tersedia
- `is_active` (boolean) - Status aktif template
- `is_default` (boolean) - Template bawaan sistem
- `send_to_admin` (boolean) - Kirim copy ke admin

### Default Gradients
```php
EmailTemplate::$defaultGradients = [
    'reset_password' => '#4F46E5 0%, #7C3AED 100%',
    'payment_request' => '#4F46E5 0%, #7C3AED 100%',
    'payment_success' => '#059669 0%, #10B981 100%',
    'order_expiry_reminder' => '#F59E0B 0%, #D97706 100%',
    'order_expiry' => '#DC2626 0%, #EF4444 100%',
    'order_status_changed' => '#4F46E5 0%, #7C3AED 100%',
];
```

### Key Methods
- `EmailTemplate::getByCode(string $code)` - Ambil template aktif by code
- `$template->renderSubject(array $placeholders)` - Render subject dengan placeholder
- `$template->renderBody(array $placeholders)` - Render body dengan layout email

## Email Layout

Template email menggunakan layout di `resources/views/emails/layout.blade.php` yang menyediakan:
- Header dengan gradient dan logo
- Content area
- Footer dengan nama website

## EmailTemplateService

Service utama untuk mengirim email: `App\Services\EmailTemplateService`

### Methods

#### `send(string $code, string $email, array $placeholders = [], bool $queue = true, string $queuePriority = 'default'): ?EmailLog`
Mengirim email menggunakan template by code.

```php
$emailService->send('newsletter', 'user@example.com', [
    'subscriber_email' => 'user@example.com',
    'website_name' => 'Toko Online',
    'unsubscribe_url' => 'https://...',
], true, 'default');
```

#### `sendToCustomer(string $code, Customer $customer, ...)`
Sama seperti send tapi otomatis menambahkan `customer_name` dan `email`.

#### `sendNow(EmailLog $emailLog): bool`
Kirim email langsung tanpa queue.

## Queue Job

Gunakan `App\Jobs\SendEmailJob` untuk mengirim email via queue, atau buat custom job yang memanggil `EmailTemplateService`.

```php
use App\Jobs\SendEmailJob;
use App\Models\EmailLog;

$emailLog = EmailLog::create([...]);
SendEmailJob::dispatch($emailLog)->onQueue('default');
```

## EmailLog Model

Setiap email yang dikirim tercatat di `email_logs` table:
- `email_template_id`
- `template_code`
- `recipient_email`
- `subject`, `body`
- `placeholders` (json)
- `status`: pending, sent, failed
- `error_message`
- `sent_at`

## Menambah Template Baru

1. **Migration/Seeder**: Tambahkan template di `database/seeders/EmailTemplateSeeder.php`
2. **Run seeder**: `php artisan db:seed --class=EmailTemplateSeeder`
3. **Gunakan di kode**:
```php
$template = EmailTemplate::getByCode('newsletter');
$emailService->send('newsletter', $email, $placeholders);
```

## Placeholder Rules

- Gunakan format `{{placeholder_name}}`
- Placeholder harus didaftarkan di field `placeholders` JSON
- Semua value akan di-cast ke string
- Placeholder yang tidak ada akan dibiarkan apa adanya (tidak error)

## Newsletter Pattern

Untuk newsletter, gunakan template code `newsletter`:
- Body template: `{{newsletter_content}}`
- Placeholders: `subscriber_email`, `website_name`, `logo_url`, `unsubscribe_url`, `current_year`, `newsletter_title`, `newsletter_content`
- Unsubscribe URL wajib disertakan untuk compliance
- Kirim via `SendNewsletterJob` yang extends `ShouldQueue`

## Filament Admin

Kelola template email di: **Admin > Setting > Email Templates**
Kelola log email di: **Admin > Logs > Email Logs**

## Testing

- BE: Test kirim email, queue dispatch, unsubscribe token
- FE: Test form submission, validation, toast feedback
- Pastikan `QUEUE_CONNECTION=sync` di testing env untuk synchronous execution