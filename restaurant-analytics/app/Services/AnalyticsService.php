<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Store;
use App\Models\Channel;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    /**
     * Get key performance indicators for the dashboard
     * Optimized: Single query with aggregations instead of multiple queries
     */
    public function getKPIs(array $filters = []): array
    {
        $cacheKey = 'kpis_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 900, function () use ($filters) { // Extended cache to 15min
            // Single optimized query for main metrics
            $query = DB::table('sales')->where('sale_status_desc', 'COMPLETED');
            
            // Apply filters efficiently  
            $this->applyFiltersToQuery($query, $filters);
            
            $mainStats = $query
                ->select([
                    DB::raw('COUNT(*) as total_sales'),
                    DB::raw('SUM(total_amount) as total_revenue'),
                    DB::raw('AVG(total_amount) as average_ticket')
                ])
                ->first();
            
            // Get comparison period stats
            $previousStats = $this->getPreviousPeriodStats($filters);
            $revenueGrowth = $this->calculateGrowth(
                $mainStats->total_revenue ?? 0, 
                $previousStats->total_revenue ?? 0
            );
            
            // Cached counts for stores and products (updated less frequently)
            $activeStores = Cache::remember('active_stores_count', 3600, fn() => Store::active()->count());
            $totalProducts = Cache::remember('total_products_count', 3600, fn() => Product::count());

            return [
                'total_sales' => (int) ($mainStats->total_sales ?? 0),
                'total_revenue' => (float) ($mainStats->total_revenue ?? 0),
                'average_ticket' => (float) ($mainStats->average_ticket ?? 0),
                'revenue_growth' => round($revenueGrowth, 1),
                'active_stores' => $activeStores,
                'total_products' => $totalProducts
            ];
        });
    }
    
    /**
     * Apply filters efficiently to query builder
     */
    private function applyFiltersToQuery($query, array $filters): void
    {
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['stores']) && is_array($filters['stores'])) {
            $query->whereIn('store_id', $filters['stores']);
        }
        if (!empty($filters['channels']) && is_array($filters['channels'])) {
            $query->whereIn('channel_id', $filters['channels']);
        }
    }
    
    /**
     * Get previous period statistics for comparison
     */
    private function getPreviousPeriodStats(array $filters): object
    {
        $previousFilters = $this->getPreviousPeriodFilters($filters);
        
        $query = DB::table('sales')->where('sale_status_desc', 'COMPLETED');
        $this->applyFiltersToQuery($query, $previousFilters);
        
        return $query
            ->select([
                DB::raw('SUM(total_amount) as total_revenue')
            ])
            ->first() ?? (object)['total_revenue' => 0];
    }
    
    /**
     * Calculate growth percentage
     */
    private function calculateGrowth(float $current, float $previous): float
    {
        if ($previous <= 0) return 0;
        return (($current - $previous) / $previous) * 100;
    }

    /**
     * Get sales data over time for charts
     */
    public function getSalesOverTime(array $filters = [], string $period = 'daily'): array
    {
        $cacheKey = "sales_over_time_{$period}_" . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 900, function () use ($filters, $period) {
            // Use direct DB query for better performance with large datasets
            $query = DB::table('sales')->where('sale_status_desc', 'COMPLETED');
            $this->applyFiltersToQuery($query, $filters);
            
            // Optimized date formatting for PostgreSQL
            $dateFormat = match($period) {
                'hourly' => "DATE_TRUNC('hour', created_at)",
                'daily' => "DATE_TRUNC('day', created_at)",
                'weekly' => "DATE_TRUNC('week', created_at)", 
                'monthly' => "DATE_TRUNC('month', created_at)",
                default => "DATE_TRUNC('day', created_at)"
            };

            $results = $query
                ->select([
                    DB::raw("{$dateFormat} as period"),
                    DB::raw('COUNT(*) as sales_count'),
                    DB::raw('SUM(total_amount) as revenue'),
                    DB::raw('AVG(total_amount) as avg_ticket')
                ])
                ->groupBy(DB::raw($dateFormat))
                ->orderBy('period')
                ->get();

            return [
                'labels' => $results->pluck('period')->map(function ($date) use ($period) {
                    return Carbon::parse($date)->format($this->getDateFormat($period));
                })->toArray(),
                'sales_data' => $results->pluck('sales_count')->toArray(),
                'revenue_data' => $results->pluck('revenue')->map(fn($r) => (float) $r)->toArray(),
                'avg_ticket_data' => $results->pluck('avg_ticket')->map(fn($r) => (float) $r)->toArray()
            ];
        });
    }

    /**
     * Get top performing products
     * Optimized: Better indexing strategy and efficient filtering
     */
    public function getTopProducts(array $filters = [], int $limit = 10): array
    {
        $cacheKey = "top_products_{$limit}_" . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 1800, function () use ($filters, $limit) { // 30min cache
            $query = DB::table('product_sales')
                ->join('products', 'products.id', '=', 'product_sales.product_id')
                ->join('sales', 'sales.id', '=', 'product_sales.sale_id')
                ->where('sales.sale_status_desc', 'COMPLETED');

            // Apply filters efficiently using the helper method
            if (!empty($filters['date_from'])) {
                $query->where('sales.created_at', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->where('sales.created_at', '<=', $filters['date_to']);
            }
            if (!empty($filters['stores']) && is_array($filters['stores'])) {
                $query->whereIn('sales.store_id', $filters['stores']);
            }
            if (!empty($filters['channels']) && is_array($filters['channels'])) {
                $query->whereIn('sales.channel_id', $filters['channels']);
            }

            return $query
                ->select([
                    'products.name',
                    DB::raw('SUM(product_sales.quantity) as total_quantity'),
                    DB::raw('SUM(product_sales.total_price) as total_revenue'),
                    DB::raw('AVG(product_sales.base_price) as avg_price')
                ])
                ->groupBy('products.id', 'products.name')
                ->orderBy('total_quantity', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->name,
                        'quantity' => (int) $item->total_quantity,
                        'revenue' => (float) $item->total_revenue,
                        'avg_price' => (float) $item->avg_price
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get performance by store
     */
    public function getStorePerformance(array $filters = []): array
    {
        $cacheKey = 'store_performance_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($filters) {
            $query = DB::table('sales')
                ->join('stores', 'stores.id', '=', 'sales.store_id')
                ->where('sales.sale_status_desc', 'COMPLETED');

            // Apply date filters
            if (isset($filters['date_from'])) {
                $query->where('sales.created_at', '>=', $filters['date_from']);
            }
            if (isset($filters['date_to'])) {
                $query->where('sales.created_at', '<=', $filters['date_to']);
            }

            return $query
                ->select([
                    'stores.name',
                    'stores.city',
                    DB::raw('COUNT(*) as total_sales'),
                    DB::raw('SUM(sales.total_amount) as total_revenue'),
                    DB::raw('AVG(sales.total_amount) as avg_ticket'),
                    DB::raw('AVG(sales.production_seconds) as avg_production_time')
                ])
                ->groupBy('stores.id', 'stores.name', 'stores.city')
                ->orderBy('total_revenue', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->name,
                        'city' => $item->city,
                        'total_sales' => (int) $item->total_sales,
                        'revenue' => number_format($item->total_revenue, 2),
                        'avg_ticket' => number_format($item->avg_ticket, 2),
                        'avg_production_minutes' => $item->avg_production_time 
                            ? round($item->avg_production_time / 60, 1) 
                            : null
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get performance by channel
     */
    public function getChannelPerformance(array $filters = []): array
    {
        $cacheKey = 'channel_performance_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($filters) {
            $query = DB::table('sales')
                ->join('channels', 'channels.id', '=', 'sales.channel_id')
                ->where('sales.sale_status_desc', 'COMPLETED');

            // Apply filters
            if (isset($filters['date_from'])) {
                $query->where('sales.created_at', '>=', $filters['date_from']);
            }
            if (isset($filters['date_to'])) {
                $query->where('sales.created_at', '<=', $filters['date_to']);
            }
            if (isset($filters['store_id'])) {
                $query->where('sales.store_id', $filters['store_id']);
            }

            return $query
                ->select([
                    'channels.name',
                    'channels.type',
                    DB::raw('COUNT(*) as total_sales'),
                    DB::raw('SUM(sales.total_amount) as total_revenue'),
                    DB::raw('AVG(sales.total_amount) as avg_ticket'),
                    DB::raw('AVG(sales.delivery_seconds) as avg_delivery_time')
                ])
                ->groupBy('channels.id', 'channels.name', 'channels.type')
                ->orderBy('total_revenue', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->name,
                        'type' => $item->type === 'D' ? 'Delivery' : 'Presencial',
                        'total_sales' => (int) $item->total_sales,
                        'revenue' => number_format($item->total_revenue, 2),
                        'avg_ticket' => number_format($item->avg_ticket, 2),
                        'avg_delivery_minutes' => $item->avg_delivery_time 
                            ? round($item->avg_delivery_time / 60, 1) 
                            : null
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get hourly sales distribution
     */
    public function getHourlySalesDistribution(array $filters = []): array
    {
        $cacheKey = 'hourly_distribution_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($filters) {
            $query = $this->applyFilters(Sale::completed(), $filters);
            
            $results = $query
                ->select([
                    DB::raw('EXTRACT(hour FROM created_at) as hour'),
                    DB::raw('COUNT(*) as sales_count'),
                    DB::raw('SUM(total_amount) as revenue')
                ])
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();

            // Ensure all 24 hours are present
            $hourlyData = collect(range(0, 23))->map(function ($hour) use ($results) {
                $data = $results->firstWhere('hour', $hour);
                return [
                    'hour' => sprintf('%02d:00', $hour),
                    'sales_count' => $data ? (int) $data->sales_count : 0,
                    'revenue' => $data ? (float) $data->revenue : 0
                ];
            });

            return [
                'labels' => $hourlyData->pluck('hour')->toArray(),
                'sales_data' => $hourlyData->pluck('sales_count')->toArray(),
                'revenue_data' => $hourlyData->pluck('revenue')->toArray()
            ];
        });
    }

    /**
     * Apply common filters to query
     */
    private function applyFilters($query, array $filters)
    {
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        if (isset($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }
        
        if (isset($filters['channel_id'])) {
            $query->where('channel_id', $filters['channel_id']);
        }

        return $query;
    }

    /**
     * Get filters for previous period comparison
     */
    private function getPreviousPeriodFilters(array $filters): array
    {
        $previousFilters = $filters;
        
        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $dateFrom = Carbon::parse($filters['date_from']);
            $dateTo = Carbon::parse($filters['date_to']);
            $daysDiff = $dateFrom->diffInDays($dateTo);
            
            $previousFilters['date_from'] = $dateFrom->copy()->subDays($daysDiff + 1)->toDateString();
            $previousFilters['date_to'] = $dateFrom->copy()->subDay()->toDateString();
        }
        
        return $previousFilters;
    }

    /**
     * Get appropriate date format for chart labels
     */
    private function getDateFormat(string $period): string
    {
        return match($period) {
            'hourly' => 'H:i',
            'daily' => 'd/m',
            'weekly' => 'W/Y',
            'monthly' => 'm/Y',
            default => 'd/m'
        };
    }

    /**
     * Clear all analytics cache
     */
    public function clearCache(): void
    {
        $patterns = [
            'kpis_*',
            'sales_over_time_*',
            'top_products_*', 
            'store_performance_*',
            'channel_performance_*',
            'hourly_distribution_*'
        ];
        
        foreach ($patterns as $pattern) {
            Cache::flush(); // For simplicity, could be more targeted
        }
    }
}