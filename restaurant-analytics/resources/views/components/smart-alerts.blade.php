@props([
    'alerts' => []
])

@if(count($alerts) > 0)
    <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
        <div class="p-4 sm:p-6">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.966-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <h3 class="ml-3 text-lg font-medium text-gray-900">
                    🚨 Alertas Inteligentes
                </h3>
            </div>
            
            <div class="space-y-3">
                @foreach($alerts as $alert)
                    <div class="flex items-start p-3 rounded-lg 
                        @if($alert['severity'] === 'high') bg-red-50 border border-red-200
                        @elseif($alert['severity'] === 'medium') bg-yellow-50 border border-yellow-200
                        @else bg-blue-50 border border-blue-200
                        @endif">
                        
                        <div class="flex-shrink-0 pt-0.5">
                            @if($alert['severity'] === 'high')
                                <div class="h-2 w-2 bg-red-400 rounded-full"></div>
                            @elseif($alert['severity'] === 'medium')
                                <div class="h-2 w-2 bg-yellow-400 rounded-full"></div>
                            @else
                                <div class="h-2 w-2 bg-blue-400 rounded-full"></div>
                            @endif
                        </div>
                        
                        <div class="ml-3 flex-1">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium
                                    @if($alert['severity'] === 'high') text-red-800
                                    @elseif($alert['severity'] === 'medium') text-yellow-800
                                    @else text-blue-800
                                    @endif">
                                    {{ $alert['title'] }}
                                </p>
                                <span class="text-xs
                                    @if($alert['severity'] === 'high') text-red-600
                                    @elseif($alert['severity'] === 'medium') text-yellow-600
                                    @else text-blue-600
                                    @endif">
                                    {{ $alert['type'] }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm
                                @if($alert['severity'] === 'high') text-red-700
                                @elseif($alert['severity'] === 'medium') text-yellow-700
                                @else text-blue-700
                                @endif">
                                {{ $alert['message'] }}
                            </p>
                            
                            @if(isset($alert['action']))
                                <div class="mt-2">
                                    <button class="text-sm font-medium underline
                                        @if($alert['severity'] === 'high') text-red-800 hover:text-red-900
                                        @elseif($alert['severity'] === 'medium') text-yellow-800 hover:text-yellow-900
                                        @else text-blue-800 hover:text-blue-900
                                        @endif">
                                        {{ $alert['action'] }}
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif