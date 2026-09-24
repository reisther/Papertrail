<header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur">
    <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <x-application-logo class="h-10 w-10 rounded-xl shadow-sm" />
            <span class="text-xl font-bold tracking-tight text-slate-900">Paper<span class="text-blue-600">Trail</span></span>
        </a>

        <nav class="hidden items-center gap-7 text-sm font-medium text-slate-600 md:flex">
            <a href="{{ route('home') }}#how-it-works" class="transition hover:text-blue-600">How it works</a>
            <a href="{{ route('features') }}" class="transition hover:text-blue-600">Features</a>
            <a href="{{ route('home') }}#for-everyone" class="transition hover:text-blue-600">For teams</a>
        </nav>

        <div class="hidden items-center gap-3 md:flex">
            @auth
                <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-slate-700 hover:text-blue-600">Open dashboard</a>
            @else
                <a href="{{ route('login') }}" class="px-3 py-2 text-sm font-semibold text-slate-700 hover:text-blue-600">Log in</a>
                <a href="{{ route('register') }}" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:-translate-y-0.5 hover:bg-blue-700">Get started</a>
            @endauth
        </div>

        <details class="relative md:hidden">
            <summary class="cursor-pointer list-none rounded-lg p-2 text-slate-700 hover:bg-slate-100" aria-label="Open navigation menu">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </summary>
            <div class="absolute right-0 mt-3 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                <a href="{{ route('home') }}#how-it-works" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-blue-50">How it works</a>
                <a href="{{ route('features') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-blue-50">Features</a>
                <a href="{{ route('login') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-blue-50">Log in</a>
                <a href="{{ route('register') }}" class="mt-1 block rounded-lg bg-blue-600 px-3 py-2 text-center text-sm font-semibold text-white">Get started</a>
            </div>
        </details>
    </div>
</header>
