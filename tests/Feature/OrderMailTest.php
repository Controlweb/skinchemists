<?php

namespace Tests\Feature;

use App\Actions\PlaceOrder;
use App\Mail\NewOrderNotification;
use App\Mail\OrderConfirmation;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    private function product(): Product
    {
        $category = Category::firstOrCreate(['slug' => 'test'], ['name' => 'Test']);

        return Product::create([
            'sku' => 'SC-'.fake()->unique()->numberBetween(1000, 9999),
            'name' => 'Soin de test',
            'slug' => 'soin-'.uniqid(),
            'category_id' => $category->id,
            'price_cents' => 50000,
            'stock' => 5,
        ]);
    }

    private function place(?string $email = 'salma@example.ma'): Order
    {
        return app(PlaceOrder::class)->handle([$this->product()->id => 1], [
            'first_name' => 'Salma', 'last_name' => 'Benali',
            'phone' => '0661228410', 'email' => $email,
            'address' => '12 rue Ibn Batouta', 'city' => 'Casablanca',
        ]);
    }

    public function test_it_emails_the_customer_and_the_shop(): void
    {
        Mail::fake();

        $order = $this->place();

        Mail::assertSent(OrderConfirmation::class, fn ($mail) => $mail->hasTo('salma@example.ma')
            && $mail->order->is($order));

        Mail::assertSent(NewOrderNotification::class, fn ($mail) => $mail->hasTo('contact@skinchemists.ma'));
    }

    public function test_it_still_notifies_the_shop_when_the_customer_gave_no_email(): void
    {
        Mail::fake();

        // Email is optional at checkout: COD customers often leave it blank.
        $this->place(email: null);

        Mail::assertNotSent(OrderConfirmation::class);
        Mail::assertSent(NewOrderNotification::class);
    }

    public function test_a_failing_mail_server_does_not_lose_the_order(): void
    {
        // The realistic shared-hosting failure: SMTP is down or misconfigured.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP connection refused'));

        $order = $this->place();

        $this->assertNotNull($order->fresh());
        $this->assertSame('nouvelle', $order->status);
        $this->assertDatabaseHas('orders', ['number' => $order->number]);
    }

    public function test_the_confirmation_renders_with_the_order_details(): void
    {
        $order = $this->place();

        $rendered = (new OrderConfirmation($order))->render();

        $this->assertStringContainsString($order->number, $rendered);
        $this->assertStringContainsString('Salma', $rendered);
        $this->assertStringContainsString('Soin de test', $rendered);
    }

    public function test_the_staff_notification_renders_with_the_phone_to_call(): void
    {
        $order = $this->place();

        $rendered = (new NewOrderNotification($order))->render();

        $this->assertStringContainsString($order->number, $rendered);
        $this->assertStringContainsString('0661228410', $rendered);
    }
}
