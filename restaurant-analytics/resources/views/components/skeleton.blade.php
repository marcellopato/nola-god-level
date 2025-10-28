@props([
    'type' => 'card',
    'height' => 'h-4',
    'width' => 'w-full',
    'lines' => 3,
    'animated' => true
])

@php
    $animationClass = $animated ? 'animate-pulse' : '';
@endphp

@if($type === 'card')
    <div class="bg-white overflow-hidden shadow rounded-lg p-6 {{ $animationClass }}">
        <div class="space-y-4">
            <!-- Header -->
            <div class="h-6 bg-gray-200 rounded w-1/3"></div>
            
            <!-- Content lines -->
            @for($i = 0; $i < $lines; $i++)
                <div class="h-4 bg-gray-200 rounded {{ $i === $lines - 1 ? 'w-3/4' : 'w-full' }}"></div>
            @endfor
        </div>
    </div>

@elseif($type === 'kpi')
    <div class="bg-white overflow-hidden shadow rounded-lg p-6 {{ $animationClass }}">
        <div class="space-y-3">
            <!-- Icon placeholder -->
            <div class="h-8 w-8 bg-gray-200 rounded"></div>
            
            <!-- Title -->
            <div class="h-4 bg-gray-200 rounded w-2/3"></div>
            
            <!-- Value -->
            <div class="h-8 bg-gray-200 rounded w-1/2"></div>
            
            <!-- Change indicator -->
            <div class="h-3 bg-gray-200 rounded w-1/4"></div>
        </div>
    </div>

@elseif($type === 'chart')
    <div class="bg-white overflow-hidden shadow rounded-lg p-6 {{ $animationClass }}">
        <div class="space-y-4">
            <!-- Chart header -->
            <div class="h-6 bg-gray-200 rounded w-1/2"></div>
            
            <!-- Chart area -->
            <div class="h-64 bg-gray-100 rounded flex items-end space-x-2 p-4">
                @for($i = 0; $i < 12; $i++)
                    <div class="bg-gray-200 rounded-t flex-1" style="height: {{ rand(20, 80) }}%"></div>
                @endfor
            </div>
            
            <!-- Chart legend -->
            <div class="flex space-x-4">
                <div class="h-3 bg-gray-200 rounded w-16"></div>
                <div class="h-3 bg-gray-200 rounded w-20"></div>
            </div>
        </div>
    </div>

@elseif($type === 'table')
    <div class="bg-white overflow-hidden shadow rounded-lg {{ $animationClass }}">
        <div class="px-4 py-5 sm:p-6">
            <!-- Table header -->
            <div class="h-6 bg-gray-200 rounded w-1/3 mb-4"></div>
            
            <!-- Table content -->
            <div class="space-y-3">
                @for($i = 0; $i < 5; $i++)
                    <div class="flex space-x-4">
                        <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                        <div class="h-4 bg-gray-200 rounded w-1/6"></div>
                        <div class="h-4 bg-gray-200 rounded w-1/6"></div>
                        <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                    </div>
                @endfor
            </div>
        </div>
    </div>

@elseif($type === 'line')
    <div class="{{ $height }} {{ $width }} bg-gray-200 rounded {{ $animationClass }}"></div>

@endif