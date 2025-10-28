<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Sale;
use App\Models\Store;
use App\Models\Channel;
use App\Models\Product;
use App\Models\ProductSale;
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
        
        $this->store = Store::create([
            'name' => 'Test Store',
            'address' => '123 Test St',
            'city' => 'Test City',
            'state' => 'TS',
            'zip_code' => '12345',
            'phone' => '123-456-7890',
            'email' => 'test@store.com',
            'is_active' => true,
        ]);

        $this->channel = Channel::create([
            'name' => 'Test Channel',
            'type' => 'Online',
            'description' => 'Test channel for unit tests',
        ]);
    }

    public function test_sale_can_be_created()
    {
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'channel_id' => $this->channel->id,
            'sale_date' => Carbon::now()->toDateString(),
            'sale_time' => Carbon::now()->toTimeString(),
            'total_amount' => 99.99,
            'tax_amount' => 9.99,
            'discount_amount' => 5.00,
            'payment_method' => 'Credit Card',
            'sale_status_desc' => 'COMPLETED',
            'customer_type' => 'Regular',
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
            'sale_date' => Carbon::now()->toDateString(),
            'sale_time' => Carbon::now()->toTimeString(),
            'total_amount' => 50.00,
            'tax_amount' => 5.00,
            'discount_amount' => 0,
            'payment_method' => 'Cash',
            'sale_status_desc' => 'COMPLETED',
            'customer_type' => 'Regular',
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
            'sale_date' => Carbon::now()->toDateString(),
            'sale_time' => Carbon::now()->toTimeString(),
            'total_amount' => 75.00,
            'tax_amount' => 7.50,
            'discount_amount' => 0,
            'payment_method' => 'Debit Card',
            'sale_status_desc' => 'COMPLETED',
            'customer_type' => 'New',
        ]);

        $this->assertInstanceOf(Channel::class, $sale->channel);
        $this->assertEquals($this->channel->id, $sale->channel->id);
        $this->assertEquals('Test Channel', $sale->channel->name);
    }

    public function test_sale_has_many_product_sales()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'description' => 'Test product description',
            'price' => 25.50,
            'category' => 'Test Category',
            'is_active' => true,
        ]);

        $sale = Sale::create([
            'store_id' => $this->store->id,
            'channel_id' => $this->channel->id,
            'sale_date' => Carbon::now()->toDateString(),
            'sale_time' => Carbon::now()->toTimeString(),
            'total_amount' => 51.00,
            'tax_amount' => 5.10,
            'discount_amount' => 0,
            'payment_method' => 'Credit Card',
            'sale_status_desc' => 'COMPLETED',
            'customer_type' => 'Regular',
        ]);

        ProductSale::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 25.50,
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
            'sale_date' => Carbon::now()->toDateString(),
            'sale_time' => Carbon::now()->toTimeString(),
            'total_amount' => 100.00,
            'tax_amount' => 10.00,
            'discount_amount' => 0,
            'payment_method' => 'Credit Card',
            'sale_status_desc' => 'COMPLETED',
            'customer_type' => 'Regular',
        ]);

        // Create pending sale
        $pendingSale = Sale::create([
            'store_id' => $this->store->id,
            'channel_id' => $this->channel->id,
            'sale_date' => Carbon::now()->toDateString(),
            'sale_time' => Carbon::now()->toTimeString(),
            'total_amount' => 50.00,
            'tax_amount' => 5.00,
            'discount_amount' => 0,
            'payment_method' => 'Cash',
            'sale_status_desc' => 'PENDING',
            'customer_type' => 'Regular',
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
            'sale_date' => $startDate->addDay()->toDateString(),
            'sale_time' => Carbon::now()->toTimeString(),
            'total_amount' => 100.00,
            'tax_amount' => 10.00,
            'discount_amount' => 0,
            'payment_method' => 'Credit Card',
            'sale_status_desc' => 'COMPLETED',
            'customer_type' => 'Regular',
            'created_at' => $startDate->addDay(),
        ]);

        // Sale outside date range
        $saleOutsideRange = Sale::create([
            'store_id' => $this->store->id,
            'channel_id' => $this->channel->id,
            'sale_date' => $outsideDate->toDateString(),
            'sale_time' => Carbon::now()->toTimeString(),
            'total_amount' => 50.00,
            'tax_amount' => 5.00,
            'discount_amount' => 0,
            'payment_method' => 'Cash',
            'sale_status_desc' => 'COMPLETED',
            'customer_type' => 'Regular',
            'created_at' => $outsideDate,
        ]);

        $salesInRange = Sale::betweenDates($startDate->toDateString(), $endDate->toDateString())->get();

        $this->assertCount(1, $salesInRange);
        $this->assertEquals($saleInRange->id, $salesInRange->first()->id);
    }

    public function test_sale_calculates_net_amount()
    {
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'channel_id' => $this->channel->id,
            'sale_date' => Carbon::now()->toDateString(),
            'sale_time' => Carbon::now()->toTimeString(),
            'total_amount' => 100.00,
            'tax_amount' => 10.00,
            'discount_amount' => 15.00,
            'payment_method' => 'Credit Card',
            'sale_status_desc' => 'COMPLETED',
            'customer_type' => 'Regular',
        ]);

        // Net amount should be: total - tax - discount = 100 - 10 - 15 = 75
        $this->assertEquals(75.00, $sale->net_amount);
    }

    public function test_sale_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Try to create sale without required fields
        Sale::create([
            'total_amount' => 100.00,
            // Missing store_id, channel_id, sale_date, etc.
        ]);
    }

    public function test_sale_formats_dates_correctly()
    {
        $saleDate = Carbon::now()->subDays(1);
        
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'channel_id' => $this->channel->id,
            'sale_date' => $saleDate->toDateString(),
            'sale_time' => $saleDate->toTimeString(),
            'total_amount' => 100.00,
            'tax_amount' => 10.00,
            'discount_amount' => 0,
            'payment_method' => 'Credit Card',
            'sale_status_desc' => 'COMPLETED',
            'customer_type' => 'Regular',
        ]);

        $this->assertEquals($saleDate->toDateString(), $sale->sale_date);
        $this->assertEquals($saleDate->toTimeString(), $sale->sale_time);
    }
}