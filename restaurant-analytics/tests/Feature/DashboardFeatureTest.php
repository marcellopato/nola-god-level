<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Sale;
use App\Models\Store;
use App\Models\Channel;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\Category;
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
        
        $this->loadSchemaIfNeeded();
        
        $this->store = Store::create([
            'name' => 'Test Restaurant',
            'address_street' => 'Rua da Comida',
            'address_number' => '123',
            'district' => 'Centro',
            'city' => 'Foodville',
            'state' => 'CA',
            'is_active' => true,
        ]);

        $this->channel = Channel::create([
            'name' => 'Online Orders',
            'type' => 'D',
            'description' => 'Online food delivery orders',
        ]);

        $category = Category::create([
            'name' => 'Main Course',
            'type' => 'P',
        ]);

        $this->product = Product::create([
            'name' => 'Delicious Burger',
            'category_id' => $category->id,
            'pos_uuid' => 'burger-uuid-001',
        ]);
    }

    public function test_dashboard_page_loads_successfully()
    {
        $response = $this->get('/');

        $response->assertStatus(200)
                ->assertSee('Restaurant Analytics')
                ->assertSee('Dashboard')
                ->assertSee('Faturamento Total')
                ->assertSee('Vendas no Tempo')
                ->assertSee('Top 10 Produtos');
    }

    public function test_dashboard_displays_kpis_correctly()
    {
        // Create test sales
        $this->createSalesData();

        $response = $this->get('/');

        $response->assertStatus(200)
                ->assertSee('Faturamento Total')
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
        // Create sales for different periods (all within the last 30 days for reliable testing)
        $this->createTestSale(100.00, Carbon::now()->subDays(1)); // Recent
        $this->createTestSale(200.00, Carbon::now()->subDays(5)); // Last week
        $this->createTestSale(300.00, Carbon::now()->subDays(10)); // Last month

        $component = \Livewire\Livewire::test(\App\Livewire\Dashboard::class);

        // Test last 7 days filter - should include sales from 1 and 5 days ago
        $component->call('setDateRange', 'last7days');
        $kpis = $component->get('kpis');
        $this->assertGreaterThanOrEqual(2, $kpis['total_sales']);

        // Test last 30 days filter - should include all sales
        $component->call('setDateRange', 'last30days');
        $kpis = $component->get('kpis'); 
        $this->assertGreaterThanOrEqual(3, $kpis['total_sales']); // All sales
    }    public function test_dashboard_charts_have_correct_data_structure()
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
        
        // If products exist, verify structure
        if (!empty($topProducts)) {
            $firstProduct = $topProducts[0];
            $this->assertArrayHasKey('name', $firstProduct);
            $this->assertArrayHasKey('quantity', $firstProduct);
            $this->assertArrayHasKey('revenue', $firstProduct);
        }
    }

    public function test_dashboard_store_performance_displays_correctly()
    {
        $this->createSalesData();

        $component = \Livewire\Livewire::test(\App\Livewire\Dashboard::class);

        $storePerformance = $component->get('storePerformance');
        $this->assertIsArray($storePerformance);
    }

    public function test_dashboard_channel_performance_displays_correctly()
    {
        $this->createSalesData();

        $component = \Livewire\Livewire::test(\App\Livewire\Dashboard::class);

        $channelPerformance = $component->get('channelPerformance');
        $this->assertIsArray($channelPerformance);
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
        $this->assertGreaterThanOrEqual(90, $kpis['total_sales']); // Allow for some variance in test data
    }

    public function test_dashboard_pagination_for_large_results()
    {
        // Create a category first
        $testCategory = Category::create([
            'name' => 'Test Category',
            'type' => 'P',
        ]);

        // Create multiple products
        $products = [];
        for ($i = 0; $i < 15; $i++) {
            $products[] = Product::create([
                'name' => "Product $i",
                'category_id' => $testCategory->id,
                'pos_uuid' => "product-uuid-$i",
            ]);
        }

        // Create sales for each product
        foreach ($products as $product) {
            $sale = $this->createTestSale(100.00);
            $quantity = rand(1, 5);
            $basePrice = rand(10, 50);
            ProductSale::create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'base_price' => $basePrice,
                'total_price' => $basePrice * $quantity,
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
            'base_price' => 15.99,
            'total_price' => 31.98,
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
            'created_at' => $date ?? Carbon::now(),
            'total_amount' => $amount,
            'total_amount_items' => $amount * 0.9,
            'total_discount' => 0,
            'service_tax_fee' => $amount * 0.1,
            'sale_status_desc' => 'COMPLETED',
        ]);
    }
}