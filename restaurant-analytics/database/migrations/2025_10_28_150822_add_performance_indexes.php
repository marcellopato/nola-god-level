<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Índices críticos para performance do dashboard
        // Verifica se as tabelas existem antes de criar os índices
        
        if (Schema::hasTable('sales')) {
            // Índice composto para filtros mais comuns (data + status + loja)
            DB::statement('CREATE INDEX IF NOT EXISTS idx_sales_date_status_store 
                          ON sales (created_at, sale_status_desc, store_id) 
                          WHERE sale_status_desc = \'COMPLETED\'');
            
            // Índice composto para filtros de data + canal
            DB::statement('CREATE INDEX IF NOT EXISTS idx_sales_date_status_channel 
                          ON sales (created_at, sale_status_desc, channel_id) 
                          WHERE sale_status_desc = \'COMPLETED\'');
        }
        
        if (Schema::hasTable('product_sales')) {
            // Índices para product_sales (análise de produtos mais vendidos)
            DB::statement('CREATE INDEX IF NOT EXISTS idx_product_sales_performance 
                          ON product_sales (product_id, quantity, total_price)');
            
            // Índice para joins frequentes entre sales e product_sales
            DB::statement('CREATE INDEX IF NOT EXISTS idx_product_sales_sale_product 
                          ON product_sales (sale_id, product_id)');
        }
        
        if (Schema::hasTable('stores')) {
            // Índice para stores ativas (usado no KPI)
            DB::statement('CREATE INDEX IF NOT EXISTS idx_stores_active 
                          ON stores (is_active) WHERE is_active = true');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_sales_date_status_store');
        DB::statement('DROP INDEX IF EXISTS idx_sales_date_status_channel');
        DB::statement('DROP INDEX IF EXISTS idx_product_sales_performance');
        DB::statement('DROP INDEX IF EXISTS idx_product_sales_sale_product');
        DB::statement('DROP INDEX IF EXISTS idx_stores_active');
    }
};
