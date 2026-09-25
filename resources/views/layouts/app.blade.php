<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PaperTrail - Streamline Your Thesis & Capstone Journey</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-900">
        <div class="min-h-screen bg-slate-50">
            @include('layouts.navigation')
            @auth
                @php
                    $hideMobileBack = request()->routeIs('dashboard', 'admin.dashboard', 'teacher.dashboard', 'chat.*');
                    $mobileBackFallback = match (true) {
                        Auth::user()->isAdmin() => route('admin.dashboard'),
                        Auth::user()->isTeacher() => route('teacher.dashboard'),
                        default => route('dashboard'),
                    };
                @endphp
                @unless($hideMobileBack)
                    <div class="border-b border-gray-200 bg-white px-4 py-2 xl:hidden">
                        <button type="button"
                                onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = @js($mobileBackFallback); }"
                                class="inline-flex items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-100">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Back
                        </button>
                    </div>
                @endunless
            @endauth
            <!-- Page Content -->
            @isset($header)
                <header class="border-b border-slate-200 bg-white/80">
                    <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset
            <main>
                {{ $slot }}
            </main>
            
            <!-- Flash Messages -->
            @if(session('success'))
                <div class="fixed right-4 top-4 z-50 max-w-[calc(100vw-2rem)] rounded-lg bg-green-500 px-4 py-3 text-white shadow-lg sm:px-6">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="fixed right-4 top-4 z-50 max-w-[calc(100vw-2rem)] rounded-lg bg-red-500 px-4 py-3 text-white shadow-lg sm:px-6">
                    {{ session('error') }}
                </div>
            @endif
            @if(session('warning'))
                <div class="fixed right-4 top-4 z-50 max-w-[calc(100vw-2rem)] rounded-lg bg-yellow-500 px-4 py-3 text-white shadow-lg sm:px-6">
                    {!! session('warning') !!}
                </div>
            @endif
        </div>
        
        <!-- Auto-hide flash messages -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const flashMessages = document.querySelectorAll('.fixed.top-4.right-4');
                flashMessages.forEach(function(message) {
                    setTimeout(function() {
                        message.style.opacity = '0';
                        setTimeout(function() {
                            message.remove();
                        }, 300);
                    }, 5000);
                });
            });
        </script>
    </body>
</html>
