<?php

namespace Tests\Feature;

use App\Actions\PlaceOrder;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Filament\Auth\Pages\EditProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
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
            'name' => 'Sérum de test',
            'slug' => 'serum-'.uniqid(),
            'category_id' => $category->id,
            'price_cents' => 50000,
            'stock' => 10,
        ]);
    }

    public function test_the_admin_panel_is_closed_to_guests(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_make_grants_access_without_touching_the_password(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $original = $user->password;

        $this->artisan('admin:make', ['email' => $user->email])->assertSuccessful();

        $user->refresh();
        $this->assertTrue($user->is_admin);
        $this->assertSame($original, $user->password);
    }

    public function test_admin_make_can_revoke_access(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->artisan('admin:make', ['email' => $user->email, '--revoke' => true])
            ->assertSuccessful();

        $this->assertFalse($user->refresh()->is_admin);
    }

    public function test_a_signed_in_user_without_the_admin_flag_is_refused(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_a_signed_in_user_reaches_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_the_orders_screen_lists_real_orders(): void
    {
        $product = $this->product();

        $order = app(PlaceOrder::class)->handle([$product->id => 1], [
            'first_name' => 'Salma', 'last_name' => 'Benali',
            'phone' => '0661228410', 'address' => '12 rue Ibn Batouta',
            'city' => 'Casablanca',
        ]);

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin/orders')
            ->assertSuccessful()
            ->assertSee($order->number);
    }

    public function test_the_products_screen_loads(): void
    {
        $this->product();

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin/products')
            ->assertSuccessful();
    }

    /**
     * These caught a real breakage once: a Filament class that moved namespace
     * only blew up when the page was actually rendered.
     */
    public function test_every_admin_screen_renders(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $screens = [
            'orders', 'products', 'reviews', 'promotions',
            'articles', 'bundles', 'ingredients', 'contact-messages',
        ];

        foreach ($screens as $screen) {
            $this->get("/admin/{$screen}")
                ->assertSuccessful("L'écran /admin/{$screen} ne s'affiche pas");
        }
    }

    public function test_the_account_screen_loads(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin/profile')
            ->assertSuccessful()
            ->assertSee('Adresse Email')
            ->assertSee('Nouveau mot de passe');
    }

    /**
     * The confirmation and current-password fields are deliberately absent
     * until you touch something sensitive — both source fields are live, so
     * they appear as you type. Asserting it, because a page that asks for the
     * current password only sometimes otherwise reads as a bug.
     */
    public function test_the_account_screen_asks_for_the_current_password_only_when_needed(): void
    {
        $user = User::factory()->create(['is_admin' => true, 'email' => 'ancien@skinchemists.ma']);

        $page = Livewire::actingAs($user)->test(EditProfile::class);
        $page->assertFormFieldHidden('currentPassword')
            ->assertFormFieldHidden('passwordConfirmation');

        $page->fillForm(['password' => 'un-nouveau-mot-de-passe'])
            ->assertFormFieldVisible('currentPassword')
            ->assertFormFieldVisible('passwordConfirmation');

        // Changing only the email must ask for it too, not just a password change.
        Livewire::actingAs($user)->test(EditProfile::class)
            ->fillForm(['email' => 'nouveau@skinchemists.ma'])
            ->assertFormFieldVisible('currentPassword');
    }

    public function test_the_account_screen_saves_a_new_email_and_password(): void
    {
        $user = User::factory()->create([
            'is_admin' => true,
            'email' => 'ancien@skinchemists.ma',
            'password' => Hash::make('mot-de-passe-actuel'),
        ]);

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->fillForm([
                'email' => 'nouveau@skinchemists.ma',
                'password' => 'un-nouveau-mot-de-passe',
                'passwordConfirmation' => 'un-nouveau-mot-de-passe',
                'currentPassword' => 'mot-de-passe-actuel',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertSame('nouveau@skinchemists.ma', $user->email);
        $this->assertTrue(Hash::check('un-nouveau-mot-de-passe', $user->password));
    }

    /** Without this, anyone with a borrowed open session could take the account. */
    public function test_the_account_screen_refuses_a_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'is_admin' => true,
            'email' => 'ancien@skinchemists.ma',
            'password' => Hash::make('mot-de-passe-actuel'),
        ]);

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->fillForm([
                'email' => 'pirate@example.com',
                'currentPassword' => 'pas-le-bon',
            ])
            ->call('save')
            ->assertHasFormErrors(['currentPassword']);

        $this->assertSame('ancien@skinchemists.ma', $user->refresh()->email);
    }

    public function test_the_reviews_screen_loads(): void
    {
        Review::create([
            'product_id' => $this->product()->id,
            'author' => 'Salma B.',
            'rating' => 5,
            'body' => 'Très bon produit.',
            'status' => 'en_attente',
        ]);

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin/reviews')
            ->assertSuccessful();
    }
}
