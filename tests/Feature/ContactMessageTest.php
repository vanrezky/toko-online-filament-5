<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test halaman kontak dapat diakses.
     */
    public function test_contact_page_can_be_accessed(): void
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer, 'customer')->get(route('frontend.contact'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('settings')
        );
    }

    /**
     * Test form kontak dapat dikirim dengan data valid.
     */
    public function test_contact_form_can_be_submitted_with_valid_data(): void
    {
        $customer = $this->createCustomer();

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Pertanyaan Produk',
            'message' => 'Saya ingin menanyakan tentang ketersediaan produk ini.',
        ];

        $response = $this->actingAs($customer, 'customer')->post(route('frontend.contact.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Pesan berhasil dikirim! Kami akan menghubungi Anda segera.');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Pertanyaan Produk',
            'message' => 'Saya ingin menanyakan tentang ketersediaan produk ini.',
            'is_read' => false,
        ]);
    }

    /**
     * Test form kontak mevalidasi field yang wajib diisi.
     */
    public function test_contact_form_validates_required_fields(): void
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer, 'customer')->post(route('frontend.contact.store'), []);

        $response->assertSessionHasErrors([
            'name',
            'email',
            'subject',
            'message',
        ]);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    /**
     * Test form kontak mevalidasi format email.
     */
    public function test_contact_form_validates_email_format(): void
    {
        $customer = $this->createCustomer();

        $data = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'subject' => 'Pertanyaan Produk',
            'message' => 'Saya ingin menanyakan tentang ketersediaan produk ini.',
        ];

        $response = $this->actingAs($customer, 'customer')->post(route('frontend.contact.store'), $data);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseCount('contact_messages', 0);
    }

    /**
     * Test form kontak mevalidasi panjang minimum pesan.
     */
    public function test_contact_form_validates_message_min_length(): void
    {
        $customer = $this->createCustomer();

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Pertanyaan Produk',
            'message' => 'Short',
        ];

        $response = $this->actingAs($customer, 'customer')->post(route('frontend.contact.store'), $data);

        $response->assertSessionHasErrors(['message']);
        $this->assertDatabaseCount('contact_messages', 0);
    }

    /**
     * Test form kontak mevalidasi panjang maksimum field.
     */
    public function test_contact_form_validates_max_length(): void
    {
        $customer = $this->createCustomer();

        $data = [
            'name' => str_repeat('a', 256),
            'email' => 'john@example.com',
            'subject' => str_repeat('b', 256),
            'message' => str_repeat('c', 5001),
        ];

        $response = $this->actingAs($customer, 'customer')->post(route('frontend.contact.store'), $data);

        $response->assertSessionHasErrors(['name', 'subject', 'message']);
        $this->assertDatabaseCount('contact_messages', 0);
    }

    /**
     * Test admin dapat mengakses halaman list contact messages di Filament.
     */
    public function test_admin_can_access_contact_messages_list(): void
    {
        $admin = User::factory()->create();

        ContactMessage::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->get('/admin/monitoring/contact-messages');

        $response->assertStatus(200);
    }

    /**
     * Test admin dapat melihat detail contact message di Filament.
     */
    public function test_admin_can_view_contact_message_detail(): void
    {
        $admin = User::factory()->create();
        $message = ContactMessage::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test message content',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/monitoring/contact-messages/{$message->id}");

        $response->assertStatus(200);
    }

    /**
     * Test badge unread menampilkan jumlah yang benar.
     */
    public function test_unread_badge_shows_correct_count(): void
    {
        ContactMessage::factory()->count(3)->create(['is_read' => false]);
        ContactMessage::factory()->count(2)->create(['is_read' => true]);

        $count = ContactMessage::unread()->count();

        $this->assertEquals(3, $count);
    }

    /**
     * Test contact message dapat ditandai sebagai telah dibaca.
     */
    public function test_contact_message_can_be_marked_as_read(): void
    {
        $message = ContactMessage::factory()->create(['is_read' => false]);

        $this->assertFalse($message->fresh()->is_read);

        $message->markAsRead();

        $this->assertTrue($message->fresh()->is_read);
        $this->assertNotNull($message->fresh()->read_at);
    }

    private function createCustomer(): Customer
    {
        return Customer::query()->create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'customer-'.Str::uuid().'@example.test',
            'password' => bcrypt('password'),
            'credit_limit' => 0,
            'is_active' => 'active',
        ]);
    }
}
