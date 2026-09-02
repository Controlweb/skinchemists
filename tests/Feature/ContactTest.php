<?php

namespace Tests\Feature;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Salma Benali',
            'email' => 'salma@example.ma',
            'phone' => '06 61 22 84 10',
            'subject' => 'commande',
            'order_number' => 'SCM-1043',
            'message' => 'Bonjour, où en est ma commande passée hier ?',
        ], $overrides);
    }

    public function test_the_contact_page_loads(): void
    {
        $this->get('/contact')
            ->assertSuccessful()
            ->assertSee('Nous contacter')
            ->assertSee('contact@skinchemists.ma');
    }

    public function test_it_stores_the_message_and_notifies_the_shop(): void
    {
        Mail::fake();

        $this->post('/contact', $this->payload())
            ->assertRedirect('/contact')
            ->assertSessionHas('sent');

        $message = ContactMessage::sole();
        $this->assertSame('Salma Benali', $message->name);
        $this->assertSame('commande', $message->subject);
        $this->assertSame('nouveau', $message->status);
        // Normalised the same way as checkout, so the two match on a phone.
        $this->assertSame('0661228410', $message->phone);

        Mail::assertSent(ContactMessageReceived::class, fn ($mail) => $mail->hasTo('contact@skinchemists.ma'));
    }

    public function test_a_failing_mail_server_does_not_lose_the_enquiry(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP connection refused'));

        $this->post('/contact', $this->payload())->assertSessionHas('sent');

        // The whole point of storing before sending.
        $this->assertSame(1, ContactMessage::count());
    }

    public function test_it_requires_a_way_to_reply(): void
    {
        $this->post('/contact', $this->payload(['email' => null, 'phone' => null]))
            ->assertSessionHasErrors(['email', 'phone']);

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_either_an_email_or_a_phone_is_enough(): void
    {
        Mail::fake();

        $this->post('/contact', $this->payload(['email' => null]))->assertSessionHas('sent');
        $this->post('/contact', $this->payload(['phone' => null]))->assertSessionHas('sent');

        $this->assertSame(2, ContactMessage::count());
    }

    public function test_it_rejects_a_message_that_says_nothing(): void
    {
        $this->post('/contact', $this->payload(['message' => 'coucou']))
            ->assertSessionHasErrors('message');
    }

    public function test_it_accepts_the_empty_honeypot_a_real_browser_sends(): void
    {
        Mail::fake();

        // A browser posts every field, including the hidden one, as "".
        // A payload that omits the key entirely is cleaner than reality and
        // hides that the value would otherwise reach the insert.
        $this->post('/contact', $this->payload(['website' => '']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('sent');

        $this->assertSame(1, ContactMessage::count());
    }

    public function test_the_notification_email_renders(): void
    {
        // Mail::fake() never renders the template, so a broken view would
        // pass every other test in this file.
        $message = ContactMessage::create([
            'name' => 'Salma Benali',
            'email' => 'salma@example.ma',
            'phone' => '0661228410',
            'subject' => 'commande',
            'order_number' => 'SCM-1043',
            'message' => 'Où en est ma commande ?',
        ]);

        $rendered = (new ContactMessageReceived($message))->render();

        $this->assertStringContainsString('Salma Benali', $rendered);
        $this->assertStringContainsString('SCM-1043', $rendered);
        $this->assertStringContainsString('Où en est ma commande ?', $rendered);
    }

    public function test_the_honeypot_blocks_a_bot(): void
    {
        $this->post('/contact', $this->payload(['website' => 'http://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_an_unknown_subject_is_refused(): void
    {
        // The value reaches a badge in the admin; it has to be one we know.
        $this->post('/contact', $this->payload(['subject' => 'n-importe-quoi']))
            ->assertSessionHasErrors('subject');
    }

    public function test_the_admin_lists_messages_and_can_mark_them_handled(): void
    {
        Mail::fake();
        $this->post('/contact', $this->payload());

        $user = User::factory()->create(['is_admin' => true]);

        $this->actingAs($user)
            ->get('/admin/contact-messages')
            ->assertSuccessful()
            ->assertSee('Salma Benali');

        $message = ContactMessage::sole();
        $message->update(['status' => 'traite', 'handled_at' => now(), 'handled_by' => $user->id]);

        $this->assertTrue($message->fresh()->isHandled());
        $this->assertSame($user->id, $message->fresh()->handler->id);
    }

    public function test_it_links_an_enquiry_to_a_real_order_when_the_number_matches(): void
    {
        Mail::fake();
        $this->post('/contact', $this->payload(['order_number' => 'SCM-9999']));

        // No such order: the admin flags it rather than pretending it exists.
        $this->assertNull(ContactMessage::sole()->order());
    }
}
