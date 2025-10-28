<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Sale;
use App\Models\Store;
use App\Models\Channel;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class AnalyticsServiceSimpleTest extends TestCase
{
    
    private AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
        
        // Clean and create test data
        $this->cleanTestData();
        $this->seedTestData();
    }

    private function cleanTestData(): void
    {
        // Clean test data in reverse order of dependencies
        DB::table('item_product_sales')->truncate();
        DB::table('product_sales')->truncate();
        DB::table('payments')->truncate();
        DB::table('delivery_sales')->truncate();
        DB::table('sales')->truncate();
        DB::table('products')->truncate();
        DB::table('categories')->truncate();
        DB::table('payment_types')->truncate();
        DB::table('channels')->truncate();
        DB::table('stores')->truncate();
        DB::table('brands')->where('id', '>', 1)->delete(); // Keep the initial brand from schema
    }

    private function seedTestData(): void
    {
        // Create brands, stores, channels, products and sales with deterministic data
        
        // Use existing brand from schema or create if not exists
        if (!DB::table('brands')->where('id', 1)->exists()) {
            DB::table('brands')->insert([
                'id' => 1,
                'name' => 'Test Restaurant Chain',
                'created_at' => now(),
            ]);
        }

        // Stores
        DB::table('stores')->insert([
            ['id' => 1, 'brand_id' => 1, 'name' => 'Store 1', 'city' => 'São Paulo', 'is_active' => true, 'created_at' => now()],
            ['id' => 2, 'brand_id' => 1, 'name' => 'Store 2', 'city' => 'Rio de Janeiro', 'is_active' => true, 'created_at' => now()],
            ['id' => 3, 'brand_id' => 1, 'name' => 'Store 3', 'city' => 'Belo Horizonte', 'is_active' => true, 'created_at' => now()],
        ]);

        // Channels
        DB::table('channels')->insert([
            ['id' => 1, 'brand_id' => 1, 'name' => 'Presencial', 'type' => 'P', 'created_at' => now()],
            ['id' => 2, 'brand_id' => 1, 'name' => 'iFood', 'type' => 'D', 'created_at' => now()],
        ]);

        // Categories
        DB::table('categories')->insert([
            'id' => 1,
            'brand_id' => 1,
            'name' => 'Hambúrgueres',
            'type' => 'P',
            'created_at' => now(),
        ]);

        // Products
        DB::table('products')->insert([
            ['id' => 1, 'brand_id' => 1, 'category_id' => 1, 'name' => 'Hambúrguer Clássico', 'created_at' => now()],
            ['id' => 2, 'brand_id' => 1, 'category_id' => 1, 'name' => 'Pizza Margherita', 'created_at' => now()],
        ]);

        // Payment types
        DB::table('payment_types')->insert([
            'id' => 1,
            'brand_id' => 1,
            'description' => 'Dinheiro',
            'created_at' => now(),
        ]);

        // Sales - deterministic data for testing
        $salesData = [
            // Store 1 sales
            ['id' => 1, 'store_id' => 1, 'channel_id' => 1, 'total_amount' => 25.00, 'sale_status_desc' => 'COMPLETED', 'created_at' => '2024-10-01 12:00:00'],
            ['id' => 2, 'store_id' => 1, 'channel_id' => 1, 'total_amount' => 30.00, 'sale_status_desc' => 'COMPLETED', 'created_at' => '2024-10-01 13:00:00'],
            ['id' => 3, 'store_id' => 1, 'channel_id' => 2, 'total_amount' => 45.00, 'sale_status_desc' => 'COMPLETED', 'created_at' => '2024-10-02 14:00:00'],
            
            // Store 2 sales
            ['id' => 4, 'store_id' => 2, 'channel_id' => 1, 'total_amount' => 35.00, 'sale_status_desc' => 'COMPLETED', 'created_at' => '2024-10-01 15:00:00'],
            ['id' => 5, 'store_id' => 2, 'channel_id' => 2, 'total_amount' => 50.00, 'sale_status_desc' => 'COMPLETED', 'created_at' => '2024-10-02 16:00:00'],
            
            // Store 3 sales
            ['id' => 6, 'store_id' => 3, 'channel_id' => 1, 'total_amount' => 20.00, 'sale_status_desc' => 'COMPLETED', 'created_at' => '2024-10-01 17:00:00'],
        ];

        DB::table('sales')->insert($salesData);

        // Product sales
        $productSalesData = [
            ['id' => 1, 'sale_id' => 1, 'product_id' => 1, 'quantity' => 1, 'base_price' => 25.00, 'total_price' => 25.00],
            ['id' => 2, 'sale_id' => 2, 'product_id' => 1, 'quantity' => 1, 'base_price' => 30.00, 'total_price' => 30.00],
            ['id' => 3, 'sale_id' => 3, 'product_id' => 2, 'quantity' => 1, 'base_price' => 45.00, 'total_price' => 45.00],
            ['id' => 4, 'sale_id' => 4, 'product_id' => 1, 'quantity' => 1, 'base_price' => 35.00, 'total_price' => 35.00],
            ['id' => 5, 'sale_id' => 5, 'product_id' => 2, 'quantity' => 1, 'base_price' => 50.00, 'total_price' => 50.00],
            ['id' => 6, 'sale_id' => 6, 'product_id' => 1, 'quantity' => 1, 'base_price' => 20.00, 'total_price' => 20.00],
        ];

        DB::table('product_sales')->insert($productSalesData);
    }

    /** @test */
    public function service_calculates_basic_kpis_structure()
    {
        $filters = ['stores' => [1, 2]];

        $result = $this->analyticsService->getKPIs($filters);

        // Verifica estrutura do resultado
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_sales', $result);
        $this->assertArrayHasKey('total_revenue', $result);
        $this->assertArrayHasKey('average_ticket', $result);
        
        // Verifica tipos dos valores
        $this->assertIsInt($result['total_sales']);
        $this->assertIsFloat($result['total_revenue']);
        $this->assertIsFloat($result['average_ticket']);
        
        // Verifica valores esperados baseados nos dados de teste (stores 1 e 2 = 5 sales totaling 185.00)
        $this->assertEquals(5, $result['total_sales']);
        $this->assertEquals(185.0, $result['total_revenue']);
        $this->assertEquals(37.0, $result['average_ticket']);
    }

    /** @test */
    public function service_calculates_sales_over_time_structure()
    {
        $filters = [];

        $result = $this->analyticsService->getSalesOverTime($filters, 'daily');

        // Verifica estrutura do resultado (formato de chart)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('sales_data', $result);
        $this->assertArrayHasKey('revenue_data', $result);
        $this->assertArrayHasKey('avg_ticket_data', $result);
        
        // Deve ter dados para 2 dias baseado nos dados de teste
        $this->assertCount(2, $result['labels']);
        $this->assertCount(2, $result['sales_data']);
        $this->assertCount(2, $result['revenue_data']);
        
        // Verifica que os dados não estão vazios
        $this->assertNotEmpty($result['labels']);
        $this->assertNotEmpty($result['sales_data']);
        $this->assertNotEmpty($result['revenue_data']);
    }

    /** @test */
    public function service_gets_top_products_structure()
    {
        $filters = [];

        $result = $this->analyticsService->getTopProducts($filters, 10);

        // Verifica estrutura do resultado
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        
        foreach ($result as $item) {
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('quantity', $item);
            $this->assertArrayHasKey('revenue', $item);
            $this->assertArrayHasKey('avg_price', $item);
            
            $this->assertIsString($item['name']);
            $this->assertIsInt($item['quantity']);
            $this->assertIsFloat($item['revenue']);
            $this->assertIsFloat($item['avg_price']);
        }
        
        // Verifica dados específicos baseados nos dados de teste
        $this->assertCount(2, $result); // Temos 2 produtos nos dados de teste
        
        // O Hambúrguer Clássico deve ser o mais vendido (4 vendas)
        $topProduct = $result[0];
        $this->assertEquals('Hambúrguer Clássico', $topProduct['name']);
        $this->assertEquals(4, $topProduct['quantity']);
        $this->assertEquals(110.0, $topProduct['revenue']); // 25+30+35+20
    }

    /** @test */
    public function service_handles_empty_filters()
    {
        $filters = ['stores' => [], 'channels' => []];

        $result = $this->analyticsService->getKPIs($filters);

        // Deve retornar estrutura válida com todos os dados (sem filtros)
        $this->assertIsArray($result);
        $this->assertEquals(6, $result['total_sales']); // Todas as 6 vendas
        $this->assertEquals(205.0, $result['total_revenue']); // Total de todas as vendas
        $this->assertEquals(34.17, round($result['average_ticket'], 2)); // Média arredondada
    }

    /** @test */
    public function service_validates_date_parameters()
    {
        // Testa se o serviço pode lidar com filtros de data vazios
        $result = $this->analyticsService->getKPIs([]);
        
        // Deve retornar dados válidos
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_sales', $result);
        $this->assertArrayHasKey('total_revenue', $result);
        $this->assertArrayHasKey('average_ticket', $result);
    }

    /** @test */
    public function service_applies_store_filters_correctly()
    {
        // Filtra apenas stores 1, 2 e 3
        $storeIds = [1, 2, 3];
        $filters = ['stores' => $storeIds];

        $result = $this->analyticsService->getKPIs($filters);

        // Deve retornar todas as 6 vendas pois todas são das stores 1, 2 e 3
        $this->assertEquals(6, $result['total_sales']);
        $this->assertEquals(205.0, $result['total_revenue']);
        $this->assertEquals(34.17, round($result['average_ticket'], 2));
    }

    /** @test */
    public function service_applies_channel_filters_correctly()
    {
        // Filtra apenas channels 1 e 2
        $channelIds = [1, 2];
        $filters = ['channels' => $channelIds];

        $result = $this->analyticsService->getKPIs($filters);

        // Deve retornar todas as 6 vendas pois todas são dos channels 1 e 2
        $this->assertEquals(6, $result['total_sales']);
        $this->assertEquals(205.0, $result['total_revenue']);
        $this->assertEquals(34.17, round($result['average_ticket'], 2));
    }
}