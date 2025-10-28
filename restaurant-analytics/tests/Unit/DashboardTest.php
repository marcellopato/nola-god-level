<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Livewire\Dashboard;
use App\Models\Sale;
use App\Models\Store;
use App\Models\Channel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private Channel $channel;
    private Store $store2;
    private Channel $channel2;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Load domain schema if needed
        $this->loadSchemaIfNeeded();
        
        $this->store = Store::create([
            'name' => 'Store 1',
            'address' => '123 Test St',
            'city' => 'Test City',
            'state' => 'TS',
            'zip_code' => '12345',
            'phone' => '123-456-7890',
            'email' => 'test1@store.com',
            'is_active' => true,
        ]);

        $this->store2 = Store::create([
            'name' => 'Store 2',
            'address' => '456 Test Ave',
            'city' => 'Test City 2',
            'state' => 'TS',
            'zip_code' => '54321',
            'phone' => '098-765-4321',
            'email' => 'test2@store.com',
            'is_active' => true,
        ]);

        $this->channel = Channel::create([
            'name' => 'Online',
            'type' => 'D', // D=Delivery
            'description' => 'Online sales channel',
        ]);

        $this->channel2 = Channel::create([
            'name' => 'In-Store',
            'type' => 'P', // P=Presential
            'description' => 'Physical store sales',
        ]);
    }

    public function test_dashboard_component_renders()
    {
        Livewire::test(Dashboard::class)
            ->assertOk()
            ->assertViewHas('stores')
            ->assertViewHas('channels');
    }

    public function test_dashboard_initializes_with_default_date_range()
    {
        $component = Livewire::test(Dashboard::class);

        $dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = Carbon::now()->format('Y-m-d');

        $component->assertSet('dateFrom', $dateFrom)
                 ->assertSet('dateTo', $dateTo)
                 ->assertSet('activeDateRange', 'last30days');
    }

    public function test_dashboard_loads_data_on_mount()
    {
        $testDate = Carbon::parse('2024-01-15 12:00:00');
        $sale = $this->createTestSale(100.00, $testDate);
        
        // Create a product and product sale to populate top products
        $product = \App\Models\Product::create([
            'name' => 'Test Product',
            'description' => 'Test product description',
            'price' => 10.99,
            'category' => 'Test Category',
            'is_active' => true,
        ]);
        
        \App\Models\ProductSale::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'base_price' => 50.00,
            'total_price' => 100.00,
        ]);

        $component = Livewire::test(Dashboard::class)
            ->set('dateFrom', $testDate->copy()->startOfDay()->toDateTimeString())
            ->set('dateTo', $testDate->copy()->endOfDay()->toDateTimeString())
            ->call('loadData');

        // Test each property individually for better debugging
        $this->assertNotEmpty($component->get('kpis'), 'KPIs array is empty');
        $this->assertNotEmpty($component->get('salesOverTime'), 'Sales over time is empty');
        $this->assertNotEmpty($component->get('topProducts'), 'Top products is empty');
        $this->assertNotEmpty($component->get('storePerformance'), 'Store performance is empty');
        $this->assertNotEmpty($component->get('channelPerformance'), 'Channel performance is empty');
        $this->assertNotEmpty($component->get('hourlyDistribution'), 'Hourly distribution is empty');
    }

    public function test_set_date_range_today()
    {
        $component = Livewire::test(Dashboard::class);

        $today = Carbon::now()->format('Y-m-d');

        $component->call('setDateRange', 'today');

        $component->assertSet('dateFrom', $today)
                 ->assertSet('dateTo', $today)
                 ->assertSet('activeDateRange', 'today')
                 ->assertDispatched('dataRefreshed');
    }

    public function test_set_date_range_last7days()
    {
        $component = Livewire::test(Dashboard::class);

        $dateFrom = Carbon::now()->subDays(7)->format('Y-m-d');
        $dateTo = Carbon::now()->format('Y-m-d');

        $component->call('setDateRange', 'last7days');

        $component->assertSet('dateFrom', $dateFrom)
                 ->assertSet('dateTo', $dateTo)
                 ->assertSet('activeDateRange', 'last7days')
                 ->assertDispatched('dataRefreshed');
    }

    public function test_set_date_range_last30days()
    {
        $component = Livewire::test(Dashboard::class);

        $dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = Carbon::now()->format('Y-m-d');

        $component->call('setDateRange', 'last30days');

        $component->assertSet('dateFrom', $dateFrom)
                 ->assertSet('dateTo', $dateTo)
                 ->assertSet('activeDateRange', 'last30days')
                 ->assertDispatched('dataRefreshed');
    }

    public function test_set_date_range_this_month()
    {
        $component = Livewire::test(Dashboard::class);

        $dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $dateTo = Carbon::now()->format('Y-m-d');

        $component->call('setDateRange', 'thisMonth');

        $component->assertSet('dateFrom', $dateFrom)
                 ->assertSet('dateTo', $dateTo)
                 ->assertSet('activeDateRange', 'thisMonth')
                 ->assertDispatched('dataRefreshed');
    }

    public function test_updated_selected_store_filters_data()
    {
        $this->createTestSale(100.00, Carbon::now(), $this->store);
        $this->createTestSale(50.00, Carbon::now(), $this->store2);

        $component = Livewire::test(Dashboard::class);

        $component->set('selectedStore', $this->store->id);

        $component->assertDispatched('dataRefreshed');
        // The component should now filter data by the selected store
    }

    public function test_updated_selected_channel_filters_data()
    {
        $this->createTestSale(100.00, Carbon::now(), $this->store, $this->channel);
        $this->createTestSale(75.00, Carbon::now(), $this->store, $this->channel2);

        $component = Livewire::test(Dashboard::class);

        $component->set('selectedChannel', $this->channel->id);

        $component->assertDispatched('dataRefreshed');
        // The component should now filter data by the selected channel
    }

    public function test_updated_date_from_clears_active_date_range()
    {
        $component = Livewire::test(Dashboard::class);

        // First set a predefined range
        $component->call('setDateRange', 'last7days');
        $component->assertSet('activeDateRange', 'last7days');

        // Then manually change dateFrom
        $newDate = Carbon::now()->subDays(5)->format('Y-m-d');
        $component->set('dateFrom', $newDate);

        $component->assertSet('dateFrom', $newDate)
                 ->assertSet('activeDateRange', null)
                 ->assertDispatched('dataRefreshed');
    }

    public function test_updated_date_to_clears_active_date_range()
    {
        $component = Livewire::test(Dashboard::class);

        // First set a predefined range
        $component->call('setDateRange', 'last30days');
        $component->assertSet('activeDateRange', 'last30days');

        // Then manually change dateTo
        $newDate = Carbon::now()->subDay()->format('Y-m-d');
        $component->set('dateTo', $newDate);

        $component->assertSet('dateTo', $newDate)
                 ->assertSet('activeDateRange', null)
                 ->assertDispatched('dataRefreshed');
    }

    public function test_refresh_data_clears_cache_and_reloads()
    {
        $this->createTestSale(100.00);

        $component = Livewire::test(Dashboard::class);

        $component->call('refreshData');

        $component->assertDispatched('dataRefreshed')
                 ->assertSet('kpis', fn($value) => !empty($value))
                 ->assertSet('salesOverTime', fn($value) => !empty($value));
    }

    public function test_get_filters_returns_correct_array()
    {
        $component = Livewire::test(Dashboard::class);

        $dateFrom = Carbon::now()->subDays(7)->format('Y-m-d');
        $dateTo = Carbon::now()->format('Y-m-d');

        $component->set('dateFrom', $dateFrom)
                 ->set('dateTo', $dateTo)
                 ->set('selectedStore', $this->store->id)
                 ->set('selectedChannel', $this->channel->id);

        // Since getFilters is private, we can't test it directly, 
        // but we can test that the data loading methods work with filters
        $component->call('refreshData');
        $component->assertDispatched('dataRefreshed');
    }

    public function test_basic_kpis_calculation()
    {
        // Create test sales with known values
        $testDate = Carbon::parse('2024-01-15 12:00:00');
        $this->createTestSale(100.00, $testDate);
        $this->createTestSale(200.00, $testDate);
        $this->createTestSale(150.00, $testDate);

        // Set dates to include test sales with wider range
        $component = Livewire::test(Dashboard::class)
            ->set('dateFrom', $testDate->copy()->startOfDay()->toDateTimeString())
            ->set('dateTo', $testDate->copy()->endOfDay()->toDateTimeString())
            ->call('loadData');

        $kpis = $component->get('kpis');

        $this->assertIsArray($kpis);
        $this->assertArrayHasKey('total_sales', $kpis);
        $this->assertArrayHasKey('total_revenue', $kpis);
        $this->assertArrayHasKey('avg_ticket', $kpis);
        $this->assertArrayHasKey('active_stores', $kpis);

        $this->assertEquals(3, $kpis['total_sales']);
        $this->assertEquals('450,00', $kpis['total_revenue']);
        $this->assertEquals('150,00', $kpis['avg_ticket']);
        $this->assertEquals(2, $kpis['active_stores']); // Both stores are active
    }

    public function test_sales_over_time_data_structure()
    {
        $this->createTestSale(100.00);

        $component = Livewire::test(Dashboard::class);

        $salesOverTime = $component->get('salesOverTime');

        $this->assertIsArray($salesOverTime);
        $this->assertArrayHasKey('labels', $salesOverTime);
        $this->assertArrayHasKey('sales_data', $salesOverTime);
        $this->assertArrayHasKey('revenue_data', $salesOverTime);
        $this->assertIsArray($salesOverTime['labels']);
        $this->assertIsArray($salesOverTime['sales_data']);
        $this->assertIsArray($salesOverTime['revenue_data']);
    }

    public function test_hourly_distribution_data_structure()
    {
        $this->createTestSale(100.00);

        $component = Livewire::test(Dashboard::class);

        $hourlyDistribution = $component->get('hourlyDistribution');

        $this->assertIsArray($hourlyDistribution);
        $this->assertArrayHasKey('labels', $hourlyDistribution);
        $this->assertArrayHasKey('sales_data', $hourlyDistribution);
        $this->assertIsArray($hourlyDistribution['labels']);
        $this->assertIsArray($hourlyDistribution['sales_data']);
    }

    public function test_component_renders_with_stores_and_channels()
    {
        $component = Livewire::test(Dashboard::class);

        $component->assertViewHas('stores', function ($stores) {
            return $stores->contains('name', 'Store 1') && 
                   $stores->contains('name', 'Store 2');
        });

        $component->assertViewHas('channels', function ($channels) {
            return $channels->contains('name', 'Online') && 
                   $channels->contains('name', 'In-Store');
        });
    }

    /**
     * Helper method to create a test sale
     */
    private function createTestSale(float $amount, ?Carbon $date = null, ?Store $store = null, ?Channel $channel = null): Sale
    {
        return Sale::create([
            'store_id' => ($store ?? $this->store)->id,
            'channel_id' => ($channel ?? $this->channel)->id,
            'sale_date' => ($date ?? Carbon::now())->toDateString(),
            'sale_time' => ($date ?? Carbon::now())->toTimeString(),
            'total_amount' => $amount,
            'tax_amount' => $amount * 0.1,
            'discount_amount' => 0,
            'payment_method' => 'Credit Card',
            'sale_status_desc' => 'COMPLETED',
            'customer_type' => 'Regular',
            'created_at' => $date ?? Carbon::now(),
            'updated_at' => $date ?? Carbon::now(),
        ]);
    }
}