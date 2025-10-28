<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Restaurant Analytics') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Chart.js -->  
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Alpine.js -->
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <h1 class="text-lg sm:text-xl font-bold text-gray-900">
                                🍔 <span class="hidden xs:inline">Restaurant</span> Analytics
                            </h1>
                        </div>
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="/" class="border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Dashboard
                            </a>
                            <a href="#" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Relatórios
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="text-xs sm:text-sm text-gray-500">
                            <span class="hidden sm:inline">Bem-vinda, </span>Maria! 👋
                        </span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-4 px-4 sm:py-6 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
    
    <script>
        // Global Chart.js configurations
        Chart.defaults.font.family = 'Figtree, sans-serif';
        Chart.defaults.color = '#6B7280';
        Chart.defaults.plugins.legend.position = 'bottom';
        
        // Listen for Livewire events
        document.addEventListener('livewire:init', () => {
            Livewire.on('dataRefreshed', () => {
                showNotification('Dados atualizados com sucesso!', 'success');
            });
            
            Livewire.on('showNotification', (data) => {
                showNotification(data.message, data.type);
            });
        });
        
        function showNotification(message, type = 'success') {
            const toast = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : 
                           type === 'error' ? 'bg-red-500' : 
                           type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
                           
            toast.className = `fixed top-4 right-4 ${bgColor} text-white px-4 py-2 rounded-md shadow-lg z-50 transition-all duration-300 transform translate-x-full`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 100);
            
            // Animate out and remove
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 300);
            }, 3000);
        }
    </script>
</body>
</html>