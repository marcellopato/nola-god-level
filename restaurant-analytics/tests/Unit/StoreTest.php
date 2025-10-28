<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Store;
use App\Models\Sale;
use App\Models\Channel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_can_be_created()
    {
        $store = Store::create([
            'name' => 'Downtown Restaurant',
            'address' => '123 Main St',
            'city' => 'San Francisco',
            'state' => 'CA',
            'zip_code' => '94102',
            'phone' => '415-555-0123',
            'email' => 'downtown@restaurant.com',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('stores', [
            'name' => 'Downtown Restaurant',
            'city' => 'San Francisco',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Store::class, $store);
        $this->assertEquals('Downtown Restaurant', $store->name);
        $this->assertTrue($store->is_active);
    }

    public function test_store_has_many_sales()
    {
        $store = Store::create([
            'name' => 'Test Store',
            'address' => '123 Test St',
            'city' => 'Test City',
            'state' => 'TS',
            'zip_code' => '12345',
            'phone' => '123-456-7890',
            'email' => 'test@store.com',
            'is_active' => true,
        ]);

        $channel = Channel::create([
            'name' => 'Test Channel',
            'type' => 'Online',
            'description' => 'Test channel',
        ]);

        // Create sales for this store
        Sale::create([
            'store_id' => $store->id,
            'channel_id' => $channel->id,
            'sale_date' => Carbon::now()->toDateString(),
            'sale_time' => Carbon::now()->toTimeString(),
            'total_amount' => 100.00,
            'tax_amount' => 10.00,
            'discount_amount' => 0,
            'payment_method' => 'Credit Card',
            'sale_status_desc' => 'COMPLETED',
            'customer_type' => 'Regular',
        ]);

        Sale::create([
            'store_id' => $store->id,
            'channel_id' => $channel->id,
            'sale_date' => Carbon::now()->toDateString(),
            'sale_time' => Carbon::now()->toTimeString(),
            'total_amount' => 75.50,
            'tax_amount' => 7.55,
            'discount_amount' => 0,
            'payment_method' => 'Cash',
            'sale_status_desc' => 'COMPLETED',
            'customer_type' => 'New',
        ]);

        $this->assertCount(2, $store->sales);
        $this->assertEquals(100.00, $store->sales->first()->total_amount);
    }

    public function test_store_scope_active()
    {
        // Create active store
        $activeStore = Store::create([
            'name' => 'Active Store',
            'address' => '123 Active St',
            'city' => 'Active City',
            'state' => 'AC',
            'zip_code' => '12345',
            'phone' => '123-456-7890',
            'email' => 'active@store.com',
            'is_active' => true,
        ]);

        // Create inactive store
        $inactiveStore = Store::create([
            'name' => 'Inactive Store',
            'address' => '456 Inactive St',
            'city' => 'Inactive City',
            'state' => 'IN',
            'zip_code' => '54321',
            'phone' => '098-765-4321',
            'email' => 'inactive@store.com',
            'is_active' => false,
        ]);

        $activeStores = Store::active()->get();

        $this->assertCount(1, $activeStores);
        $this->assertEquals($activeStore->id, $activeStores->first()->id);
        $this->assertTrue($activeStores->first()->is_active);
    }

    public function test_store_full_address_attribute()
    {
        $store = Store::create([
            'name' => 'Address Test Store',
            'address' => '789 Full Address Blvd',
            'city' => 'Address City',
            'state' => 'AD',
            'zip_code' => '90210',
            'phone' => '555-123-4567',
            'email' => 'address@store.com',
            'is_active' => true,
        ]);

        $expectedFullAddress = '789 Full Address Blvd, Address City, AD 90210';
        $this->assertEquals($expectedFullAddress, $store->full_address);
    }

    public function test_store_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Try to create store without required fields
        Store::create([
            'address' => '123 Test St',
            // Missing name and other required fields
        ]);
    }

    public function test_store_email_can_be_unique()
    {
        Store::create([
            'name' => 'First Store',
            'address' => '123 First St',
            'city' => 'First City',
            'state' => 'FS',
            'zip_code' => '12345',
            'phone' => '123-456-7890',
            'email' => 'unique@store.com',
            'is_active' => true,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        // Try to create another store with the same email
        Store::create([
            'name' => 'Second Store',
            'address' => '456 Second St',
            'city' => 'Second City',
            'state' => 'SC',
            'zip_code' => '54321',
            'phone' => '098-765-4321',
            'email' => 'unique@store.com', // Same email
            'is_active' => true,
        ]);
    }

    public function test_store_can_be_soft_deleted()
    {
        $store = Store::create([
            'name' => 'Deletable Store',
            'address' => '999 Delete St',
            'city' => 'Delete City',
            'state' => 'DL',
            'zip_code' => '99999',
            'phone' => '999-999-9999',
            'email' => 'delete@store.com',
            'is_active' => true,
        ]);

        $storeId = $store->id;

        // Soft delete the store (set is_active to false)
        $store->update(['is_active' => false]);

        $this->assertDatabaseHas('stores', [
            'id' => $storeId,
            'is_active' => false,
        ]);

        // Verify it's not included in active scope
        $activeStores = Store::active()->get();
        $this->assertFalse($activeStores->contains('id', $storeId));
    }

    public function test_store_phone_format_validation()
    {
        $store = Store::create([
            'name' => 'Phone Test Store',
            'address' => '123 Phone St',
            'city' => 'Phone City',
            'state' => 'PH',
            'zip_code' => '12345',
            'phone' => '(555) 123-4567',
            'email' => 'phone@store.com',
            'is_active' => true,
        ]);

        $this->assertEquals('(555) 123-4567', $store->phone);
    }

    public function test_store_can_calculate_total_sales()
    {
        $store = Store::create([
            'name' => 'Sales Test Store',
            'address' => '123 Sales St',
            'city' => 'Sales City',
            'state' => 'SL',
            'zip_code' => '12345',
            'phone' => '123-456-7890',
            'email' => 'sales@store.com',
            'is_active' => true,
        ]);

        $channel = Channel::create([
            'name' => 'Test Channel',
            'type' => 'Online',
            'description' => 'Test channel',
        ]);

        // Create completed sales
        Sale::create([
            'store_id' => $store->id,
            'channel_id' => $channel->id,
            'sale_date' => Carbon::now()->toDateString(),
            'sale_time' => Carbon::now()->toTimeString(),
            'total_amount' => 100.00,
            'tax_amount' => 10.00,
            'discount_amount' => 0,
            'payment_method' => 'Credit Card',
            'sale_status_desc' => 'COMPLETED',
            'customer_type' => 'Regular',
        ]);

        Sale::create([
            'store_id' => $store->id,
            'channel_id' => $channel->id,
            'sale_date' => Carbon::now()->toDateString(),
            'sale_time' => Carbon::now()->toTimeString(),
            'total_amount' => 50.00,
            'tax_amount' => 5.00,
            'discount_amount' => 0,
            'payment_method' => 'Cash',
            'sale_status_desc' => 'COMPLETED',
            'customer_type' => 'Regular',
        ]);

        // Create pending sale (should not be counted)
        Sale::create([
            'store_id' => $store->id,
            'channel_id' => $channel->id,
            'sale_date' => Carbon::now()->toDateString(),
            'sale_time' => Carbon::now()->toTimeString(),
            'total_amount' => 75.00,
            'tax_amount' => 7.50,
            'discount_amount' => 0,
            'payment_method' => 'Credit Card',
            'sale_status_desc' => 'PENDING',
            'customer_type' => 'Regular',
        ]);

        // Test total sales count (only completed)
        $completedSales = $store->sales()->completed()->count();
        $this->assertEquals(2, $completedSales);

        // Test total revenue (only completed)
        $totalRevenue = $store->sales()->completed()->sum('total_amount');
        $this->assertEquals(150.00, $totalRevenue);
    }

    public function test_store_name_is_searchable()
    {
        Store::create([
            'name' => 'Downtown Pizza Palace',
            'address' => '123 Downtown St',
            'city' => 'Pizza City',
            'state' => 'PC',
            'zip_code' => '12345',
            'phone' => '123-456-7890',
            'email' => 'pizza@downtown.com',
            'is_active' => true,
        ]);

        Store::create([
            'name' => 'Uptown Burger Joint',
            'address' => '456 Uptown Ave',
            'city' => 'Burger City',
            'state' => 'BC',
            'zip_code' => '54321',
            'phone' => '098-765-4321',
            'email' => 'burger@uptown.com',
            'is_active' => true,
        ]);

        $pizzaStores = Store::where('name', 'like', '%Pizza%')->get();
        $this->assertCount(1, $pizzaStores);
        $this->assertEquals('Downtown Pizza Palace', $pizzaStores->first()->name);

        $burgerStores = Store::where('name', 'like', '%Burger%')->get();
        $this->assertCount(1, $burgerStores);
        $this->assertEquals('Uptown Burger Joint', $burgerStores->first()->name);
    }
}