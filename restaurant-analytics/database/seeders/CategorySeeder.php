<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Burgers',
                'description' => 'Hamburgueres artesanais',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now()->subMonths(12),
                'updated_at' => now()
            ],
            [
                'name' => 'Acompanhamentos',
                'description' => 'Batatas, onion rings e outros acompanhamentos',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now()->subMonths(12),
                'updated_at' => now()
            ],
            [
                'name' => 'Bebidas',
                'description' => 'Refrigerantes, sucos e bebidas',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now()->subMonths(12),
                'updated_at' => now()
            ],
            [
                'name' => 'Sobremesas',
                'description' => 'Milk-shakes, sorvetes e doces',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now()->subMonths(12),
                'updated_at' => now()
            ],
            [
                'name' => 'Combos',
                'description' => 'Combos promocionais',
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => now()->subMonths(6),
                'updated_at' => now()
            ]
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
