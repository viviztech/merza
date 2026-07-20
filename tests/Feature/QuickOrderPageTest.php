<?php

namespace Tests\Feature;

use App\Filament\Pages\QuickOrder;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuickOrderPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Filament's ->callAction() test helper only resolves actions that are
     * page-level (header/form) or table actions — it doesn't address actions
     * nested inside an arbitrary schema component (like our inline
     * "Use Previous Address" / "Repeat Last Order" buttons), so we find and
     * invoke them directly off the live schema tree instead.
     */
    private function callSchemaAction(\Livewire\Features\SupportTesting\Testable $test, string $name): void
    {
        $schema = $test->instance()->getSchema('content');

        foreach ($schema->getFlatComponents(true, true) as $component) {
            if ($component instanceof Action && $component->getName() === $name) {
                $component->call();

                return;
            }
        }

        throw new \RuntimeException("Action [{$name}] not found in schema.");
    }

    private User $admin;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $this->admin = User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin-test@merza.com',
            'password' => bcrypt('password'),
        ]);
        $this->admin->assignRole('Admin');

        $category = Category::create(['name' => 'Fresh Fruits', 'slug' => 'fresh-fruits', 'is_active' => true]);
        $product  = Product::create([
            'category_id' => $category->id,
            'name'        => 'Test Mango',
            'slug'        => 'test-mango',
            'base_price'  => 100,
            'unit'        => 'kg',
            'is_active'   => true,
        ]);
        $this->variant = ProductVariant::create([
            'product_id'   => $product->id,
            'name'         => '5 kg',
            'sku'          => 'TM-5KG',
            'price'        => 500,
            'weight_value' => 5,
            'weight_unit'  => 'kg',
            'stock_qty'    => 10,
            'is_active'    => true,
        ]);
    }

    public function test_page_loads_for_an_admin(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/quick-order')
            ->assertSuccessful();
    }

    public function test_typing_a_known_phone_number_prefills_customer_and_shows_previous_order(): void
    {
        $contact = Contact::create([
            'name'   => 'Repeat Customer',
            'phone'  => '9333333333',
            'source' => 'whatsapp',
        ]);

        $previousOrder = Order::create([
            'channel'          => 'manual',
            'contact_id'       => $contact->id,
            'customer_name'    => 'Repeat Customer',
            'customer_phone'   => '9333333333',
            'delivery_address' => '12 Old Street',
            'city'             => 'Madurai',
            'state'            => 'Tamil Nadu',
            'postcode'         => '625001',
            'subtotal'         => 500,
            'delivery_fee'     => 20,
            'total'            => 520,
            'payment_method'   => 'cod',
        ]);

        \App\Models\OrderItem::create([
            'order_id'           => $previousOrder->id,
            'product_variant_id' => $this->variant->id,
            'product_name'       => 'Test Mango',
            'variant_name'       => '5 kg',
            'sku'                => 'TM-5KG',
            'quantity'           => 2,
            'unit_price'         => 500,
            'subtotal'           => 1000,
        ]);

        $this->actingAs($this->admin);

        // afterStateUpdated fires automatically on set() for live() fields
        Livewire::test(QuickOrder::class)
            ->set('data.customer_phone', '9333333333')
            ->assertSet('foundContact.id', $contact->id)
            ->assertSet('lastOrder.id', $previousOrder->id)
            ->assertSet('data.customer_name', 'Repeat Customer');
    }

    public function test_repeat_last_order_button_fills_address_and_items(): void
    {
        $contact = Contact::create([
            'name'   => 'Repeat Customer',
            'phone'  => '9777777777',
            'source' => 'whatsapp',
        ]);

        $previousOrder = Order::create([
            'channel'          => 'manual',
            'contact_id'       => $contact->id,
            'customer_name'    => 'Repeat Customer',
            'customer_phone'   => '9777777777',
            'delivery_address' => '12 Old Street',
            'city'             => 'Madurai',
            'state'            => 'Tamil Nadu',
            'postcode'         => '625001',
            'landmark'         => 'Near Temple',
            'subtotal'         => 500,
            'delivery_fee'     => 20,
            'total'            => 520,
            'payment_method'   => 'cod',
        ]);

        \App\Models\OrderItem::create([
            'order_id'           => $previousOrder->id,
            'product_variant_id' => $this->variant->id,
            'product_name'       => 'Test Mango',
            'variant_name'       => '5 kg',
            'sku'                => 'TM-5KG',
            'quantity'           => 2,
            'unit_price'         => 500,
            'subtotal'           => 1000,
        ]);

        $this->actingAs($this->admin);

        $test = Livewire::test(QuickOrder::class)->set('data.customer_phone', '9777777777');
        $this->callSchemaAction($test, 'repeatLastOrder');

        $test->assertSet('data.delivery_address', '12 Old Street')
            ->assertSet('data.postcode', '625001')
            ->assertSet('data.items.0.product_variant_id', $this->variant->id)
            ->assertSet('data.items.0.quantity', 2);
    }

    public function test_creating_an_order_with_valid_data_succeeds_and_recalculates_totals(): void
    {
        $this->actingAs($this->admin);

        $test = Livewire::test(QuickOrder::class)
            ->set('data.customer_phone', '9444444444')
            ->set('data.customer_name', 'New Customer')
            ->set('data.delivery_address', '45 New Street')
            ->set('data.city', 'Theni')
            ->set('data.state', 'Tamil Nadu')
            ->set('data.postcode', '625513')
            ->set('data.items.0.product_variant_id', $this->variant->id)
            ->set('data.items.0.quantity', 3)
            ->call('createOrder');

        $order = Order::where('customer_phone', '9444444444')->first();

        $this->assertNotNull($order);
        $this->assertEquals(1500, (float) $order->subtotal);
    }

    public function test_creating_an_order_with_invalid_pincode_is_rejected(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(QuickOrder::class)
            ->set('data.customer_phone', '9555555555')
            ->set('data.customer_name', 'Bad Pincode')
            ->set('data.delivery_address', '1 Street')
            ->set('data.postcode', '123')
            ->set('data.items.0.product_variant_id', $this->variant->id)
            ->set('data.items.0.quantity', 1)
            ->call('createOrder')
            ->assertHasFormErrors(['data.postcode' => 'digits']);

        $this->assertNull(Order::where('customer_phone', '9555555555')->first());
    }

    public function test_insufficient_stock_blocks_order_creation(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(QuickOrder::class)
            ->set('data.customer_phone', '9666666666')
            ->set('data.customer_name', 'Overorder Customer')
            ->set('data.delivery_address', '1 Street')
            ->set('data.city', 'Theni')
            ->set('data.state', 'Tamil Nadu')
            ->set('data.postcode', '625513')
            ->set('data.items.0.product_variant_id', $this->variant->id)
            ->set('data.items.0.quantity', 999)
            ->call('createOrder');

        $this->assertNull(Order::where('customer_phone', '9666666666')->first());
    }
}
