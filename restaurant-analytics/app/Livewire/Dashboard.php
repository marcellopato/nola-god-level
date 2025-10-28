<?php

namespace App\Livewire;

use App\Services\AnalyticsService;
use App\Models\Store;
use App\Models\Channel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public $dateFrom;
    public $dateTo;
    public $selectedStore = '';
    public $selectedChannel = '';
    public $selectedPeriod = 'daily';
    public $activeDateRange = 'last30days';
    
    public $kpis = [];
    public $salesOverTime = [];
    public $topProducts = [];
    public $storePerformance = [];
    public $channelPerformance = [];
    public $hourlyDistribution = [];
    
    public function mount()
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        
        $this->loadData();
    }

    public function updatedDateFrom()
    {
        $this->activeDateRange = null; // Limpa o período ativo quando mudança manual
        $this->loadData();
        $this->dispatch('dataRefreshed', [
            'salesOverTime' => $this->salesOverTime,
            'hourlyDistribution' => $this->hourlyDistribution
        ]);
    }

    public function updatedDateTo()
    {
        $this->activeDateRange = null; // Limpa o período ativo quando mudança manual
        $this->loadData();
        $this->dispatch('dataRefreshed', [
            'salesOverTime' => $this->salesOverTime,
            'hourlyDistribution' => $this->hourlyDistribution
        ]);
    }

    public function updatedSelectedStore()
    {
        $this->loadData();
        $this->dispatch('dataRefreshed', [
            'salesOverTime' => $this->salesOverTime,
            'hourlyDistribution' => $this->hourlyDistribution
        ]);
    }

    public function updatedSelectedChannel()
    {
        $this->loadData();
        $this->dispatch('dataRefreshed', [
            'salesOverTime' => $this->salesOverTime,
            'hourlyDistribution' => $this->hourlyDistribution
        ]);
    }

    public function updatedSelectedPeriod()
    {
        $this->loadData();
        $this->dispatch('dataRefreshed', [
            'salesOverTime' => $this->salesOverTime,
            'hourlyDistribution' => $this->hourlyDistribution
        ]);
    }

    public function loadData()
    {
        $filters = $this->getFilters();
        
        // KPIs básicos usando queries diretas
        $this->kpis = $this->getBasicKPIs($filters);
        $this->salesOverTime = $this->getSalesOverTimeData($filters);
        $this->topProducts = $this->getTopProductsData($filters);
        $this->storePerformance = $this->getStorePerformanceData($filters);
        $this->channelPerformance = $this->getChannelPerformanceData($filters);
        $this->hourlyDistribution = $this->getHourlyDistributionData($filters);
    }

    public function refreshData()
    {
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->clearCache();
        $this->loadData();
        
        $this->dispatch('dataRefreshed', [
            'salesOverTime' => $this->salesOverTime,
            'hourlyDistribution' => $this->hourlyDistribution
        ]);
    }

    public function setDateRange($range)
    {
        $this->activeDateRange = $range;
        
        switch ($range) {
            case 'today':
                $this->dateFrom = $this->dateTo = Carbon::now()->toDateString();
                break;
            case 'yesterday':
                $this->dateFrom = $this->dateTo = Carbon::yesterday()->toDateString();
                break;
            case 'last7days':
                $this->dateFrom = Carbon::now()->subDays(7)->toDateString();
                $this->dateTo = Carbon::now()->toDateString();
                break;
            case 'last30days':
                $this->dateFrom = Carbon::now()->subDays(30)->toDateString();
                $this->dateTo = Carbon::now()->toDateString();
                break;
            case 'thisMonth':
                $this->dateFrom = Carbon::now()->startOfMonth()->toDateString();
                $this->dateTo = Carbon::now()->toDateString();
                break;
            case 'lastMonth':
                $this->dateFrom = Carbon::now()->subMonth()->startOfMonth()->toDateString();
                $this->dateTo = Carbon::now()->subMonth()->endOfMonth()->toDateString();
                break;
        }
        
        $this->loadData();
        $this->dispatch('dataRefreshed', [
            'salesOverTime' => $this->salesOverTime,
            'hourlyDistribution' => $this->hourlyDistribution
        ]);
    }

    private function getFilters(): array
    {
        $filters = [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo
        ];
        
        if ($this->selectedStore) {
            $filters['store_id'] = $this->selectedStore;
        }
        
        if ($this->selectedChannel) {
            $filters['channel_id'] = $this->selectedChannel;
        }
        
        return $filters;
    }

    private function getBasicKPIs($filters)
    {
        $query = DB::table('sales')->where('sale_status_desc', 'COMPLETED');
        
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

        $totalSales = $query->count();
        $totalRevenue = $query->sum('total_amount') ?? 0;
        $avgTicket = $totalSales > 0 ? $totalRevenue / $totalSales : 0;
        $activeStores = DB::table('stores')->where('is_active', true)->count();

        return [
            'total_revenue' => number_format($totalRevenue, 2, ',', '.'),
            'total_sales' => $totalSales,
            'avg_ticket' => number_format($avgTicket, 2, ',', '.'),
            'active_stores' => $activeStores
        ];
    }

    private function getSalesOverTimeData($filters)
    {
        $query = DB::table('sales')
            ->select(DB::raw('DATE(created_at) as period'), DB::raw('COUNT(*) as sales_count'), DB::raw('SUM(total_amount) as revenue'))
            ->where('sale_status_desc', 'COMPLETED');
            
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

        $data = $query->groupBy('period')->orderBy('period')->get();
        
        return [
            'labels' => $data->pluck('period')->toArray(),
            'sales_data' => $data->pluck('sales_count')->toArray(),
            'revenue_data' => $data->pluck('revenue')->toArray()
        ];
    }

    private function getTopProductsData($filters)
    {
        $query = DB::table('product_sales')
            ->join('products', 'products.id', '=', 'product_sales.product_id')
            ->join('sales', 'sales.id', '=', 'product_sales.sale_id')
            ->select('products.name', DB::raw('SUM(product_sales.quantity) as total_quantity'), DB::raw('SUM(product_sales.total_price) as total_revenue'))
            ->where('sales.sale_status_desc', 'COMPLETED');

        if (isset($filters['date_from'])) {
            $query->where('sales.created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('sales.created_at', '<=', $filters['date_to']);
        }
        if (isset($filters['store_id'])) {
            $query->where('sales.store_id', $filters['store_id']);
        }
        if (isset($filters['channel_id'])) {
            $query->where('sales.channel_id', $filters['channel_id']);
        }

        return $query->groupBy('products.id', 'products.name')
                    ->orderBy('total_quantity', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($item) {
                        return [
                            'name' => $item->name,
                            'quantity' => $item->total_quantity,
                            'revenue' => number_format($item->total_revenue, 2, ',', '.')
                        ];
                    })
                    ->toArray();
    }

    private function getStorePerformanceData($filters)
    {
        $query = DB::table('sales')
            ->join('stores', 'stores.id', '=', 'sales.store_id')
            ->select('stores.name', 'stores.city', DB::raw('COUNT(*) as total_sales'), DB::raw('SUM(sales.total_amount) as total_revenue'), DB::raw('AVG(sales.total_amount) as avg_ticket'))
            ->where('sales.sale_status_desc', 'COMPLETED');

        if (isset($filters['date_from'])) {
            $query->where('sales.created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('sales.created_at', '<=', $filters['date_to']);
        }
        if (isset($filters['store_id'])) {
            $query->where('sales.store_id', $filters['store_id']);
        }
        if (isset($filters['channel_id'])) {
            $query->where('sales.channel_id', $filters['channel_id']);
        }

        return $query->groupBy('stores.id', 'stores.name', 'stores.city')
                    ->orderBy('total_revenue', 'desc')
                    ->get()
                    ->toArray();
    }

    private function getChannelPerformanceData($filters)
    {
        $query = DB::table('sales')
            ->join('channels', 'channels.id', '=', 'sales.channel_id')
            ->select('channels.name', 'channels.type', DB::raw('COUNT(*) as total_sales'), DB::raw('SUM(sales.total_amount) as total_revenue'), DB::raw('AVG(sales.total_amount) as avg_ticket'))
            ->where('sales.sale_status_desc', 'COMPLETED');

        if (isset($filters['date_from'])) {
            $query->where('sales.created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('sales.created_at', '<=', $filters['date_to']);
        }
        if (isset($filters['store_id'])) {
            $query->where('sales.store_id', $filters['store_id']);
        }
        if (isset($filters['channel_id'])) {
            $query->where('sales.channel_id', $filters['channel_id']);
        }

        return $query->groupBy('channels.id', 'channels.name', 'channels.type')
                    ->orderBy('total_revenue', 'desc')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'name' => $item->name,
                            'type' => $item->type,
                            'total_sales' => $item->total_sales,
                            'revenue' => number_format($item->total_revenue, 2, ',', '.'),
                            'avg_ticket' => number_format($item->avg_ticket, 2, ',', '.')
                        ];
                    })
                    ->toArray();
    }

    private function getHourlyDistributionData($filters)
    {
        $query = DB::table('sales')
            ->select(DB::raw('EXTRACT(hour FROM created_at) as hour'), DB::raw('COUNT(*) as sales_count'), DB::raw('SUM(total_amount) as revenue'))
            ->where('sale_status_desc', 'COMPLETED');

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

        $data = $query->groupBy('hour')->orderBy('hour')->get();
        
        return [
            'labels' => $data->pluck('hour')->map(function($hour) {
                return $hour . ':00';
            })->toArray(),
            'sales_data' => $data->pluck('sales_count')->toArray()
        ];
    }

    public function render()
    {
        return view('livewire.dashboard', [
            'stores' => Store::active()->orderBy('name')->get(),
            'channels' => Channel::orderBy('name')->get()
        ]);
    }
}
