<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Customer;
use Faker\Factory as Faker;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('pt_BR');
        
        // Criar 200 clientes fictícios
        for ($i = 0; $i < 200; $i++) {
            Customer::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'phone' => $faker->cellphone(false),
                'address' => $faker->streetAddress,
                'city' => $faker->city,
                'state' => $faker->stateAbbr,
                'postal_code' => $faker->postcode,
                'birth_date' => $faker->dateTimeBetween('-60 years', '-18 years'),
                'gender' => $faker->randomElement(['M', 'F', 'O']),
                'customer_since' => $faker->dateTimeBetween('-2 years', 'now'),
                'created_at' => $faker->dateTimeBetween('-2 years', 'now'),
                'updated_at' => now()
            ]);
        }
    }
}
