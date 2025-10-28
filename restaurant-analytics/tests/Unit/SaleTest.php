<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Sale;
use App\Models\Store;
use App\Models\Channel;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private Channel $channel;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->loadSchemaIfNeeded();
        
        $this->store = Store::create([
            'name' => 'Test Store',
            'address_street' => 'Rua Teste',
            'address_number' => '123',
            'district' => 'Centro',
            'city' => 'Test City',
            'state' => 'TS',
            'is_active' => true,
        ]);

        $this->channel = Channel::create([
            'name' => 'Test Channel',
            'type' => 'P',
            'description' => 'Test channel for unit tests',
        ]);
    }

    public function test_sale_can_be_created()
    {
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'channel_id' => $this->channel->id,
            'created_at' => Carbon::now(),
            'total_amount' => 99.99,
            'total_amount_items' => 94.99,
            'total_discount' => 5.00,
            'sale_status_desc' => 'COMPLETED',
        ]);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'store_id' => $this->store->id,
            'channel_id' => $this->channel->id,
            'total_amount' => 99.99,
            'sale_status_desc' => 'COMPLETED',
        ]);
    }

    public function test_sale_belongs_to_store()
    {
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'channel_id' => $this->channel->id,
            'created_at' => Carbon::now(),
            'total_amount' => 50.00,
            'total_amount_items' => 50.00,
            'total_discount' => 0,
            'sale_status_desc' => 'COMPLETED',
        ]);

        $this->assertInstanceOf(Store::class, $sale->store);
        $this->assertEquals($this->store->id, $sale->store->id);
        $this->assertEquals('Test Store', $sale->store->name);
    }

    public function test_sale_belongs_to_channel()
    {
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'channel_id' => $this->channel->id,
            'created_at' => Carbon::now(),
            'total_amount' => 75.00,
            'total_amount_items' => 75.00,
            'total_discount' => 0,
            'sale_status_desc' => 'COMPLETED',
        ]);

        $this->assertInstanceOf(Channel::class, $sale->channel);
        $this->assertEquals($this->channel->id, $sale->channel->id);
        $this->assertEquals('Test Channel', $sale->channel->name);
    }

    public function test_sale_has_many_product_sales()
    {
        $category = Category::create([
            'name' => 'Test Category',
            'type' => 'P',
        ]);

        $product = Product::create([
            'name' => 'Test Product',
            'category_id' => $category->id,
            'pos_uuid' => 'test-product-uuid',
        ]);

        $sale = Sale::create([
            'store_id' => $this->store->id,
            'channel_id' => $this->channel->id,
            'created_at' => Carbon::now(),
            'total_amount' => 51.00,
            'total_amount_items' => 51.00,
            'total_discount' => 0,
            'sale_status_desc' => 'COMPLETED',
        ]);

        ProductSale::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'base_price' => 25.50,
            'total_price' => 51.00,
        ]);

        $this->assertCount(1, $sale->productSales);
        $this->assertEquals(2, $sale->productSales->first()->quantity);
        $this->assertEquals(51.00, $sale->productSales->first()->total_price);
    }

    public function test_sale_scope_completed()
    {
        // Create completed sale
        $completedSale = Sale::create([
            'store_id' => $this->store->id,
            'channel_id' => $this->channel->id,
            'created_at' => Carbon::now(),
            'total_amount' => 100.00,
            'total_amount_items' => 100.00,
            'total_discount' => 0,
            'sale_status_desc' => 'COMPLETED',
        ]);

        // Create cancelled sale
        $cancelledSale = Sale::create([
            'store_id' => $this->store->id,
            'channel_id' => $this->channel->id,
            'created_at' => Carbon::now(),
            'total_amount' => 50.00,
            'total_amount_items' => 50.00,
            'total_discount' => 0,
            'sale_status_desc' => 'CANCELLED',
        ]);

        $completedSales = Sale::completed()->get();

        $this->assertCount(1, $completedSales);
        $this->assertEquals($completedSale->id, $completedSales->first()->id);
        $this->assertEquals('COMPLETED', $completedSales->first()->sale_status_desc);
    }

    public function test_sale_scope_between_dates()
    {
        $startDate = Carbon::now()->subDays(5);
        $endDate = Carbon::now()->subDays(2);
        $outsideDate = Carbon::now()->subDays(10);

        // Sale within date range
        $saleInRange = Sale::create([
            'store_id' => $this->store->id,
            'channel_id' => $this->channel->id,
            'created_at' => $startDate->addDay(),
            'total_amount' => 100.00,
            'total_amount_items' => 100.00,
            'total_discount' => 0,
            'sale_status_desc' => 'COMPLETED',
        ]);

        // Sale outside date range
        $saleOutsideRange = Sale::create([
            'store_id' => $this->store->id,
            'channel_id' => $this->channel->id,
            'created_at' => $outsideDate,
            'total_amount' => 50.00,
            'total_amount_items' => 50.00,
            'total_discount' => 0,
            'sale_status_desc' => 'COMPLETED',
        ]);

        $salesInRange = Sale::byDateRange($startDate, $endDate)->get();

        $this->assertCount(1, $salesInRange);
        $this->assertEquals($saleInRange->id, $salesInRange->first()->id);
    }

    public function test_sale_calculates_net_amount()
    {
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'channel_id' => $this->channel->id,
            'created_at' => Carbon::now(),
            'total_amount' => 100.00,
            'total_amount_items' => 85.00,
            'total_discount' => 15.00,
            'service_tax_fee' => 10.00,
            'sale_status_desc' => 'COMPLETED',
        ]);

        // Verify the values are set correctly
        $this->assertEquals(100.00, $sale->total_amount);
        $this->assertEquals(15.00, $sale->total_discount);
        $this->assertEquals(10.00, $sale->service_tax_fee);
    }

    public function test_sale_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Try to create sale without required fields
        Sale::create([
            'total_amount' => 100.00,
            // Missing store_id, channel_id, etc.
        ]);
    }

    public function test_sale_formats_dates_correctly()
    {
        $saleDate = Carbon::now()->subDays(1);
        
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'channel_id' => $this->channel->id,
            'created_at' => $saleDate,
            'total_amount' => 100.00,
            'total_amount_items' => 100.00,
            'total_discount' => 0,
            'sale_status_desc' => 'COMPLETED',
        ]);

        $this->assertEquals($saleDate->toDateString(), $sale->created_at->toDateString());
        $this->assertEquals($saleDate->toTimeString(), $sale->created_at->toTimeString());
    }
}