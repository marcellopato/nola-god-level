<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            StoreSeeder::class,
            ChannelSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            CustomerSeeder::class,
            SaleSeeder::class,
        ]);
    }
}
