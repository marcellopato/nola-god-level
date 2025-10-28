@props([
    'size' => 'md',
    'color' => 'indigo',
    'text' => '',
    'overlay' => false
])

@if($overlay)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 flex flex-col items-center space-y-4">
            <svg class="@if($size === 'sm') h-4 w-4 @elseif($size === 'lg') h-8 w-8 @elseif($size === 'xl') h-12 w-12 @else h-6 w-6 @endif 
                       @if($color === 'white') text-white @elseif($color === 'gray') text-gray-600 @elseif($color === 'green') text-green-600 @else text-indigo-600 @endif 
                       animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            @if($text)
                <p class="text-gray-700 text-sm font-medium">{{ $text }}</p>
            @endif
        </div>
    </div>
@else
    <div class="flex items-center space-x-2">
        <svg class="@if($size === 'sm') h-4 w-4 @elseif($size === 'lg') h-8 w-8 @elseif($size === 'xl') h-12 w-12 @else h-6 w-6 @endif 
                   @if($color === 'white') text-white @elseif($color === 'gray') text-gray-600 @elseif($color === 'green') text-green-600 @else text-indigo-600 @endif 
                   animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        @if($text)
            <span class="text-sm text-gray-600">{{ $text }}</span>
        @endif
    </div>
@endif