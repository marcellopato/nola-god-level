<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Channel;

class ChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $channels = [
            [
                'name' => 'Balcão',
                'type' => 'presencial',
                'description' => 'Vendas no balcão da loja',
                'commission_rate' => 0.00,
                'is_active' => true,
                'created_at' => now()->subMonths(12),
                'updated_at' => now()
            ],
            [
                'name' => 'iFood',
                'type' => 'delivery',
                'description' => 'Pedidos via aplicativo iFood',
                'commission_rate' => 0.25,
                'is_active' => true,
                'created_at' => now()->subMonths(12),
                'updated_at' => now()
            ],
            [
                'name' => 'Uber Eats',
                'type' => 'delivery',
                'description' => 'Pedidos via aplicativo Uber Eats',
                'commission_rate' => 0.30,
                'is_active' => true,
                'created_at' => now()->subMonths(10),
                'updated_at' => now()
            ],
            [
                'name' => 'Rappi',
                'type' => 'delivery',
                'description' => 'Pedidos via aplicativo Rappi',
                'commission_rate' => 0.28,
                'is_active' => true,
                'created_at' => now()->subMonths(8),
                'updated_at' => now()
            ],
            [
                'name' => 'App Próprio',
                'type' => 'delivery',
                'description' => 'Aplicativo próprio Maria\'s Burger',
                'commission_rate' => 0.05,
                'is_active' => true,
                'created_at' => now()->subMonths(6),
                'updated_at' => now()
            ],
            [
                'name' => 'WhatsApp',
                'type' => 'delivery',
                'description' => 'Pedidos via WhatsApp',
                'commission_rate' => 0.02,
                'is_active' => true,
                'created_at' => now()->subMonths(12),
                'updated_at' => now()
            ],
            [
                'name' => 'Telefone',
                'type' => 'delivery',
                'description' => 'Pedidos por telefone',
                'commission_rate' => 0.00,
                'is_active' => true,
                'created_at' => now()->subMonths(12),
                'updated_at' => now()
            ]
        ];

        foreach ($channels as $channel) {
            Channel::create($channel);
        }
    }
}
