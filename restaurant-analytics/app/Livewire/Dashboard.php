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
    public $smartAlerts = [];
    public $restaurantInsights = [];
    
    // Loading states
    public $isLoading = false;
    public $isRefreshing = false;
    public $loadingSection = '';
    public $isInitialLoad = true;
    
    public function mount()
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        
        $this->isLoading = true;
        $this->loadData();
        $this->isLoading = false;
        $this->isInitialLoad = false;
    }

    public function updatedDateFrom()
    {
        $this->activeDateRange = null; // Limpa o período ativo quando mudança manual
        $this->isLoading = true;
        $this->loadingSection = 'Atualizando filtros...';
        $this->loadData();
        $this->isLoading = false;
        $this->loadingSection = '';
        $this->dispatch('dataRefreshed', [
            'salesOverTime' => $this->salesOverTime,
            'hourlyDistribution' => $this->hourlyDistribution
        ]);
    }

    public function updatedDateTo()
    {
        $this->activeDateRange = null; // Limpa o período ativo quando mudança manual
        $this->isLoading = true;
        $this->loadingSection = 'Atualizando filtros...';
        $this->loadData();
        $this->isLoading = false;
        $this->loadingSection = '';
        $this->dispatch('dataRefreshed', [
            'salesOverTime' => $this->salesOverTime,
            'hourlyDistribution' => $this->hourlyDistribution
        ]);
    }

    public function updatedSelectedStore()
    {
        $this->isLoading = true;
        $this->loadingSection = 'Filtrando por loja...';
        $this->loadData();
        $this->isLoading = false;
        $this->loadingSection = '';
        $this->dispatch('dataRefreshed', [
            'salesOverTime' => $this->salesOverTime,
            'hourlyDistribution' => $this->hourlyDistribution
        ]);
    }

    public function updatedSelectedChannel()
    {
        $this->isLoading = true;
        $this->loadingSection = 'Filtrando por canal...';
        $this->loadData();
        $this->isLoading = false;
        $this->loadingSection = '';
        $this->dispatch('dataRefreshed', [
            'salesOverTime' => $this->salesOverTime,
            'hourlyDistribution' => $this->hourlyDistribution
        ]);
    }

    public function updatedSelectedPeriod()
    {
        $this->isLoading = true;
        $this->loadingSection = 'Ajustando período...';
        $this->loadData();
        $this->isLoading = false;
        $this->loadingSection = '';
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
        
        // Sistema de alertas inteligentes
        $this->smartAlerts = $this->generateSmartAlerts($this->kpis, $filters);
        
        // Insights específicos para restaurantes
        $this->restaurantInsights = $this->generateRestaurantInsights($filters);
    }

    public function refreshData()
    {
        $this->isRefreshing = true;
        $this->loadingSection = 'Atualizando dados...';
        
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->clearCache();
        $this->loadData();
        
        $this->isRefreshing = false;
        $this->loadingSection = '';
        
        $this->dispatch('dataRefreshed', [
            'salesOverTime' => $this->salesOverTime,
            'hourlyDistribution' => $this->hourlyDistribution
        ]);
        
        // Notificação de sucesso
        $this->dispatch('showNotification', [
            'type' => 'success',
            'message' => 'Dados atualizados com sucesso!'
        ]);
    }

    public function setDateRange($range)
    {
        $this->activeDateRange = $range;
        $this->isLoading = true;
        $this->loadingSection = 'Alterando período...';
        
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
        $this->isLoading = false;
        $this->loadingSection = '';
        
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

        // Comparação com período anterior para insights
        $previousPeriodData = $this->getPreviousPeriodComparison($filters);

        return [
            'total_revenue' => number_format($totalRevenue, 2, ',', '.'),
            'total_sales' => $totalSales,
            'avg_ticket' => number_format($avgTicket, 2, ',', '.'),
            'active_stores' => $activeStores,
            'revenue_growth' => $previousPeriodData['revenue_growth'],
            'sales_growth' => $previousPeriodData['sales_growth'],
            'ticket_growth' => $previousPeriodData['ticket_growth'],
        ];
    }

    private function getPreviousPeriodComparison($filters)
    {
        // Calcula o período anterior baseado no filtro atual
        $currentStart = Carbon::parse($filters['date_from']);
        $currentEnd = Carbon::parse($filters['date_to']);
        $daysDiff = $currentStart->diffInDays($currentEnd);
        
        $previousStart = $currentStart->copy()->subDays($daysDiff + 1);
        $previousEnd = $currentStart->copy()->subDay();

        $previousQuery = DB::table('sales')
            ->where('sale_status_desc', 'COMPLETED')
            ->where('created_at', '>=', $previousStart)
            ->where('created_at', '<=', $previousEnd);

        if (isset($filters['store_id'])) {
            $previousQuery->where('store_id', $filters['store_id']);
        }
        if (isset($filters['channel_id'])) {
            $previousQuery->where('channel_id', $filters['channel_id']);
        }

        $previousSales = $previousQuery->count();
        $previousRevenue = $previousQuery->sum('total_amount') ?? 0;
        $previousAvgTicket = $previousSales > 0 ? $previousRevenue / $previousSales : 0;

        // Cálculo atual
        $currentQuery = DB::table('sales')
            ->where('sale_status_desc', 'COMPLETED')
            ->where('created_at', '>=', $filters['date_from'])
            ->where('created_at', '<=', $filters['date_to']);

        if (isset($filters['store_id'])) {
            $currentQuery->where('store_id', $filters['store_id']);
        }
        if (isset($filters['channel_id'])) {
            $currentQuery->where('channel_id', $filters['channel_id']);
        }

        $currentSales = $currentQuery->count();
        $currentRevenue = $currentQuery->sum('total_amount') ?? 0;
        $currentAvgTicket = $currentSales > 0 ? $currentRevenue / $currentSales : 0;

        // Cálculo de crescimento
        $revenueGrowth = $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;
        $salesGrowth = $previousSales > 0 ? (($currentSales - $previousSales) / $previousSales) * 100 : 0;
        $ticketGrowth = $previousAvgTicket > 0 ? (($currentAvgTicket - $previousAvgTicket) / $previousAvgTicket) * 100 : 0;

        return [
            'revenue_growth' => round($revenueGrowth, 1),
            'sales_growth' => round($salesGrowth, 1),
            'ticket_growth' => round($ticketGrowth, 1),
        ];
    }

    private function generateSmartAlerts($kpis, $filters)
    {
        $alerts = [];

        // Alert 1: Queda significativa de faturamento
        if (isset($kpis['revenue_growth']) && $kpis['revenue_growth'] < -15) {
            $alerts[] = [
                'title' => 'Queda Crítica no Faturamento',
                'message' => "Faturamento caiu {$kpis['revenue_growth']}% vs período anterior. Verifique se há problemas operacionais ou sazonalidade.",
                'type' => 'Financeiro',
                'severity' => 'high',
                'action' => 'Investigar causas'
            ];
        }

        // Alert 2: Crescimento acelerado
        if (isset($kpis['revenue_growth']) && $kpis['revenue_growth'] > 25) {
            $alerts[] = [
                'title' => 'Crescimento Excepcional! 🚀',
                'message' => "Faturamento cresceu {$kpis['revenue_growth']}%! Considere expandir estratégias que estão funcionando.",
                'type' => 'Oportunidade',
                'severity' => 'low',
                'action' => 'Analisar fatores de sucesso'
            ];
        }

        // Alert 3: Ticket médio em queda com vendas em alta
        if (isset($kpis['sales_growth']) && isset($kpis['ticket_growth']) && 
            $kpis['sales_growth'] > 10 && $kpis['ticket_growth'] < -5) {
            $alerts[] = [
                'title' => 'Ticket Médio Caindo',
                'message' => "Vendas cresceram {$kpis['sales_growth']}% mas ticket médio caiu {$kpis['ticket_growth']}%. Produtos de menor valor estão dominando.",
                'type' => 'Operacional',
                'severity' => 'medium',
                'action' => 'Revisar mix de produtos'
            ];
        }

        // Alert 4: Detecção de padrão de horário anômalo
        $hourlyAlert = $this->detectHourlyAnomalies($filters);
        if ($hourlyAlert) {
            $alerts[] = $hourlyAlert;
        }

        // Alert 5: Performance de canal específico
        $channelAlert = $this->detectChannelAnomalies($filters);
        if ($channelAlert) {
            $alerts[] = $channelAlert;
        }

        return $alerts;
    }

    private function detectHourlyAnomalies($filters)
    {
        // Análise de distribuição horária para detectar padrões estranhos
        $hourlyData = $this->getHourlyDistributionData($filters);
        
        if (empty($hourlyData['sales_data'])) {
            return null;
        }

        $totalSales = array_sum($hourlyData['sales_data']);
        $salesDataCount = count($hourlyData['sales_data']);
        
        if ($salesDataCount === 0) {
            return null;
        }
        
        $avgSalesPerHour = $totalSales / $salesDataCount;
        
        // Encontra picos incomuns (3x a média)
        foreach ($hourlyData['sales_data'] as $index => $sales) {
            if ($sales > $avgSalesPerHour * 3 && $sales > 10) {
                $hour = $hourlyData['labels'][$index] ?? $index;
                return [
                    'title' => 'Pico de Vendas Detectado',
                    'message' => "Vendas excepcionalmente altas às {$hour} ({$sales} vendas vs média de " . round($avgSalesPerHour) . "). Considere otimizar operação neste horário.",
                    'type' => 'Operacional',
                    'severity' => 'low',
                    'action' => 'Otimizar staffing'
                ];
            }
        }

        return null;
    }

    private function detectChannelAnomalies($filters)
    {
        // Detecta se um canal específico está muito abaixo da performance
        $channelData = $this->getChannelPerformanceData($filters);
        
        if (count($channelData) < 2) {
            return null;
        }

        $revenues = array_column($channelData, 'total_revenue');
        
        if (empty($revenues) || count($revenues) === 0) {
            return null;
        }
        
        $avgRevenue = array_sum($revenues) / count($revenues);
        
        foreach ($channelData as $channel) {
            $revenueNumeric = (float) str_replace(['.', ','], ['', '.'], $channel['revenue']);
            if ($revenueNumeric < $avgRevenue * 0.3 && $revenueNumeric > 0) {
                return [
                    'title' => 'Canal com Performance Baixa',
                    'message' => "Canal '{$channel['name']}' está com receita muito abaixo da média. Pode haver problema de integração ou marketing.",
                    'type' => 'Marketing',
                    'severity' => 'medium',
                    'action' => 'Verificar canal'
                ];
            }
        }

        return null;
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

    private function generateRestaurantInsights($filters)
    {
        $insights = [];

        // Insight 1: Horário de pico para otimização de staffing
        $peakHour = $this->findPeakHour($filters);
        if ($peakHour) {
            $insights[] = [
                'title' => 'Horário de Pico Identificado',
                'description' => "Maior movimento às {$peakHour['hour']} com {$peakHour['sales']} vendas. Considere aumentar equipe neste período.",
                'icon' => '⏰',
                'category' => 'operations',
                'priority' => 'high',
                'metric' => "Pico: {$peakHour['sales']} vendas"
            ];
        }

        // Insight 2: Canal mais rentável
        $bestChannel = $this->findMostProfitableChannel($filters);
        if ($bestChannel) {
            $insights[] = [
                'title' => 'Canal Mais Rentável',
                'description' => "Canal '{$bestChannel['name']}' tem o melhor desempenho. Foque marketing e promoções neste canal.",
                'icon' => '📱',
                'category' => 'marketing',
                'priority' => 'medium',
                'metric' => "R$ {$bestChannel['revenue']} de receita"
            ];
        }

        // Insight 3: Produto estrela
        $topProduct = $this->findTopProduct($filters);
        if ($topProduct) {
            $insights[] = [
                'title' => 'Produto Estrela',
                'description' => "'{$topProduct['name']}' é seu produto mais vendido. Considere criar combos ou variações.",
                'icon' => '⭐',
                'category' => 'marketing',
                'priority' => 'medium',
                'metric' => "{$topProduct['quantity']} unidades vendidas"
            ];
        }

        // Insight 4: Análise de ticket médio
        $ticketInsight = $this->analyzeTicketTrends($filters);
        if ($ticketInsight) {
            $insights[] = $ticketInsight;
        }

        return array_slice($insights, 0, 6); // Máximo 6 insights
    }

    private function findPeakHour($filters)
    {
        $hourlyData = $this->getHourlyDistributionData($filters);
        if (empty($hourlyData['sales_data'])) return null;

        $maxSales = max($hourlyData['sales_data']);
        $maxIndex = array_search($maxSales, $hourlyData['sales_data']);
        
        return [
            'hour' => $hourlyData['labels'][$maxIndex] ?? $maxIndex . ':00',
            'sales' => $maxSales
        ];
    }

    private function findMostProfitableChannel($filters)
    {
        $channelData = $this->getChannelPerformanceData($filters);
        if (empty($channelData)) return null;

        return $channelData[0]; // Já ordenado por receita desc
    }

    private function findTopProduct($filters)
    {
        $productData = $this->getTopProductsData($filters);
        if (empty($productData)) return null;

        return $productData[0]; // Primeiro produto já é o mais vendido
    }

    private function analyzeTicketTrends($filters)
    {
        if (!isset($this->kpis['ticket_growth'])) return null;

        $growth = $this->kpis['ticket_growth'];
        
        if ($growth > 10) {
            return [
                'title' => 'Ticket Médio em Alta! 📈',
                'description' => "Ticket médio cresceu {$growth}%. Clientes estão comprando mais por pedido. Continue estratégias de upselling.",
                'icon' => '💰',
                'category' => 'financial',
                'priority' => 'low',
                'metric' => "+{$growth}% vs período anterior"
            ];
        } elseif ($growth < -5) {
            return [
                'title' => 'Ticket Médio Preocupante',
                'description' => "Ticket médio caiu {$growth}%. Considere combos promocionais ou itens complementares.",
                'icon' => '⚠️',
                'category' => 'financial',
                'priority' => 'high',
                'metric' => "{$growth}% vs período anterior"
            ];
        }

        return null;
    }

    public function render()
    {
        return view('livewire.dashboard', [
            'stores' => Store::active()->orderBy('name')->get(),
            'channels' => Channel::orderBy('name')->get()
        ]);
    }
}
