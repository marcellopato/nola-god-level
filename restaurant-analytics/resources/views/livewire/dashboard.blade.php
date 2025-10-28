<div>
    <!-- Loading Overlay -->
    @if($isLoading || $isRefreshing)
        <x-loading-spinner :overlay="true" :text="$loadingSection" />
    @endif

    <!-- Notification Area (será implementada via JavaScript simples) -->

    <!-- Smart Alerts -->
    <x-smart-alerts :alerts="$smartAlerts" />

    <!-- Restaurant Insights -->
    <x-restaurant-insights :insights="$restaurantInsights" />

    <!-- Dashboard Header -->
    <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Dashboard Analytics</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Insights em tempo real do seu restaurante
                    </p>
                </div>
                
                <div class="mt-4 lg:mt-0 flex flex-col sm:flex-row gap-4">
                    <!-- Date Range Selector -->
                    <div class="flex flex-wrap gap-2">
                        <button wire:click="setDateRange('today')" 
                                class="px-3 py-1 text-xs rounded-md transition-all duration-200 {{ $activeDateRange === 'today' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 hover:bg-gray-200' }}">
                            Hoje
                        </button>
                        <button wire:click="setDateRange('last7days')" 
                                class="px-3 py-1 text-xs rounded-md transition-all duration-200 {{ $activeDateRange === 'last7days' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 hover:bg-gray-200' }}">
                            7 dias
                        </button>
                        <button wire:click="setDateRange('last30days')" 
                                class="px-3 py-1 text-xs rounded-md transition-all duration-200 {{ $activeDateRange === 'last30days' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 hover:bg-gray-200' }}">
                            30 dias
                        </button>
                        <button wire:click="setDateRange('thisMonth')" 
                                class="px-3 py-1 text-xs rounded-md transition-all duration-200 {{ $activeDateRange === 'thisMonth' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 hover:bg-gray-200' }}">
                            Este mês
                        </button>
                    </div>
                    
                    <button wire:click="refreshData" 
                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
                            @if($isRefreshing) disabled @endif>
                        @if($isRefreshing)
                            <x-loading-spinner size="sm" color="white" />
                            <span>Atualizando...</span>
                        @else
                            <span>🔄</span>
                            <span>Atualizar</span>
                        @endif
                    </button>
                </div>
            </div>
            
            <!-- Advanced Filters -->
            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data Início</label>
                    <input type="date" wire:model.live="dateFrom" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data Fim</label>
                    <input type="date" wire:model.live="dateTo" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Loja</label>
                    <select wire:model.live="selectedStore" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">Todas as lojas</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Canal</label>
                    <select wire:model.live="selectedChannel" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">Todos os canais</option>
                        @foreach($channels as $channel)
                            <option value="{{ $channel->id }}">{{ $channel->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- KPIs Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        @if($isInitialLoad)
            @for($i = 0; $i < 4; $i++)
                <x-skeleton type="kpi" />
            @endfor
        @else
            <div class="bg-white overflow-hidden shadow rounded-lg transition-all duration-300 hover:shadow-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                                <span class="text-green-600 text-lg">💰</span>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Faturamento Total</dt>
                                <dd class="text-lg font-medium text-gray-900">R$ {{ $kpis['total_revenue'] ?? '0,00' }}</dd>
                            </dl>
                        </div>
                    </div>
                    @if(isset($kpis['revenue_growth']))
                        <div class="mt-3 flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $kpis['revenue_growth'] >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $kpis['revenue_growth'] >= 0 ? '↗️' : '↘️' }} 
                                {{ $kpis['revenue_growth'] >= 0 ? '+' : '' }}{{ $kpis['revenue_growth'] }}%
                            </span>
                            <span class="ml-2 text-xs text-gray-500">vs período anterior</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg transition-all duration-300 hover:shadow-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                                <span class="text-blue-600 text-lg">🛒</span>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total de Vendas</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($kpis['total_sales'] ?? 0) }}</dd>
                            </dl>
                        </div>
                    </div>
                    @if(isset($kpis['sales_growth']))
                        <div class="mt-3 flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $kpis['sales_growth'] >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $kpis['sales_growth'] >= 0 ? '↗️' : '↘️' }} 
                                {{ $kpis['sales_growth'] >= 0 ? '+' : '' }}{{ $kpis['sales_growth'] }}%
                            </span>
                            <span class="ml-2 text-xs text-gray-500">vendas vs anterior</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg transition-all duration-300 hover:shadow-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                                <span class="text-purple-600 text-lg">🎯</span>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Ticket Médio</dt>
                                <dd class="text-lg font-medium text-gray-900">R$ {{ $kpis['avg_ticket'] ?? '0,00' }}</dd>
                            </dl>
                        </div>
                    </div>
                    @if(isset($kpis['ticket_growth']))
                        <div class="mt-3 flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $kpis['ticket_growth'] >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $kpis['ticket_growth'] >= 0 ? '↗️' : '↘️' }} 
                                {{ $kpis['ticket_growth'] >= 0 ? '+' : '' }}{{ $kpis['ticket_growth'] }}%
                            </span>
                            <span class="ml-2 text-xs text-gray-500">ticket vs anterior</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg transition-all duration-300 hover:shadow-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-orange-100 rounded-md flex items-center justify-center">
                                <span class="text-orange-600 text-lg">🏪</span>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Lojas Ativas</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $kpis['active_stores'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        @if($isInitialLoad)
            <x-skeleton type="chart" />
            <x-skeleton type="chart" />
        @else
            <!-- Sales Over Time Chart -->
            <div class="bg-white overflow-hidden shadow rounded-lg transition-all duration-300 hover:shadow-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Vendas no Tempo</h3>
                        <select wire:model.live="selectedPeriod" 
                                class="text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="daily">Diário</option>
                            <option value="weekly">Semanal</option>
                            <option value="monthly">Mensal</option>
                            <option value="hourly">Por Hora</option>
                        </select>
                    </div>
                    
                    <div class="h-48 sm:h-64">
                        <canvas id="salesOverTimeChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Hourly Distribution Chart -->
            <div class="bg-white overflow-hidden shadow rounded-lg transition-all duration-300 hover:shadow-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Distribuição por Horário</h3>
                    <div class="h-48 sm:h-64">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Tables Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        @if($isInitialLoad)
            <x-skeleton type="table" />
            <x-skeleton type="table" />
        @else
            <!-- Top Products -->
            <div class="bg-white overflow-hidden shadow rounded-lg transition-all duration-300 hover:shadow-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Top 10 Produtos</h3>
                    <div class="overflow-x-auto -mx-4 sm:mx-0">
                        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sm:px-6">Produto</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sm:px-6">Qtd</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sm:px-6">Receita</th>
                                    </tr>
                                </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($topProducts as $product)
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-3 py-4 text-sm text-gray-900 sm:px-6">
                                            <div class="font-medium truncate max-w-xs">{{ $product['name'] }}</div>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500 sm:px-6">{{ $product['quantity'] }}</td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500 sm:px-6">R$ {{ $product['revenue'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-3 py-4 text-center text-sm text-gray-500 sm:px-6">
                                            Nenhum produto encontrado para o período selecionado
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Channel Performance -->
            <div class="bg-white overflow-hidden shadow rounded-lg transition-all duration-300 hover:shadow-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Performance por Canal</h3>
                    <div class="overflow-x-auto -mx-4 sm:mx-0">
                        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sm:px-6">Canal</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sm:px-6">Vendas</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sm:px-6">Receita</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sm:px-6">Ticket Médio</th>
                                    </tr>
                                </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($channelPerformance as $channel)
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-3 py-4 sm:px-6">
                                            <div class="text-sm text-gray-900 font-medium">{{ $channel['name'] }}</div>
                                            <div class="text-xs text-gray-500">{{ $channel['type'] }}</div>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500 sm:px-6">{{ number_format($channel['total_sales']) }}</td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500 sm:px-6">R$ {{ $channel['revenue'] }}</td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500 sm:px-6">R$ {{ $channel['avg_ticket'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-4 text-center text-sm text-gray-500 sm:px-6">
                                            Nenhum canal encontrado para o período selecionado
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            let salesChart, hourlyChart;
            
            function initCharts(salesData = null, hourlyData = null) {
                // Use provided data or fallback to initial server data
                const salesOverTime = salesData || @json($salesOverTime ?? ['labels' => [], 'sales_data' => [], 'revenue_data' => []]);
                const hourlyDistribution = hourlyData || @json($hourlyDistribution ?? ['labels' => [], 'sales_data' => []]);
                
                console.log('Initializing charts with data:', { salesOverTime, hourlyDistribution });
                
                // Sales Over Time Chart
                const salesCtx = document.getElementById('salesOverTimeChart');
                if (salesCtx) {
                    if (salesChart) {
                        salesChart.destroy();
                        salesChart = null;
                    }
                    
                    salesChart = new Chart(salesCtx, {
                        type: 'line',
                        data: {
                            labels: salesOverTime.labels || [],
                            datasets: [{
                                label: 'Vendas',
                                data: salesOverTime.sales_data || [],
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                tension: 0.1,
                                yAxisID: 'y'
                            }, {
                                label: 'Receita (R$)',
                                data: salesOverTime.revenue_data || [],
                                borderColor: 'rgb(16, 185, 129)',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                tension: 0.1,
                                yAxisID: 'y1'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            scales: {
                                y: {
                                    type: 'linear',
                                    display: true,
                                    position: 'left',
                                    title: {
                                        display: true,
                                        text: 'Número de Vendas'
                                    }
                                },
                                y1: {
                                    type: 'linear',
                                    display: true,
                                    position: 'right',
                                    title: {
                                        display: true,
                                        text: 'Receita (R$)'
                                    },
                                    grid: {
                                        drawOnChartArea: false,
                                    },
                                }
                            }
                        }
                    });
                }
                
                // Hourly Distribution Chart
                const hourlyCtx = document.getElementById('hourlyChart');
                if (hourlyCtx) {
                    if (hourlyChart) {
                        hourlyChart.destroy();
                        hourlyChart = null;
                    }
                    
                    hourlyChart = new Chart(hourlyCtx, {
                        type: 'bar',
                        data: {
                            labels: hourlyDistribution.labels || [],
                            datasets: [{
                                label: 'Vendas por Hora',
                                data: hourlyDistribution.sales_data || [],
                                backgroundColor: 'rgba(147, 51, 234, 0.8)',
                                borderColor: 'rgb(147, 51, 234)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Número de Vendas'
                                    }
                                }
                            }
                        }
                    });
                }
            }
            
            // Initialize charts with initial data
            initCharts();
            
            // Listen for custom dataRefreshed event with new data
            Livewire.on('dataRefreshed', (eventData) => {
                console.log('Data refresh event received:', eventData);
                const data = Array.isArray(eventData) ? eventData[0] : eventData;
                
                setTimeout(() => {
                    initCharts(data.salesOverTime, data.hourlyDistribution);
                }, 100);
            });
        });
    </script>
</div>
