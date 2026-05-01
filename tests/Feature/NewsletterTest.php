<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        EmailTemplate::create([
            'code' => 'newsletter',
            'name' => 'Newsletter',
            'subject' => '{{newsletter_title}} - {{website_name}}',
            'header_title' => 'Newsletter',
            'header_gradient' => '#4F46E5 0%, #7C3AED 100%',
            'body' => '{{newsletter_content}}',
            'placeholders' => ['subscriber_email', 'website_name', 'logo_url', 'unsubscribe_url', 'current_year', 'newsletter_title', 'newsletter_content'],
            'is_active' => true,
            'is_default' => true,
            'send_to_admin' => false,
        ]);
    }

    /**
     * Test user can subscribe to newsletter.
     */
    public function test_user_can_subscribe_to_newsletter(): void
    {
        Queue::fake();

        $response = $this->post(route('frontend.newsletter.subscribe'), [
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        Queue::assertPushed(\App\Jobs\SendNewsletterJob::class);
    }

    /**
     * Test subscribe validates email format.
     */
    public function test_subscribe_validates_email_format(): void
    {
        $response = $this->post(route('frontend.newsletter.subscribe'), [
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    /**
     * Test subscribe requires email.
     */
    public function test_subscribe_requires_email(): void
    {
        $response = $this->post(route('frontend.newsletter.subscribe'), []);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    /**
     * Test existing active subscriber gets info message.
     */
    public function test_existing_active_subscriber_gets_info_message(): void
    {
        NewsletterSubscriber::factory()->create([
            'email' => 'existing@example.com',
            'is_active' => true,
        ]);

        $response = $this->post(route('frontend.newsletter.subscribe'), [
            'email' => 'existing@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('info');
        $this->assertDatabaseCount('newsletter_subscribers', 1);
    }

    /**
     * Test inactive subscriber can resubscribe.
     */
    public function test_inactive_subscriber_can_resubscribe(): void
    {
        Queue::fake();

        $subscriber = NewsletterSubscriber::factory()->inactive()->create([
            'email' => 'inactive@example.com',
        ]);

        $response = $this->post(route('frontend.newsletter.subscribe'), [
            'email' => 'inactive@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'inactive@example.com',
            'is_active' => true,
            'unsubscribed_at' => null,
        ]);

        Queue::assertPushed(\App\Jobs\SendNewsletterJob::class);
    }

    /**
     * Test user can unsubscribe with valid token.
     */
    public function test_user_can_unsubscribe_with_valid_token(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'unsub@example.com',
            'is_active' => true,
        ]);

        $response = $this->get(route('frontend.newsletter.unsubscribe', ['token' => $subscriber->token]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('status')
            ->has('email')
        );

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'unsub@example.com',
            'is_active' => false,
        ]);

        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);
    }

    /**
     * Test already unsubscribed user sees correct status.
     */
    public function test_already_unsubscribed_user_sees_correct_status(): void
    {
        $subscriber = NewsletterSubscriber::factory()->inactive()->create([
            'email' => 'already@example.com',
        ]);

        $response = $this->get(route('frontend.newsletter.unsubscribe', ['token' => $subscriber->token]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('status', 'already_unsubscribed')
            ->where('email', 'already@example.com')
        );
    }

    /**
     * Test unsubscribe with invalid token returns 404.
     */
    public function test_unsubscribe_with_invalid_token_returns_404(): void
    {
        $response = $this->get(route('frontend.newsletter.unsubscribe', ['token' => 'invalid-token']));

        $response->assertStatus(404);
    }

    /**
     * Test send test newsletter creates subscriber and dispatches job.
     */
    public function test_send_test_newsletter_creates_subscriber_and_dispatches_job(): void
    {
        Queue::fake();

        $response = $this->post(route('frontend.newsletter.send-test'), [
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'test@example.com',
        ]);

        Queue::assertPushed(\App\Jobs\SendNewsletterJob::class);
    }

    /**
     * Test send test validates email.
     */
    public function test_send_test_validates_email(): void
    {
        $response = $this->post(route('frontend.newsletter.send-test'), [
            'email' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Test admin can access newsletter subscribers list.
     */
    public function test_admin_can_access_newsletter_subscribers_list(): void
    {
        $admin = User::factory()->create();
        NewsletterSubscriber::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->get('/admin/newsletter-subscribers');

        $response->assertStatus(200);
    }

    /**
     * Test SendNewsletterJob skips inactive subscribers.
     */
    public function test_send_newsletter_job_skips_inactive_subscribers(): void
    {
        $subscriber = NewsletterSubscriber::factory()->inactive()->create();

        $job = new \App\Jobs\SendNewsletterJob($subscriber, 'welcome');
        $job->handle(app(\App\Services\EmailTemplateService::class));

        $this->assertDatabaseMissing('email_logs', [
            'recipient_email' => $subscriber->email,
        ]);
    }

    /**
     * Test SendNewsletterJob sends email for active subscribers.
     */
    public function test_send_newsletter_job_sends_email_for_active_subscribers(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'active@example.com',
            'is_active' => true,
        ]);

        $job = new \App\Jobs\SendNewsletterJob($subscriber, 'welcome');
        $job->handle(app(\App\Services\EmailTemplateService::class));

        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => 'active@example.com',
            'template_code' => 'newsletter',
        ]);
    }
}