<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Sale;
use App\Models\Store;
use App\Models\Channel;
use App\Models\Product;
use Carbon\Carbon;
use Mockery;
use Illuminate\Support\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class AnalyticsServiceSimpleTest extends TestCase
{
    private AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function service_calculates_basic_kpis_structure()
    {
        // Mock do modelo Sale com dados de exemplo
        $saleMock = Mockery::mock('alias:' . Sale::class);
        
        // Mock da query builder chain
        $builderMock = Mockery::mock(EloquentBuilder::class);
        
        $saleMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('whereBetween')->with('created_at', Mockery::any())->andReturnSelf();
        $builderMock->shouldReceive('where')->andReturnSelf();
        $builderMock->shouldReceive('whereIn')->andReturnSelf();
        
        // Mock dos resultados das queries
        $builderMock->shouldReceive('count')->andReturn(150);
        $builderMock->shouldReceive('sum')->with('total_amount')->andReturn(25000.50);
        $builderMock->shouldReceive('avg')->with('total_amount')->andReturn(166.67);

        // Mock do Store para filtros
        $storeMock = Mockery::mock('alias:' . Store::class);
        $storeMock->shouldReceive('pluck')->with('id', 'name')->andReturn(collect([1 => 'Loja 1', 2 => 'Loja 2']));

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
    }

    /** @test */
    public function service_calculates_sales_over_time_structure()
    {
        $saleMock = Mockery::mock('alias:' . Sale::class);
        $builderMock = Mockery::mock(EloquentBuilder::class);
        
        $saleMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('whereBetween')->andReturnSelf();
        $builderMock->shouldReceive('where')->andReturnSelf();
        $builderMock->shouldReceive('whereIn')->andReturnSelf();
        $builderMock->shouldReceive('selectRaw')->andReturnSelf();
        $builderMock->shouldReceive('groupBy')->andReturnSelf();
        $builderMock->shouldReceive('orderBy')->andReturnSelf();
        
        // Dados simulados de vendas por período
        $mockData = collect([
            (object)['period' => '2024-10-01', 'total_sales' => 10, 'total_revenue' => 1500.00],
            (object)['period' => '2024-10-02', 'total_sales' => 15, 'total_revenue' => 2250.00],
        ]);
        
        $builderMock->shouldReceive('get')->andReturn($mockData);

        $filters = [];

        $result = $this->analyticsService->getSalesOverTime($filters, 'daily');

        // Verifica estrutura do resultado
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        
        foreach ($result as $item) {
            $this->assertArrayHasKey('period', $item);
            $this->assertArrayHasKey('total_sales', $item);
            $this->assertArrayHasKey('total_revenue', $item);
        }
    }

    /** @test */
    public function service_gets_top_products_structure()
    {
        $saleMock = Mockery::mock('alias:' . Sale::class);
        $builderMock = Mockery::mock(EloquentBuilder::class);
        
        $saleMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('join')->andReturnSelf();
        $builderMock->shouldReceive('whereBetween')->andReturnSelf();
        $builderMock->shouldReceive('where')->andReturnSelf();
        $builderMock->shouldReceive('whereIn')->andReturnSelf();
        $builderMock->shouldReceive('selectRaw')->andReturnSelf();
        $builderMock->shouldReceive('groupBy')->andReturnSelf();
        $builderMock->shouldReceive('orderBy')->andReturnSelf();
        $builderMock->shouldReceive('limit')->andReturnSelf();
        
        // Dados simulados de produtos mais vendidos
        $mockData = collect([
            (object)[
                'product_name' => 'Hambúrguer Clássico',
                'total_quantity' => 85,
                'total_revenue' => 2125.00
            ],
            (object)[
                'product_name' => 'Pizza Margherita',
                'total_quantity' => 62,
                'total_revenue' => 1860.00
            ],
        ]);
        
        $builderMock->shouldReceive('get')->andReturn($mockData);

        $filters = [];

        $result = $this->analyticsService->getTopProducts($filters, 10);

        // Verifica estrutura do resultado
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        
        foreach ($result as $item) {
            $this->assertArrayHasKey('product_name', $item);
            $this->assertArrayHasKey('total_quantity', $item);
            $this->assertArrayHasKey('total_revenue', $item);
            
            $this->assertIsString($item['product_name']);
            $this->assertIsInt($item['total_quantity']);
            $this->assertIsFloat($item['total_revenue']);
        }
    }

    /** @test */
    public function service_handles_empty_filters()
    {
        $saleMock = Mockery::mock('alias:' . Sale::class);
        $builderMock = Mockery::mock(EloquentBuilder::class);
        
        $saleMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('whereBetween')->andReturnSelf();
        
        // Não deve aplicar filtros de store ou channel quando arrays vazios
        $builderMock->shouldNotReceive('whereIn');
        
        $builderMock->shouldReceive('count')->andReturn(0);
        $builderMock->shouldReceive('sum')->andReturn(0);
        $builderMock->shouldReceive('avg')->andReturn(0);

        // Mock do Store
        $storeMock = Mockery::mock('alias:' . Store::class);
        $storeMock->shouldReceive('pluck')->andReturn(collect([]));

        $filters = ['stores' => [], 'channels' => []];

        $result = $this->analyticsService->getKPIs($filters);

        // Deve retornar estrutura válida mesmo sem dados
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_sales']);
        $this->assertEquals(0.0, $result['total_revenue']);
        $this->assertEquals(0.0, $result['average_ticket']);
    }

    /** @test */
    public function service_validates_date_parameters()
    {
        $saleMock = Mockery::mock('alias:' . Sale::class);
        $builderMock = Mockery::mock(EloquentBuilder::class);
        
        $saleMock->shouldReceive('query')->andReturn($builderMock);
        
        // Testa se o serviço pode lidar com filtros de data (através dos filtros)
        $builderMock->shouldReceive('count')->andReturn(0);
        $builderMock->shouldReceive('sum')->andReturn(0);
        $builderMock->shouldReceive('avg')->andReturn(0);

        $storeMock = Mockery::mock('alias:' . Store::class);
        $storeMock->shouldReceive('pluck')->andReturn(collect([]));

        $this->analyticsService->getKPIs([]);
    }

    /** @test */
    public function service_applies_store_filters_correctly()
    {
        $saleMock = Mockery::mock('alias:' . Sale::class);
        $builderMock = Mockery::mock(EloquentBuilder::class);
        
        $saleMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('whereBetween')->andReturnSelf();
        
        // Verifica se o filtro de stores é aplicado corretamente
        $storeIds = [1, 2, 3];
        $builderMock->shouldReceive('whereIn')
            ->with('store_id', $storeIds)
            ->once()
            ->andReturnSelf();
            
        $builderMock->shouldReceive('count')->andReturn(50);
        $builderMock->shouldReceive('sum')->andReturn(1000);
        $builderMock->shouldReceive('avg')->andReturn(20);

        $storeMock = Mockery::mock('alias:' . Store::class);
        $storeMock->shouldReceive('pluck')->andReturn(collect([
            1 => 'Store 1', 2 => 'Store 2', 3 => 'Store 3'
        ]));

        $filters = ['stores' => $storeIds];

        $result = $this->analyticsService->getKPIs($filters);

        $this->assertEquals(50, $result['total_sales']);
        $this->assertEquals(1000.0, $result['total_revenue']);
        $this->assertEquals(20.0, $result['average_ticket']);
    }

    /** @test */
    public function service_applies_channel_filters_correctly()
    {
        $saleMock = Mockery::mock('alias:' . Sale::class);
        $builderMock = Mockery::mock(EloquentBuilder::class);
        
        $saleMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('whereBetween')->andReturnSelf();
        
        // Verifica se o filtro de channels é aplicado corretamente
        $channelIds = [1, 2];
        $builderMock->shouldReceive('whereIn')
            ->with('channel_id', $channelIds)
            ->once()
            ->andReturnSelf();
            
        $builderMock->shouldReceive('count')->andReturn(30);
        $builderMock->shouldReceive('sum')->andReturn(750);
        $builderMock->shouldReceive('avg')->andReturn(25);

        $storeMock = Mockery::mock('alias:' . Store::class);
        $storeMock->shouldReceive('pluck')->andReturn(collect([]));

        $filters = ['channels' => $channelIds];

        $result = $this->analyticsService->getKPIs($filters);

        $this->assertEquals(30, $result['total_sales']);
        $this->assertEquals(750.0, $result['total_revenue']);
        $this->assertEquals(25.0, $result['average_ticket']);
    }
}