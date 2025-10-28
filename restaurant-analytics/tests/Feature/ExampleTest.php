<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->loadSchemaIfNeeded();
        
        // Create some basic test data to avoid database errors
        $this->seedBasicData();
        
        $response = $this->get('/');

        $response->assertStatus(200);
    }
    
    private function seedBasicData()
    {
        // Create required models with minimal data to allow the page to load
        if (!\App\Models\Brand::count()) {
            \App\Models\Brand::create([
                'name' => 'Test Brand',
                'email' => 'test@example.com'
            ]);
        }
        
        if (!\App\Models\Store::count()) {
            \App\Models\Store::create([
                'name' => 'Test Store',
                'brand_id' => 1,
                'address_street' => 'Test Street',
                'address_number' => '123',
                'district' => 'Test District'
            ]);
        }
        
        if (!\App\Models\Channel::count()) {
            \App\Models\Channel::create([
                'name' => 'Test Channel',
                'type' => 'D'
            ]);
        }
        
        if (!\App\Models\Category::count()) {
            \App\Models\Category::create([
                'name' => 'Test Category'
            ]);
        }
    }
}
