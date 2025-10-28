<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all()->keyBy('name');

        $products = [
            // Burgers
            [
                'name' => 'Maria\'s Classic',
                'description' => 'Hambúrguer artesanal 180g, queijo, alface, tomate, cebola e molho especial',
                'category_id' => $categories['Burgers']->id,
                'price' => 24.90,
                'cost' => 12.50,
                'sku' => 'MB-001',
                'is_active' => true,
                'preparation_time' => 8,
                'calories' => 650,
                'created_at' => now()->subMonths(12),
                'updated_at' => now()
            ],
            [
                'name' => 'Maria\'s Bacon',
                'description' => 'Hambúrguer 180g, bacon crocante, queijo cheddar, alface, tomate e molho BBQ',
                'category_id' => $categories['Burgers']->id,
                'price' => 29.90,
                'cost' => 15.20,
                'sku' => 'MB-002',
                'is_active' => true,
                'preparation_time' => 10,
                'calories' => 780,
                'created_at' => now()->subMonths(12),
                'updated_at' => now()
            ],
            [
                'name' => 'Veggie Burger',
                'description' => 'Hambúrguer de grão-de-bico e quinoa, queijo vegano, rúcula e molho tahine',
                'category_id' => $categories['Burgers']->id,
                'price' => 26.90,
                'cost' => 13.80,
                'sku' => 'MB-003',
                'is_active' => true,
                'preparation_time' => 8,
                'calories' => 520,
                'created_at' => now()->subMonths(8),
                'updated_at' => now()
            ],
            [
                'name' => 'Maria\'s Supreme',
                'description' => 'Duplo hambúrguer 180g cada, duplo queijo, bacon, cebola caramelizada',
                'category_id' => $categories['Burgers']->id,
                'price' => 39.90,
                'cost' => 22.00,
                'sku' => 'MB-004',
                'is_active' => true,
                'preparation_time' => 12,
                'calories' => 1200,
                'created_at' => now()->subMonths(10),
                'updated_at' => now()
            ],
            [
                'name' => 'Fish Burger',
                'description' => 'Filé de salmão grelhado, cream cheese, rúcula e molho de ervas',
                'category_id' => $categories['Burgers']->id,
                'price' => 32.90,
                'cost' => 18.50,
                'sku' => 'MB-005',
                'is_active' => true,
                'preparation_time' => 10,
                'calories' => 580,
                'created_at' => now()->subMonths(6),
                'updated_at' => now()
            ],
            // Acompanhamentos
            [
                'name' => 'Batata Frita Clássica',
                'description' => 'Batatas fritas crocantes temperadas com sal especial',
                'category_id' => $categories['Acompanhamentos']->id,
                'price' => 12.90,
                'cost' => 4.50,
                'sku' => 'AC-001',
                'is_active' => true,
                'preparation_time' => 5,
                'calories' => 350,
                'created_at' => now()->subMonths(12),
                'updated_at' => now()
            ],
            [
                'name' => 'Batata com Cheddar e Bacon',
                'description' => 'Batatas fritas cobertas com cheddar derretido e bacon crocante',
                'category_id' => $categories['Acompanhamentos']->id,
                'price' => 18.90,
                'cost' => 7.80,
                'sku' => 'AC-002',
                'is_active' => true,
                'preparation_time' => 7,
                'calories' => 520,
                'created_at' => now()->subMonths(12),
                'updated_at' => now()
            ],
            [
                'name' => 'Onion Rings',
                'description' => 'Anéis de cebola empanados e fritos, com molho especial',
                'category_id' => $categories['Acompanhamentos']->id,
                'price' => 15.90,
                'cost' => 6.20,
                'sku' => 'AC-003',
                'is_active' => true,
                'preparation_time' => 6,
                'calories' => 420,
                'created_at' => now()->subMonths(10),
                'updated_at' => now()
            ],
            [
                'name' => 'Nuggets de Frango',
                'description' => '8 unidades de nuggets crocantes com molho à escolha',
                'category_id' => $categories['Acompanhamentos']->id,
                'price' => 16.90,
                'cost' => 7.50,
                'sku' => 'AC-004',
                'is_active' => true,
                'preparation_time' => 6,
                'calories' => 380,
                'created_at' => now()->subMonths(8),
                'updated_at' => now()
            ],
            // Bebidas
            [
                'name' => 'Coca-Cola Lata',
                'description' => 'Refrigerante Coca-Cola 350ml',
                'category_id' => $categories['Bebidas']->id,
                'price' => 5.90,
                'cost' => 2.10,
                'sku' => 'BE-001',
                'is_active' => true,
                'preparation_time' => 1,
                'calories' => 140,
                'created_at' => now()->subMonths(12),
                'updated_at' => now()
            ],
            [
                'name' => 'Suco de Laranja Natural',
                'description' => 'Suco de laranja natural 300ml',
                'category_id' => $categories['Bebidas']->id,
                'price' => 8.90,
                'cost' => 3.20,
                'sku' => 'BE-002',
                'is_active' => true,
                'preparation_time' => 3,
                'calories' => 120,
                'created_at' => now()->subMonths(12),
                'updated_at' => now()
            ],
            [
                'name' => 'Água Mineral',
                'description' => 'Água mineral 500ml',
                'category_id' => $categories['Bebidas']->id,
                'price' => 3.90,
                'cost' => 1.20,
                'sku' => 'BE-003',
                'is_active' => true,
                'preparation_time' => 1,
                'calories' => 0,
                'created_at' => now()->subMonths(12),
                'updated_at' => now()
            ],
            [
                'name' => 'Cerveja Heineken',
                'description' => 'Cerveja Heineken long neck 330ml',
                'category_id' => $categories['Bebidas']->id,
                'price' => 9.90,
                'cost' => 4.50,
                'sku' => 'BE-004',
                'is_active' => true,
                'preparation_time' => 1,
                'calories' => 150,
                'created_at' => now()->subMonths(8),
                'updated_at' => now()
            ],
            // Sobremesas
            [
                'name' => 'Milk-shake de Chocolate',
                'description' => 'Milk-shake cremoso de chocolate com chantilly',
                'category_id' => $categories['Sobremesas']->id,
                'price' => 14.90,
                'cost' => 6.80,
                'sku' => 'SO-001',
                'is_active' => true,
                'preparation_time' => 4,
                'calories' => 420,
                'created_at' => now()->subMonths(12),
                'updated_at' => now()
            ],
            [
                'name' => 'Brownie com Sorvete',
                'description' => 'Brownie de chocolate com sorvete de baunilha e calda',
                'category_id' => $categories['Sobremesas']->id,
                'price' => 12.90,
                'cost' => 5.20,
                'sku' => 'SO-002',
                'is_active' => true,
                'preparation_time' => 3,
                'calories' => 380,
                'created_at' => now()->subMonths(10),
                'updated_at' => now()
            ],
            // Combos
            [
                'name' => 'Combo Classic',
                'description' => 'Maria\'s Classic + Batata Frita + Refrigerante',
                'category_id' => $categories['Combos']->id,
                'price' => 34.90,
                'cost' => 18.50,
                'sku' => 'CO-001',
                'is_active' => true,
                'preparation_time' => 10,
                'calories' => 1100,
                'created_at' => now()->subMonths(6),
                'updated_at' => now()
            ],
            [
                'name' => 'Combo Supreme',
                'description' => 'Maria\'s Supreme + Batata com Cheddar + Refrigerante + Sobremesa',
                'category_id' => $categories['Combos']->id,
                'price' => 59.90,
                'cost' => 32.00,
                'sku' => 'CO-002',
                'is_active' => true,
                'preparation_time' => 15,
                'calories' => 1800,
                'created_at' => now()->subMonths(6),
                'updated_at' => now()
            ]
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
