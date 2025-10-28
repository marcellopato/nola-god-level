<?php

namespace App\Livewire;

use App\Services\RestaurantAnalyticsService;
use App\Models\Store;
use App\Models\Channel;
use Carbon\Carbon;
use Livewire\Component;

class RestaurantInsights extends Component
{
    public $dateFrom;
    public $dateTo;
    public $selectedStore = '';
    public $selectedChannel = '';
    
    public $popularCustomizations = [];
    public $deliveryPerformance = [];
    public $mostCustomizedProducts = [];
    public $peakHours = [];
    public $paymentMix = [];
    public $salesAnomalies = [];
    
    protected $restaurantAnalytics;

    public function mount(RestaurantAnalyticsService $restaurantAnalytics)
    {
        $this->restaurantAnalytics = $restaurantAnalytics;
        
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

    public function loadData()
    {
        $filters = $this->getFilters();
        
        $this->popularCustomizations = $this->restaurantAnalytics->getPopularCustomizations($filters);
        $this->deliveryPerformance = $this->restaurantAnalytics->getDeliveryPerformanceByRegion($filters);
        $this->mostCustomizedProducts = $this->restaurantAnalytics->getMostCustomizedProducts($filters);
        $this->peakHours = $this->restaurantAnalytics->getPeakHoursByWeekday($filters);
        $this->paymentMix = $this->restaurantAnalytics->getPaymentMixAnalysis($filters);
        $this->salesAnomalies = $this->restaurantAnalytics->detectSalesAnomalies($filters);
    }

    public function refreshData()
    {
        $this->restaurantAnalytics->clearCache();
        $this->loadData();
        
        $this->dispatch('dataRefreshed');
    }

    public function setDateRange($range)
    {
        switch ($range) {
            case 'last7days':
                $this->dateFrom = Carbon::now()->subDays(7)->toDateString();
                $this->dateTo = Carbon::now()->toDateString();
                break;
            case 'last30days':
                $this->dateFrom = Carbon::now()->subDays(30)->toDateString();
                $this->dateTo = Carbon::now()->toDateString();
                break;
            case 'last90days':
                $this->dateFrom = Carbon::now()->subDays(90)->toDateString();
                $this->dateTo = Carbon::now()->toDateString();
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
        return view('livewire.restaurant-insights', [
            'stores' => Store::active()->orderBy('name')->get(),
            'channels' => Channel::orderBy('name')->get()
        ]);
    }
}
