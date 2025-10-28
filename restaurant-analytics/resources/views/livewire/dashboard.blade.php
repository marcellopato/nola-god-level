<div>
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
                    <div class="flex gap-2">
                        <button wire:click="setDateRange('today')" 
                                class="px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded-md">
                            Hoje
                        </button>
                        <button wire:click="setDateRange('last7days')" 
                                class="px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded-md">
                            7 dias
                        </button>
                        <button wire:click="setDateRange('last30days')" 
                                class="px-3 py-1 text-xs bg-indigo-100 text-indigo-700 rounded-md">
                            30 dias
                        </button>
                        <button wire:click="setDateRange('thisMonth')" 
                                class="px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded-md">
                            Este mês
                        </button>
                    </div>
                    
                    <button wire:click="refreshData" 
                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                        🔄 Atualizar
                    </button>
                </div>
            </div>
            
            <!-- Advanced Filters -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
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
        <div class="bg-white overflow-hidden shadow rounded-lg">
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
                    <div class="mt-2">
                        <span class="text-sm {{ $kpis['revenue_growth'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $kpis['revenue_growth'] >= 0 ? '↗️' : '↘️' }} {{ abs($kpis['revenue_growth']) }}% vs período anterior
                        </span>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
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
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
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
                            <dd class="text-lg font-medium text-gray-900">R$ {{ $kpis['average_ticket'] ?? '0,00' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
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
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Sales Over Time Chart -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
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
                
                <div class="h-64">
                    <canvas id="salesOverTimeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Hourly Distribution Chart -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Distribuição por Horário</h3>
                <div class="h-64">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Top Products -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Top 10 Produtos</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qtd</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receita</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($topProducts as $product)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $product['name'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $product['quantity'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">R$ {{ $product['revenue'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Channel Performance -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Performance por Canal</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Canal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receita</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket Médio</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($channelPerformance as $channel)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $channel['name'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $channel['type'] }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($channel['total_sales']) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">R$ {{ $channel['revenue'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">R$ {{ $channel['avg_ticket'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            let salesChart, hourlyChart;
            
            function initCharts() {
                // Sales Over Time Chart
                const salesCtx = document.getElementById('salesOverTimeChart');
                if (salesCtx) {
                    if (salesChart) salesChart.destroy();
                    
                    salesChart = new Chart(salesCtx, {
                        type: 'line',
                        data: {
                            labels: @json($salesOverTime['labels'] ?? []),
                            datasets: [{
                                label: 'Vendas',
                                data: @json($salesOverTime['sales_data'] ?? []),
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                tension: 0.1,
                                yAxisID: 'y'
                            }, {
                                label: 'Receita (R$)',
                                data: @json($salesOverTime['revenue_data'] ?? []),
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
                                },
                                y1: {
                                    type: 'linear',
                                    display: true,
                                    position: 'right',
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
                    if (hourlyChart) hourlyChart.destroy();
                    
                    hourlyChart = new Chart(hourlyCtx, {
                        type: 'bar',
                        data: {
                            labels: @json($hourlyDistribution['labels'] ?? []),
                            datasets: [{
                                label: 'Vendas por Hora',
                                data: @json($hourlyDistribution['sales_data'] ?? []),
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
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
            }
            
            // Initialize charts
            initCharts();
            
            // Reinitialize charts when data changes
            Livewire.on('dataRefreshed', () => {
                setTimeout(initCharts, 100);
            });
        });
    </script>
</div>
