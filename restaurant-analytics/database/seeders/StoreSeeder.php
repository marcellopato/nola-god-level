<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Store;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Primeiro precisamos criar brands
        $brand = \App\Models\Brand::create([
            'name' => 'Maria\'s Burger',
            'created_at' => now()->subMonths(12)
        ]);

        $stores = [
            [
                'brand_id' => $brand->id,
                'name' => 'Maria\'s Burger Centro',
                'city' => 'São Paulo',
                'state' => 'SP',
                'district' => 'Centro',
                'address_street' => 'Rua das Flores',
                'address_number' => 123,
                'latitude' => -23.550520,
                'longitude' => -46.633309,
                'is_active' => true,
                'is_own' => true,
                'creation_date' => now()->subMonths(12)->toDateString(),
                'created_at' => now()->subMonths(12)
            ],
            [
                'name' => 'Maria\'s Burger Shopping',
                'address' => 'Av. Paulista, 2000 - Loja 45',
                'city' => 'São Paulo',
                'state' => 'SP',
                'country' => 'Brasil',
                'postal_code' => '01310-100',
                'phone' => '(11) 99999-5678',
                'email' => 'shopping@mariasburger.com.br',
                'is_active' => true,
                'store_type' => 'shopping',
                'opening_hours' => '{"todos": "10:00-22:00"}',
                'manager_name' => 'Ana Costa',
                'created_at' => now()->subMonths(8),
                'updated_at' => now()
            ],
            [
                'name' => 'Maria\'s Burger Vila Madalena',
                'address' => 'Rua Aspicuelta, 456',
                'city' => 'São Paulo',
                'state' => 'SP',
                'country' => 'Brasil',
                'postal_code' => '05433-010',
                'phone' => '(11) 99999-9012',
                'email' => 'vilamadalena@mariasburger.com.br',
                'is_active' => true,
                'store_type' => 'bairro',
                'opening_hours' => '{"ter-dom": "18:00-01:00"}',
                'manager_name' => 'Carlos Mendes',
                'created_at' => now()->subMonths(6),
                'updated_at' => now()
            ],
            [
                'name' => 'Maria\'s Burger Delivery Center',
                'address' => 'Rua dos Logistas, 789',
                'city' => 'São Paulo',
                'state' => 'SP',
                'country' => 'Brasil',
                'postal_code' => '03456-789',
                'phone' => '(11) 99999-3456',
                'email' => 'delivery@mariasburger.com.br',
                'is_active' => true,
                'store_type' => 'dark_kitchen',
                'opening_hours' => '{"todos": "17:00-02:00"}',
                'manager_name' => 'Fernanda Lima',
                'created_at' => now()->subMonths(3),
                'updated_at' => now()
            ]
        ];

        foreach ($stores as $store) {
            Store::create($store);
        }
    }
}
