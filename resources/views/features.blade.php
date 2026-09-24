<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Features | PaperTrail</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-900">
    @include('layouts.public-navigation')

    <main>
        <section class="relative overflow-hidden bg-slate-900 px-4 py-20 text-center text-white sm:px-6 lg:px-8">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(37,99,235,.35),_transparent_34%),radial-gradient(circle_at_bottom_left,_rgba(79,70,229,.28),_transparent_34%)]"></div>
            <div class="relative mx-auto max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-[.2em] text-blue-300">Features</p>
                <h1 class="mt-4 text-4xl font-bold tracking-tight sm:text-5xl">Everything a thesis team needs to keep moving.</h1>
                <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-slate-300">A connected workspace built around the real rhythm of capstone work: planning, writing, review, meetings, and deadlines.</p>
                <a href="{{ route('register') }}" class="mt-8 inline-flex rounded-xl bg-white px-6 py-3.5 text-sm font-bold text-slate-900 transition hover:bg-blue-50">Start your workspace →</a>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div><p class="text-sm font-bold uppercase tracking-widest text-blue-600">Project command center</p><h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">See the work, not the confusion.</h2><p class="mt-5 leading-7 text-slate-600">Each group gets a home for project information, chapter progress, members, files, and the next meaningful action. No more searching through disconnected messages for the latest update.</p><ul class="mt-6 space-y-3 text-sm text-slate-700"><li class="flex gap-3"><span class="font-bold text-blue-600">✓</span> A clear project overview for every member</li><li class="flex gap-3"><span class="font-bold text-blue-600">✓</span> Chapter-based progress you can understand at a glance</li><li class="flex gap-3"><span class="font-bold text-blue-600">✓</span> Shared folders for submissions and working files</li></ul></div>
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/60"><div class="flex items-center justify-between border-b border-slate-100 pb-4"><div><p class="text-xs text-slate-400">Research project</p><p class="font-bold">Smart Attendance System</p></div><span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">On track</span></div><div class="mt-5 space-y-4">@foreach ([['Chapter 1 · Introduction', '100%', 'bg-emerald-500'], ['Chapter 2 · Review of Literature', '80%', 'bg-blue-500'], ['Chapter 3 · Methodology', '55%', 'bg-indigo-500'], ['Chapter 4 · Results', '20%', 'bg-slate-300']] as [$chapter, $progress, $color])<div><div class="mb-1.5 flex justify-between text-xs"><span class="font-medium text-slate-700">{{ $chapter }}</span><span class="text-slate-400">{{ $progress }}</span></div><div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full {{ $color }}" style="width: {{ $progress }}"></div></div></div>@endforeach</div><div class="mt-6 grid grid-cols-2 gap-3"><div class="rounded-xl bg-blue-50 p-3"><p class="text-xs text-blue-600">Open tasks</p><p class="mt-1 text-2xl font-bold text-blue-900">08</p></div><div class="rounded-xl bg-violet-50 p-3"><p class="text-xs text-violet-600">Next review</p><p class="mt-1 text-sm font-bold text-violet-900">Friday · 2 PM</p></div></div></div>
            </div>
        </section>

        <section class="border-y border-slate-200 bg-white"><div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8"><div class="max-w-2xl"><p class="text-sm font-bold uppercase tracking-widest text-blue-600">Your workday, organized</p><h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">The tools that remove the usual thesis friction.</h2></div><div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['Task tracking', 'Break chapters into clear assignments, set due dates, and keep ownership visible.', '✓'],
                ['Document organization', 'Store drafts and submissions by project so the latest file is always easy to find.', '▤'],
                ['Adviser matching', 'Help leaders find potential advisers and manage adviser requests in one place.', '◎'],
                ['Meeting schedules', 'Plan consultations, defense events, and review sessions with full project context.', '◷'],
                ['Team chat', 'Discuss the work where it belongs, with conversations tied to the right people and project.', '↗'],
                ['Progress insights', 'Give leaders and advisers a high-level view of work completed and work that needs attention.', '▥'],
            ] as [$title, $description, $icon])
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-6 transition hover:-translate-y-1 hover:border-blue-200 hover:bg-white hover:shadow-lg"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-xl font-bold text-blue-700">{{ $icon }}</span><h3 class="mt-5 text-lg font-bold">{{ $title }}</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p></article>
            @endforeach
        </div></div></section>

        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8"><div class="rounded-3xl bg-gradient-to-br from-blue-600 to-indigo-700 px-7 py-12 text-center text-white shadow-xl shadow-blue-600/20 sm:px-12"><x-application-logo class="mx-auto h-14 w-14 rounded-2xl shadow-lg" /><h2 class="mt-5 text-3xl font-bold">Your project deserves a clearer path.</h2><p class="mx-auto mt-3 max-w-xl text-blue-100">Bring your group, adviser, and work together with PaperTrail.</p><a href="{{ route('register') }}" class="mt-7 inline-flex rounded-xl bg-white px-6 py-3.5 text-sm font-bold text-blue-700 transition hover:bg-blue-50">Create an account</a></div></section>
    </main>
    <footer class="border-t border-slate-200 bg-white"><div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-7 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8"><a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-slate-800"><x-application-logo class="h-7 w-7 rounded-lg" /> PaperTrail</a><div class="flex gap-5"><a href="{{ route('home') }}" class="hover:text-blue-600">Home</a><a href="{{ route('terms') }}" class="hover:text-blue-600">Terms</a></div></div></footer>
</body>
</html>
