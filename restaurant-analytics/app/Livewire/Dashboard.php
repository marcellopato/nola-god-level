<?php

namespace App\Livewire;

use App\Services\AnalyticsService;
use App\Models\Store;
use App\Models\Channel;
use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public $dateFrom;
    public $dateTo;
    public $selectedStore = '';
    public $selectedChannel = '';
    public $selectedPeriod = 'daily';
    
    public $kpis = [];
    public $salesOverTime = [];
    public $topProducts = [];
    public $storePerformance = [];
    public $channelPerformance = [];
    public $hourlyDistribution = [];
    
    protected $analyticsService;

    public function mount(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
        
        // Default to last 30 days
        $this->dateTo = Carbon::now()->toDateString();
        $this->dateFrom = Carbon::now()->subDays(30)->toDateString();
        
        $this->loadData();
    }

    public function updatedDateFrom()
    {
        $this->loadData();
    }

    public function updatedDateTo()
    {
        $this->loadData();
    }

    public function updatedSelectedStore()
    {
        $this->loadData();
    }

    public function updatedSelectedChannel()
    {
        $this->loadData();
    }

    public function updatedSelectedPeriod()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $filters = $this->getFilters();
        
        $this->kpis = $this->analyticsService->getKPIs($filters);
        $this->salesOverTime = $this->analyticsService->getSalesOverTime($filters, $this->selectedPeriod);
        $this->topProducts = $this->analyticsService->getTopProducts($filters, 10);
        $this->storePerformance = $this->analyticsService->getStorePerformance($filters);
        $this->channelPerformance = $this->analyticsService->getChannelPerformance($filters);
        $this->hourlyDistribution = $this->analyticsService->getHourlySalesDistribution($filters);
    }

    public function refreshData()
    {
        $this->analyticsService->clearCache();
        $this->loadData();
        
        $this->dispatch('dataRefreshed');
    }

    public function setDateRange($range)
    {
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

    public function render()
    {
        return view('livewire.dashboard', [
            'stores' => Store::active()->orderBy('name')->get(),
            'channels' => Channel::orderBy('name')->get()
        ]);
    }
}
