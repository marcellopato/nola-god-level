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

    protected function setUp(): void
    {
        parent::setUp();
        
        // Load domain schema if needed
        $this->loadSchemaIfNeeded();
    }

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
        // Create a store
        $store = Store::create([
            'name' => 'Test Store',
            'address_street' => 'Rua Teste',
            'address_number' => '123',
            'district' => 'Centro',
            'city' => 'Test City',
            'state' => 'TS',
            'is_active' => true,
        ]);

        $channel = Channel::create([
            'name' => 'Test Channel',
            'type' => 'P',
            'description' => 'Test channel',
        ]);

        // Create sales for this store
        Sale::create([
            'store_id' => $store->id,
            'channel_id' => $channel->id,
            'created_at' => Carbon::now(),
            'total_amount' => 100.00,
            'total_amount_items' => 90.00,
            'total_discount' => 0,
            'sale_status_desc' => 'COMPLETED',
        ]);

        Sale::create([
            'store_id' => $store->id,
            'channel_id' => $channel->id,
            'created_at' => Carbon::now(),
            'total_amount' => 75.50,
            'total_amount_items' => 75.50,
            'total_discount' => 0,
            'sale_status_desc' => 'COMPLETED',
        ]);

        $this->assertCount(2, $store->sales);
        $this->assertEquals(100.00, $store->sales->first()->total_amount);
    }

    public function test_store_scope_active()
    {
        // Create active store
        $activeStore = Store::create([
            'name' => 'Active Store',
            'address_street' => 'Rua Ativa',
            'address_number' => '123',
            'district' => 'Centro',
            'city' => 'Active City',
            'state' => 'AC',
            'is_active' => true,
        ]);

        // Create inactive store
        $inactiveStore = Store::create([
            'name' => 'Inactive Store',
            'address_street' => 'Rua Inativa',
            'address_number' => '456',
            'district' => 'Bairro',
            'city' => 'Inactive City',
            'state' => 'IN',
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
            'address_street' => 'Rua Endereço Completo',
            'address_number' => '789',
            'district' => 'Centro',
            'city' => 'Address City',
            'state' => 'AD',
            'is_active' => true,
        ]);

        $expectedFullAddress = 'Rua Endereço Completo, 789 - Centro, Address City/AD';
        $this->assertEquals($expectedFullAddress, $store->full_address);
    }

    public function test_store_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Try to create store without required fields
        Store::create([
            'address_street' => 'Rua Teste',
            // Missing name and other required fields
        ]);
    }

    public function test_store_name_can_be_duplicated()
    {
        Store::create([
            'name' => 'Duplicate Store',
            'address_street' => 'Rua Primeira',
            'address_number' => '123',
            'district' => 'Centro',
            'city' => 'First City',
            'state' => 'FS',
            'is_active' => true,
        ]);

        // Store names can be duplicated - no exception expected
        $secondStore = Store::create([
            'name' => 'Duplicate Store',
            'address_street' => 'Rua Segunda',
            'address_number' => '456',
            'district' => 'Bairro',
            'city' => 'Second City',
            'state' => 'SC',
            'is_active' => true,
        ]);

        $this->assertNotNull($secondStore);
        $this->assertEquals('Duplicate Store', $secondStore->name);
    }

    public function test_store_can_be_soft_deleted()
    {
        $store = Store::create([
            'name' => 'Deletable Store',
            'address_street' => 'Rua Deletável',
            'address_number' => '999',
            'district' => 'Centro',
            'city' => 'Delete City',
            'state' => 'DL',
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

    public function test_store_can_have_coordinates()
    {
        $store = Store::create([
            'name' => 'Location Test Store',
            'address_street' => 'Rua Coordenadas',
            'address_number' => '123',
            'district' => 'Centro',
            'city' => 'Location City',
            'state' => 'LC',
            'latitude' => -23.550520,
            'longitude' => -46.633309,
            'is_active' => true,
        ]);

        $this->assertEquals(-23.550520, $store->latitude);
        $this->assertEquals(-46.633309, $store->longitude);
    }

    public function test_store_can_calculate_total_sales()
    {
        $store = Store::create([
            'name' => 'Sales Test Store',
            'address_street' => 'Rua Vendas',
            'address_number' => '123',
            'district' => 'Centro',
            'city' => 'Sales City',
            'state' => 'SL',
            'is_active' => true,
        ]);

        $channel = Channel::create([
            'name' => 'Test Channel',
            'type' => 'P',
            'description' => 'Test channel',
        ]);

        // Create completed sales
        Sale::create([
            'store_id' => $store->id,
            'channel_id' => $channel->id,
            'created_at' => Carbon::now(),
            'total_amount' => 100.00,
            'total_amount_items' => 90.00,
            'total_discount' => 0,
            'sale_status_desc' => 'COMPLETED',
        ]);

        Sale::create([
            'store_id' => $store->id,
            'channel_id' => $channel->id,
            'created_at' => Carbon::now(),
            'total_amount' => 50.00,
            'total_amount_items' => 50.00,
            'total_discount' => 0,
            'sale_status_desc' => 'COMPLETED',
        ]);

        // Create cancelled sale (should not be counted)
        Sale::create([
            'store_id' => $store->id,
            'channel_id' => $channel->id,
            'created_at' => Carbon::now(),
            'total_amount' => 75.00,
            'total_amount_items' => 75.00,
            'total_discount' => 0,
            'sale_status_desc' => 'CANCELLED',
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
            'address_street' => 'Rua Downtown',
            'address_number' => '123',
            'district' => 'Centro',
            'city' => 'Pizza City',
            'state' => 'PC',
            'is_active' => true,
        ]);

        Store::create([
            'name' => 'Uptown Burger Joint',
            'address_street' => 'Av Uptown',
            'address_number' => '456',
            'district' => 'Norte',
            'city' => 'Burger City',
            'state' => 'BC',
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