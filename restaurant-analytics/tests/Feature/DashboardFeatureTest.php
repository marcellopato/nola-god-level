<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Sale;
use App\Models\Store;
use App\Models\Channel;
use App\Models\Product;
use App\Models\ProductSale;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private Channel $channel;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->store = Store::create([
            'name' => 'Test Restaurant',
            'address' => '123 Food St',
            'city' => 'Foodville',
            'state' => 'CA',
            'zip_code' => '90210',
            'phone' => '555-123-4567',
            'email' => 'test@restaurant.com',
            'is_active' => true,
        ]);

        $this->channel = Channel::create([
            'name' => 'Online Orders',
            'type' => 'Online',
            'description' => 'Online food delivery orders',
        ]);

        $this->product = Product::create([
            'name' => 'Delicious Burger',
            'description' => 'Best burger in town',
            'price' => 15.99,
            'category' => 'Main Course',
            'is_active' => true,
        ]);
    }

    public function test_dashboard_page_loads_successfully()
    {
        $response = $this->get('/');

        $response->assertStatus(200)
                ->assertSee('Restaurant Analytics')
                ->assertSee('Dashboard')
                ->assertSee('KPIs')
                ->assertSee('Vendas ao Longo do Tempo')
                ->assertSee('Produtos Mais Vendidos');
    }

    public function test_dashboard_displays_kpis_correctly()
    {
        // Create test sales
        $this->createSalesData();

        $response = $this->get('/');

        $response->assertStatus(200)
                ->assertSee('Total de Receita')
                ->assertSee('Total de Vendas')
                ->assertSee('Ticket Médio')
                ->assertSee('Lojas Ativas');
    }

    public function test_dashboard_shows_empty_state_with_no_data()
    {
        $response = $this->get('/');

        $response->assertStatus(200)
                ->assertSee('R$ 0,00') // Empty revenue
                ->assertSee('0'); // Empty sales count
    }

    public function test_dashboard_filters_work_correctly()
    {
        $this->createSalesData();

        // Test with Livewire component
        $component = \Livewire\Livewire::test(\App\Livewire\Dashboard::class);

        // Test store filter
        $component->set('selectedStore', $this->store->id);
        $component->assertDispatched('dataRefreshed');

        // Test channel filter
        $component->set('selectedChannel', $this->channel->id);
        $component->assertDispatched('dataRefreshed');

        // Test date range filter
        $component->call('setDateRange', 'last7days');
        $component->assertSet('activeDateRange', 'last7days');
    }

    public function test_dashboard_date_range_buttons_work()
    {
        $component = \Livewire\Livewire::test(\App\Livewire\Dashboard::class);

        // Test different date ranges
        $ranges = ['today', 'last7days', 'last30days', 'thisMonth'];

        foreach ($ranges as $range) {
            $component->call('setDateRange', $range);
            $component->assertSet('activeDateRange', $range);
            $component->assertDispatched('dataRefreshed');
        }
    }

    public function test_dashboard_manual_date_input_works()
    {
        $component = \Livewire\Livewire::test(\App\Livewire\Dashboard::class);

        $customDateFrom = Carbon::now()->subDays(14)->format('Y-m-d');
        $customDateTo = Carbon::now()->subDays(1)->format('Y-m-d');

        $component->set('dateFrom', $customDateFrom);
        $component->assertSet('dateFrom', $customDateFrom);
        $component->assertSet('activeDateRange', null); // Should clear active range

        $component->set('dateTo', $customDateTo);
        $component->assertSet('dateTo', $customDateTo);
        $component->assertSet('activeDateRange', null);
    }

    public function test_dashboard_refresh_button_works()
    {
        $this->createSalesData();

        $component = \Livewire\Livewire::test(\App\Livewire\Dashboard::class);

        $component->call('refreshData');
        $component->assertDispatched('dataRefreshed');
    }

    public function test_dashboard_shows_correct_data_for_different_periods()
    {
        // Create sales for different periods
        $this->createTestSale(100.00, Carbon::now()); // Today
        $this->createTestSale(200.00, Carbon::now()->subDays(5)); // Last week
        $this->createTestSale(300.00, Carbon::now()->subDays(20)); // Last month

        $component = \Livewire\Livewire::test(\App\Livewire\Dashboard::class);

        // Test today filter
        $component->call('setDateRange', 'today');
        $kpis = $component->get('kpis');
        $this->assertEquals(1, $kpis['total_sales']);

        // Test last 7 days filter
        $component->call('setDateRange', 'last7days');
        $kpis = $component->get('kpis');
        $this->assertEquals(2, $kpis['total_sales']); // Today + 5 days ago

        // Test last 30 days filter
        $component->call('setDateRange', 'last30days');
        $kpis = $component->get('kpis');
        $this->assertEquals(3, $kpis['total_sales']); // All sales
    }

    public function test_dashboard_charts_have_correct_data_structure()
    {
        $this->createSalesData();

        $component = \Livewire\Livewire::test(\App\Livewire\Dashboard::class);

        // Test sales over time chart data
        $salesOverTime = $component->get('salesOverTime');
        $this->assertIsArray($salesOverTime);
        $this->assertArrayHasKey('labels', $salesOverTime);
        $this->assertArrayHasKey('sales_data', $salesOverTime);
        $this->assertArrayHasKey('revenue_data', $salesOverTime);

        // Test hourly distribution chart data
        $hourlyDistribution = $component->get('hourlyDistribution');
        $this->assertIsArray($hourlyDistribution);
        $this->assertArrayHasKey('labels', $hourlyDistribution);
        $this->assertArrayHasKey('sales_data', $hourlyDistribution);
    }

    public function test_dashboard_top_products_display_correctly()
    {
        $this->createSalesWithProducts();

        $component = \Livewire\Livewire::test(\App\Livewire\Dashboard::class);

        $topProducts = $component->get('topProducts');
        $this->assertIsArray($topProducts);
        $this->assertNotEmpty($topProducts);
        
        $firstProduct = $topProducts[0];
        $this->assertArrayHasKey('name', $firstProduct);
        $this->assertArrayHasKey('quantity', $firstProduct);
        $this->assertArrayHasKey('revenue', $firstProduct);
    }

    public function test_dashboard_store_performance_displays_correctly()
    {
        $this->createSalesData();

        $component = \Livewire\Livewire::test(\App\Livewire\Dashboard::class);

        $storePerformance = $component->get('storePerformance');
        $this->assertIsArray($storePerformance);
        $this->assertNotEmpty($storePerformance);
    }

    public function test_dashboard_channel_performance_displays_correctly()
    {
        $this->createSalesData();

        $component = \Livewire\Livewire::test(\App\Livewire\Dashboard::class);

        $channelPerformance = $component->get('channelPerformance');
        $this->assertIsArray($channelPerformance);
        $this->assertNotEmpty($channelPerformance);
    }

    public function test_dashboard_handles_large_datasets()
    {
        // Create 100 sales records
        for ($i = 0; $i < 100; $i++) {
            $this->createTestSale(
                rand(50, 500), 
                Carbon::now()->subDays(rand(0, 30))
            );
        }

        $response = $this->get('/');
        $response->assertStatus(200);

        $component = \Livewire\Livewire::test(\App\Livewire\Dashboard::class);
        
        // Should handle large dataset without errors
        $kpis = $component->get('kpis');
        $this->assertEquals(100, $kpis['total_sales']);
    }

    public function test_dashboard_pagination_for_large_results()
    {
        // Create multiple products
        $products = [];
        for ($i = 0; $i < 15; $i++) {
            $products[] = Product::create([
                'name' => "Product $i",
                'description' => "Description for product $i",
                'price' => rand(10, 50),
                'category' => 'Test Category',
                'is_active' => true,
            ]);
        }

        // Create sales for each product
        foreach ($products as $product) {
            $sale = $this->createTestSale(100.00);
            ProductSale::create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'quantity' => rand(1, 5),
                'unit_price' => $product->price,
                'total_price' => $product->price * rand(1, 5),
            ]);
        }

        $component = \Livewire\Livewire::test(\App\Livewire\Dashboard::class);

        $topProducts = $component->get('topProducts');
        // Should limit to top 10 products
        $this->assertLessThanOrEqual(10, count($topProducts));
    }

    /**
     * Create basic sales data for testing
     */
    private function createSalesData(): void
    {
        $this->createTestSale(150.00);
        $this->createTestSale(200.00);
        $this->createTestSale(75.50);
    }

    /**
     * Create sales with product relationships
     */
    private function createSalesWithProducts(): void
    {
        $sale = $this->createTestSale(100.00);
        
        ProductSale::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => $this->product->price,
            'total_price' => $this->product->price * 2,
        ]);
    }

    /**
     * Helper method to create a test sale
     */
    private function createTestSale(float $amount, ?Carbon $date = null): Sale
    {
        return Sale::create([
            'store_id' => $this->store->id,
            'channel_id' => $this->channel->id,
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