@props([
    'insights' => []
])

@if(count($insights) > 0)
    <div class="bg-gradient-to-br from-indigo-50 to-blue-50 overflow-hidden shadow rounded-lg mb-6">
        <div class="p-4 sm:p-6">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <span class="text-2xl">💡</span>
                    </div>  
                </div>
                <h3 class="ml-3 text-lg font-medium text-gray-900">
                    Insights Inteligentes para Restaurantes
                </h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($insights as $insight)
                    <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 
                        @if($insight['category'] === 'marketing') border-green-400
                        @elseif($insight['category'] === 'operations') border-blue-400
                        @elseif($insight['category'] === 'financial') border-purple-400
                        @else border-gray-400
                        @endif">
                        
                        <div class="flex items-start">
                            <div class="flex-shrink-0 pt-1">
                                <span class="text-lg">{{ $insight['icon'] }}</span>
                            </div>
                            
                            <div class="ml-3 flex-1">
                                <h4 class="text-sm font-semibold text-gray-900 mb-1">
                                    {{ $insight['title'] }}
                                </h4>
                                <p class="text-xs text-gray-600 mb-2">
                                    {{ $insight['description'] }}
                                </p>
                                
                                @if(isset($insight['metric']))
                                    <div class="bg-gray-50 rounded px-2 py-1 mb-2">
                                        <p class="text-xs font-medium text-gray-800">
                                            {{ $insight['metric'] }}
                                        </p>
                                    </div>
                                @endif
                                
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        @if($insight['category'] === 'marketing') bg-green-100 text-green-800
                                        @elseif($insight['category'] === 'operations') bg-blue-100 text-blue-800
                                        @elseif($insight['category'] === 'financial') bg-purple-100 text-purple-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst($insight['category']) }}
                                    </span>
                                    
                                    @if(isset($insight['priority']))
                                        <span class="text-xs
                                            @if($insight['priority'] === 'high') text-red-600 font-semibold
                                            @elseif($insight['priority'] === 'medium') text-yellow-600 font-medium
                                            @else text-green-600
                                            @endif">
                                            {{ ucfirst($insight['priority']) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif