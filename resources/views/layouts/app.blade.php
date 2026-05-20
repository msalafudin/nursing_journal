<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;">
    <title>Nursing Journal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-midnight">
    <div id="app">
        @auth
        <!-- Navigation -->
        <nav class="bg-frost/80 backdrop-blur-lg sticky top-0 z-50 border-b border-steel/30">
            <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-12">
                    <!-- Logo -->
                    <a href="{{ route('dashboard') }}" class="font-sf-display text-[21px] font-semibold text-midnight tracking-tight">
                        Nursing Journal
                    </a>

                    <!-- Desktop Nav -->
                    <div class="hidden md:flex items-center">
                        <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>
                        @if(auth()->user()->isNurse())
                            <a href="{{ route('patient-data.form') }}" class="nav-link">Input Data</a>
                        @endif
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('units.index') }}" class="nav-link">Unit</a>
                            <a href="{{ route('users.index') }}" class="nav-link">Pengguna</a>
                            <a href="{{ route('reports.index') }}" class="nav-link">Laporan</a>
                        @endif
                    </div>

                    <!-- User -->
                    <div class="hidden md:flex items-center gap-4">
                        <span class="text-body text-cloud">{{ auth()->user()->full_name }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-body text-ocean hover:underline">Logout</button>
                        </form>
                    </div>

                    <!-- Mobile -->
                    <button id="mobile-menu-btn" class="md:hidden tap-target" aria-expanded="false" aria-label="Menu">
                        <svg class="h-5 w-5 text-midnight" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-steel/30">
                <div class="px-4 py-3 space-y-1">
                    <a href="{{ route('dashboard') }}" class="block py-2 text-body-lg text-midnight">Dashboard</a>
                    @if(auth()->user()->isNurse())
                        <a href="{{ route('patient-data.form') }}" class="block py-2 text-body-lg text-midnight">Input Data</a>
                    @endif
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('units.index') }}" class="block py-2 text-body-lg text-midnight">Unit</a>
                        <a href="{{ route('users.index') }}" class="block py-2 text-body-lg text-midnight">Pengguna</a>
                        <a href="{{ route('reports.index') }}" class="block py-2 text-body-lg text-midnight">Laporan</a>
                    @endif
                    <hr class="my-2 border-frost">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block py-2 text-body-lg text-ocean">Logout</button>
                    </form>
                </div>
            </div>
        </nav>
        @endauth

        <!-- Notifications -->
        <div id="notification-container" class="fixed top-4 right-4 z-40 space-y-2 max-w-sm"></div>

        <!-- Main -->
        <main class="min-h-screen">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-frost border-t border-steel/20 mt-[70px]">
            <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <p class="text-center text-body text-cloud">
                    &copy; {{ date('Y') }} Nursing Journal — RSI Muhammadiyah 2 Kendal
                </p>
            </div>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('mobile-menu-btn');
            const menu = document.getElementById('mobile-menu');
            if (btn && menu) {
                btn.addEventListener('click', () => {
                    menu.classList.toggle('hidden');
                    btn.setAttribute('aria-expanded', menu.classList.contains('hidden') ? 'false' : 'true');
                });
                menu.querySelectorAll('a').forEach(l => l.addEventListener('click', () => menu.classList.add('hidden')));
            }
        });
    </script>
</body>
</html>
