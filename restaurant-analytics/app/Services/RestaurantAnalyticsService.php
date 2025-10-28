<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class RestaurantAnalyticsService
{
    /**
     * Análise de customizações mais populares
     */
    public function getPopularCustomizations(array $filters = [], int $limit = 20): array
    {
        $cacheKey = "popular_customizations_{$limit}_" . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 600, function () use ($filters, $limit) {
            $query = DB::table('item_product_sales')
                ->join('items', 'items.id', '=', 'item_product_sales.item_id')
                ->join('product_sales', 'product_sales.id', '=', 'item_product_sales.product_sale_id')
                ->join('sales', 'sales.id', '=', 'product_sales.sale_id')
                ->where('sales.sale_status_desc', 'COMPLETED');

            // Apply filters
            $this->applyFiltersToQuery($query, $filters, 'sales');

            return $query
                ->select([
                    'items.name',
                    DB::raw('COUNT(*) as times_added'),
                    DB::raw('SUM(item_product_sales.price) as total_revenue'),
                    DB::raw('AVG(item_product_sales.price) as avg_price')
                ])
                ->groupBy('items.id', 'items.name')
                ->orderBy('times_added', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->name,
                        'times_added' => (int) $item->times_added,
                        'total_revenue' => number_format($item->total_revenue, 2),
                        'avg_price' => number_format($item->avg_price, 2)
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Análise de performance de delivery por região
     */
    public function getDeliveryPerformanceByRegion(array $filters = []): array
    {
        $cacheKey = "delivery_performance_" . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 600, function () use ($filters) {
            $query = DB::table('sales')
                ->join('delivery_addresses', 'delivery_addresses.sale_id', '=', 'sales.id')
                ->join('channels', 'channels.id', '=', 'sales.channel_id')
                ->where('sales.sale_status_desc', 'COMPLETED')
                ->where('channels.type', 'D')
                ->whereNotNull('sales.delivery_seconds');

            // Apply filters
            $this->applyFiltersToQuery($query, $filters, 'sales');

            return $query
                ->select([
                    'delivery_addresses.neighborhood',
                    'delivery_addresses.city',
                    DB::raw('COUNT(*) as total_deliveries'),
                    DB::raw('AVG(sales.delivery_seconds / 60.0) as avg_delivery_minutes'),
                    DB::raw('AVG(sales.total_amount) as avg_order_value'),
                    DB::raw('PERCENTILE_CONT(0.9) WITHIN GROUP (ORDER BY sales.delivery_seconds / 60.0) as p90_delivery_minutes')
                ])
                ->groupBy('delivery_addresses.neighborhood', 'delivery_addresses.city')
                ->having('total_deliveries', '>=', 5) // Minimum 5 deliveries for statistical relevance
                ->orderBy('total_deliveries', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'neighborhood' => $item->neighborhood,
                        'city' => $item->city,
                        'total_deliveries' => (int) $item->total_deliveries,
                        'avg_delivery_minutes' => round($item->avg_delivery_minutes, 1),
                        'avg_order_value' => number_format($item->avg_order_value, 2),
                        'p90_delivery_minutes' => round($item->p90_delivery_minutes, 1)
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Análise de produtos que mais recebem customizações
     */
    public function getMostCustomizedProducts(array $filters = [], int $limit = 15): array
    {
        $cacheKey = "most_customized_products_{$limit}_" . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 600, function () use ($filters, $limit) {
            $query = DB::table('product_sales')
                ->join('products', 'products.id', '=', 'product_sales.product_id')
                ->join('sales', 'sales.id', '=', 'product_sales.sale_id')
                ->leftJoin('item_product_sales', 'item_product_sales.product_sale_id', '=', 'product_sales.id')
                ->where('sales.sale_status_desc', 'COMPLETED');

            // Apply filters
            $this->applyFiltersToQuery($query, $filters, 'sales');

            return $query
                ->select([
                    'products.name',
                    DB::raw('COUNT(DISTINCT product_sales.id) as total_sold'),
                    DB::raw('COUNT(item_product_sales.id) as total_customizations'),
                    DB::raw('ROUND((COUNT(item_product_sales.id)::float / COUNT(DISTINCT product_sales.id)) * 100, 1) as customization_rate'),
                    DB::raw('AVG(product_sales.base_price) as avg_base_price')
                ])
                ->groupBy('products.id', 'products.name')
                ->having('total_sold', '>=', 10) // Minimum 10 sales for relevance
                ->orderBy('customization_rate', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->name,
                        'total_sold' => (int) $item->total_sold,
                        'total_customizations' => (int) $item->total_customizations,
                        'customization_rate' => (float) $item->customization_rate,
                        'avg_base_price' => number_format($item->avg_base_price, 2)
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Análise de horários de pico por dia da semana
     */
    public function getPeakHoursByWeekday(array $filters = []): array
    {
        $cacheKey = "peak_hours_weekday_" . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 600, function () use ($filters) {
            $query = DB::table('sales')
                ->where('sale_status_desc', 'COMPLETED');

            // Apply filters
            $this->applyFiltersToQuery($query, $filters, 'sales');

            return $query
                ->select([
                    DB::raw('EXTRACT(dow FROM created_at) as weekday'), // 0=Sunday, 1=Monday, etc.
                    DB::raw('EXTRACT(hour FROM created_at) as hour'),
                    DB::raw('COUNT(*) as sales_count'),
                    DB::raw('SUM(total_amount) as revenue')
                ])
                ->groupBy('weekday', 'hour')
                ->orderBy('weekday')
                ->orderBy('hour')
                ->get()
                ->groupBy('weekday')
                ->map(function ($dayData, $weekday) {
                    $dayName = [
                        '0' => 'Domingo',
                        '1' => 'Segunda',
                        '2' => 'Terça', 
                        '3' => 'Quarta',
                        '4' => 'Quinta',
                        '5' => 'Sexta',
                        '6' => 'Sábado'
                    ][$weekday] ?? 'N/A';

                    $peakHour = $dayData->sortByDesc('sales_count')->first();
                    
                    return [
                        'weekday' => $dayName,
                        'peak_hour' => $peakHour ? sprintf('%02d:00', $peakHour->hour) : null,
                        'peak_sales' => $peakHour ? (int) $peakHour->sales_count : 0,
                        'peak_revenue' => $peakHour ? number_format($peakHour->revenue, 2) : '0.00',
                        'hourly_data' => $dayData->map(function ($hour) {
                            return [
                                'hour' => sprintf('%02d:00', $hour->hour),
                                'sales_count' => (int) $hour->sales_count,
                                'revenue' => (float) $hour->revenue
                            ];
                        })->values()->toArray()
                    ];
                })
                ->values()
                ->toArray();
        });
    }

    /**
     * Análise de mix de pagamentos
     */
    public function getPaymentMixAnalysis(array $filters = []): array
    {
        $cacheKey = "payment_mix_" . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 600, function () use ($filters) {
            $query = DB::table('payments')
                ->join('payment_types', 'payment_types.id', '=', 'payments.payment_type_id')
                ->join('sales', 'sales.id', '=', 'payments.sale_id')
                ->where('sales.sale_status_desc', 'COMPLETED');

            // Apply filters
            $this->applyFiltersToQuery($query, $filters, 'sales');

            $results = $query
                ->select([
                    'payment_types.description as payment_type',
                    DB::raw('COUNT(*) as transaction_count'),
                    DB::raw('SUM(payments.value) as total_value'),
                    DB::raw('AVG(payments.value) as avg_transaction_value')
                ])
                ->groupBy('payment_types.id', 'payment_types.description')
                ->orderBy('total_value', 'desc')
                ->get();

            $totalValue = $results->sum('total_value');
            
            return $results->map(function ($item) use ($totalValue) {
                return [
                    'payment_type' => $item->payment_type,
                    'transaction_count' => (int) $item->transaction_count,
                    'total_value' => number_format($item->total_value, 2),
                    'avg_transaction_value' => number_format($item->avg_transaction_value, 2),
                    'percentage' => $totalValue > 0 ? round(($item->total_value / $totalValue) * 100, 1) : 0
                ];
            })->toArray();
        });
    }

    /**
     * Detecção de anomalias nas vendas
     */
    public function detectSalesAnomalies(array $filters = [], int $days = 30): array
    {
        $cacheKey = "sales_anomalies_{$days}_" . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($filters, $days) {
            $endDate = isset($filters['date_to']) ? Carbon::parse($filters['date_to']) : Carbon::now();
            $startDate = $endDate->copy()->subDays($days);
            
            $query = DB::table('sales')
                ->where('sale_status_desc', 'COMPLETED')
                ->whereBetween('created_at', [$startDate, $endDate]);

            // Apply other filters
            $filtersWithoutDate = $filters;
            unset($filtersWithoutDate['date_from'], $filtersWithoutDate['date_to']);
            $this->applyFiltersToQuery($query, $filtersWithoutDate, 'sales');

            $dailyData = $query
                ->select([
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as sales_count'),
                    DB::raw('SUM(total_amount) as revenue')
                ])
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            if ($dailyData->count() < 7) {
                return []; // Not enough data for anomaly detection
            }

            // Calculate statistics
            $salesValues = $dailyData->pluck('sales_count')->toArray();
            $revenueValues = $dailyData->pluck('revenue')->map(fn($r) => (float) $r)->toArray();
            
            $salesMean = array_sum($salesValues) / count($salesValues);
            $revenueMean = array_sum($revenueValues) / count($revenueValues);
            
            $salesStdDev = sqrt(array_sum(array_map(fn($x) => pow($x - $salesMean, 2), $salesValues)) / count($salesValues));
            $revenueStdDev = sqrt(array_sum(array_map(fn($x) => pow($x - $revenueMean, 2), $revenueValues)) / count($revenueValues));

            $anomalies = [];
            
            foreach ($dailyData as $day) {
                $salesZScore = $salesStdDev > 0 ? abs(($day->sales_count - $salesMean) / $salesStdDev) : 0;
                $revenueZScore = $revenueStdDev > 0 ? abs(((float) $day->revenue - $revenueMean) / $revenueStdDev) : 0;
                
                // Consider anomaly if z-score > 2 (roughly 95% confidence)
                if ($salesZScore > 2 || $revenueZScore > 2) {
                    $anomalies[] = [
                        'date' => $day->date,
                        'sales_count' => (int) $day->sales_count,
                        'revenue' => number_format($day->revenue, 2),
                        'sales_z_score' => round($salesZScore, 2),
                        'revenue_z_score' => round($revenueZScore, 2),
                        'type' => $day->sales_count > $salesMean ? 'high_sales' : 'low_sales',
                        'deviation_percentage' => [
                            'sales' => round((($day->sales_count - $salesMean) / $salesMean) * 100, 1),
                            'revenue' => round((((float) $day->revenue - $revenueMean) / $revenueMean) * 100, 1)
                        ]
                    ];
                }
            }
            
            return $anomalies;
        });
    }

    /**
     * Apply filters to query based on table prefix
     */
    private function applyFiltersToQuery($query, array $filters, string $tablePrefix = 'sales')
    {
        if (isset($filters['date_from'])) {
            $query->where("{$tablePrefix}.created_at", '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where("{$tablePrefix}.created_at", '<=', $filters['date_to']);
        }
        
        if (isset($filters['store_id'])) {
            $query->where("{$tablePrefix}.store_id", $filters['store_id']);
        }
        
        if (isset($filters['channel_id'])) {
            $query->where("{$tablePrefix}.channel_id", $filters['channel_id']);
        }

        return $query;
    }

    /**
     * Clear restaurant analytics cache
     */
    public function clearCache(): void
    {
        $patterns = [
            'popular_customizations_*',
            'delivery_performance_*',
            'most_customized_products_*',
            'peak_hours_weekday_*',
            'payment_mix_*',
            'sales_anomalies_*'
        ];
        
        // For simplicity, clear all cache
        Cache::flush();
    }
}