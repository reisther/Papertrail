<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PaperTrail - Streamline Your Thesis & Capstone Journey</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-900 min-h-screen">

    @include('layouts.public-navigation')
    <main>
        <section class="relative overflow-hidden bg-gradient-to-b from-blue-50 via-white to-slate-50">
            <div class="absolute -left-32 top-12 h-72 w-72 rounded-full bg-blue-200/40 blur-3xl"></div>
            <div class="absolute -right-24 top-36 h-80 w-80 rounded-full bg-indigo-200/40 blur-3xl"></div>
            <div class="relative mx-auto grid max-w-7xl items-center gap-14 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-24">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-white px-3 py-1.5 text-xs font-semibold text-blue-700 shadow-sm"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Your thesis journey, in one clear workspace</div>
                    <h1 class="mt-6 max-w-xl text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl lg:text-6xl">Keep your capstone moving <span class="text-blue-600">forward.</span></h1>
                    <p class="mt-6 max-w-lg text-lg leading-8 text-slate-600">PaperTrail brings your group, adviser, files, tasks, meetings, and feedback into one focused place—so you always know what happens next.</p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <button onclick="openModal('signupModal')" class="rounded-xl bg-blue-600 px-6 py-3.5 text-sm font-semibold text-white shadow-xl shadow-blue-600/25 transition hover:-translate-y-0.5 hover:bg-blue-700">Create your workspace <span aria-hidden="true">→</span></button>
                        <a href="{{ route('features') }}" class="rounded-xl border border-slate-200 bg-white px-6 py-3.5 text-center text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-200 hover:text-blue-700">Explore features</a>
                    </div>
                    <div class="mt-8 flex items-center gap-5 text-sm text-slate-500"><span class="flex items-center gap-1.5"><span class="text-emerald-500">✓</span> Built for student teams</span><span class="flex items-center gap-1.5"><span class="text-emerald-500">✓</span> Adviser-ready</span></div>
                </div>
                <div class="relative mx-auto w-full max-w-xl">
                    <div class="absolute -inset-5 rounded-[2rem] bg-gradient-to-br from-blue-300/45 to-indigo-200/30 blur-2xl"></div>
                    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-blue-900/15">
                        <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50 px-5 py-4"><span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span><span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span><span class="ml-3 text-xs font-medium text-slate-400">PaperTrail workspace</span></div>
                        <div class="grid grid-cols-[105px_1fr]">
                            <aside class="space-y-3 border-r border-slate-100 bg-slate-50/70 p-4 text-[10px] font-medium text-slate-400"><div class="flex items-center gap-2 text-blue-600"><span class="h-5 w-5 rounded-md bg-blue-600"></span>Overview</div><div>Projects</div><div>Tasks</div><div>Messages</div><div>Meetings</div></aside>
                            <div class="p-5"><div class="flex items-start justify-between"><div><p class="text-xs text-slate-400">Good morning, team</p><h2 class="mt-1 text-lg font-bold text-slate-800">Capstone Dashboard</h2></div><div class="rounded-lg bg-blue-50 px-2 py-1 text-[10px] font-semibold text-blue-700">75% complete</div></div><div class="mt-5 rounded-xl bg-slate-900 p-4 text-white"><div class="flex justify-between text-xs"><span>Chapter 3: Methodology</span><span class="text-blue-300">3 tasks left</span></div><div class="mt-3 h-2 rounded-full bg-slate-700"><div class="h-2 w-3/4 rounded-full bg-blue-400"></div></div></div><div class="mt-4 grid grid-cols-2 gap-3"><div class="rounded-xl border border-slate-100 p-3"><p class="text-[10px] text-slate-400">Next meeting</p><p class="mt-1 text-xs font-bold text-slate-700">Fri, 2:00 PM</p><p class="mt-1 text-[10px] text-blue-600">with Adviser Cruz</p></div><div class="rounded-xl border border-slate-100 p-3"><p class="text-[10px] text-slate-400">Recent update</p><p class="mt-1 text-xs font-bold text-slate-700">Literature review</p><p class="mt-1 text-[10px] text-emerald-600">Ready to review</p></div></div></div>
                        </div>
                    </div>
                    <div class="absolute -bottom-6 -left-5 rounded-xl border border-slate-100 bg-white p-3 shadow-xl"><div class="flex items-center gap-2"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">✓</span><div><p class="text-xs font-semibold text-slate-700">Task submitted</p><p class="text-[10px] text-slate-400">Just now</p></div></div></div>
                </div>
            </div>
        </section>

        <section id="how-it-works" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center"><p class="text-sm font-bold uppercase tracking-widest text-blue-600">One calm workflow</p><h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">From first idea to final defense.</h2><p class="mt-4 text-slate-600">PaperTrail makes progress visible at every stage, without adding more noise to your group chat.</p></div>
            <div class="mt-12 grid gap-5 md:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-lg font-bold text-blue-700">1</span><h3 class="mt-5 text-lg font-bold text-slate-800">Set up your group</h3><p class="mt-2 text-sm leading-6 text-slate-600">Create a project space, invite members, and organize your thesis materials from day one.</p></article>
                <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 text-lg font-bold text-indigo-700">2</span><h3 class="mt-5 text-lg font-bold text-slate-800">Work with clarity</h3><p class="mt-2 text-sm leading-6 text-slate-600">Turn chapters into tasks, share documents, and keep every responsibility visible.</p></article>
                <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-lg font-bold text-emerald-700">3</span><h3 class="mt-5 text-lg font-bold text-slate-800">Stay aligned</h3><p class="mt-2 text-sm leading-6 text-slate-600">Schedule consultations and get adviser feedback without losing the context of your work.</p></article>
            </div>
        </section>

        <section id="for-everyone" class="border-y border-slate-200 bg-slate-900 py-20 text-white"><div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:px-8"><div><p class="text-sm font-bold uppercase tracking-widest text-blue-300">Built for the whole team</p><h2 class="mt-3 text-3xl font-bold sm:text-4xl">A better research experience for everyone involved.</h2><p class="mt-5 max-w-xl leading-7 text-slate-300">Students keep momentum. Leaders see the full picture. Advisers can guide projects with the context they need.</p><a href="{{ route('features') }}" class="mt-7 inline-flex rounded-xl bg-white px-5 py-3 text-sm font-bold text-slate-900 transition hover:bg-blue-50">See everything PaperTrail can do →</a></div><div class="grid gap-4 sm:grid-cols-2"><div class="rounded-2xl bg-white/10 p-5 ring-1 ring-white/10"><p class="text-sm font-bold">For students</p><p class="mt-2 text-sm leading-6 text-slate-300">Know exactly what to work on and what is due next.</p></div><div class="rounded-2xl bg-white/10 p-5 ring-1 ring-white/10"><p class="text-sm font-bold">For leaders</p><p class="mt-2 text-sm leading-6 text-slate-300">Coordinate your group without chasing status updates.</p></div><div class="rounded-2xl bg-white/10 p-5 ring-1 ring-white/10"><p class="text-sm font-bold">For advisers</p><p class="mt-2 text-sm leading-6 text-slate-300">Review progress and support students at the right time.</p></div><div class="rounded-2xl bg-blue-500 p-5"><p class="text-sm font-bold">One shared trail</p><p class="mt-2 text-sm leading-6 text-blue-50">Every update stays connected to the project.</p></div></div></div></section>

        <section class="mx-auto max-w-4xl px-4 py-20 text-center sm:px-6"><x-application-logo class="mx-auto h-14 w-14 rounded-2xl shadow-lg shadow-blue-600/20" /><h2 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Make your next milestone feel manageable.</h2><p class="mx-auto mt-4 max-w-xl text-slate-600">Create a focused workspace for your thesis or capstone group today.</p><button onclick="openModal('signupModal')" class="mt-7 rounded-xl bg-blue-600 px-6 py-3.5 text-sm font-semibold text-white shadow-xl shadow-blue-600/25 transition hover:bg-blue-700">Get started with PaperTrail</button></section>
    </main>
    <footer class="border-t border-slate-200 bg-white"><div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-7 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8"><a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-slate-800"><x-application-logo class="h-7 w-7 rounded-lg" /> PaperTrail</a><div class="flex gap-5"><a href="{{ route('features') }}" class="hover:text-blue-600">Features</a><a href="{{ route('terms') }}" class="hover:text-blue-600">Terms</a></div></div></footer>

    <!-- Login Modal -->
    <div id="loginModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Login to PaperTrail</h3>
                    <button onclick="closeModal('loginModal')" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form action="{{ route('login', [], false) }}" method="POST" class="space-y-4">
                    @csrf

                    <!-- Display validation errors -->
                    @if ($errors->any())
                        <div class="bg-red-50 border border-red-200 rounded-md p-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Login failed:</h3>
                                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                        @if(session('login_attempt_notice'))
                                            <li class="mt-1 font-medium" role="status">{{ session('login_attempt_notice') }}</li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div>
                        <label for="login-email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="login-email" name="email" value="{{ old('email') }}" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="login-password" class="block text-sm font-medium text-gray-700">Password</label>
                        <div class="relative mt-1">
                            <input type="password" id="login-password" name="password" required class="block w-full rounded-md border border-gray-300 px-3 py-2 pr-12 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500">
                            <button type="button"
                                    id="toggle-login-password"
                                    onclick="togglePasswordVisibility('login-password', 'login-password-eye', 'login-password-eye-off', this)"
                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    aria-label="Show password"
                                    aria-pressed="false">
                                <svg id="login-password-eye" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <svg id="login-password-eye-off" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.584 10.587A2 2 0 0012 14a2 2 0 001.416-.587"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.363 5.365A9.466 9.466 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-2.099 3.592"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.228 6.232C4.518 7.509 3.226 9.53 2.458 12c1.274 4.057 5.064 7 9.542 7 1.446 0 2.822-.306 4.064-.856"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    @if(session('login_captcha_required'))
                        <div>
                            <x-recaptcha />
                        </div>
                    @endif
                    @if(session('account_locked'))
                        <div class="rounded-md bg-amber-50 p-3 text-sm text-amber-900">
                            <a href="{{ route('account.unlock') }}" class="font-semibold underline">Verify My Identity</a>
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="remember" class="ml-2 block text-sm text-gray-900">Remember me</label>
                        </div>
                        <div class="text-sm">
                            <a href="{{ route('password.request') }}" class="font-medium text-blue-600 hover:text-blue-500">Forgot password?</a>
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Sign in
                        </button>
                    </div>
                    @if(session('login_attempt_notice'))
                        <div class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-800" role="alert">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-2.5L13.73 4c-.77-.83-1.96-.83-2.73 0L4.07 16.5C3.3 17.33 4.23 19 5.07 19z"></path>
                            </svg>
                            <span><strong>Unable to sign in.</strong> Check your email and password, then try again.</span>
                        </div>
                    @endif
                </form>
                <div class="mt-4 text-center">
                    <p class="text-sm text-gray-600">
                        Don't have an account? 
                        <button onclick="closeModal('loginModal'); openModal('signupModal')" class="font-medium text-blue-600 hover:text-blue-500">Sign up</button>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Signup Modal -->
    <div id="signupModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white mb-10">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Create Account</h3>
                    <button onclick="closeModal('signupModal')" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form action="{{ route('register', [], false) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    
                    <!-- Display validation errors -->
                    @if ($errors->any())
                        <div class="bg-red-50 border border-red-200 rounded-md p-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
                                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <!-- Role Selection -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Sign up as</label>
                        <select id="role" name="role" required class="mt-1 block w-full px-3 py-2 border border-blue-300 rounded-md shadow-sm bg-blue-50 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="Student" {{ old('role') === 'Student' ? 'selected' : '' }}>Member</option>
                            <option value="Leader" {{ old('role') === 'Leader' ? 'selected' : '' }}>Leader</option>
                            <option value="Teacher" {{ old('role') === 'Teacher' ? 'selected' : '' }}>Adviser</option>
                        </select>
                    </div>

                    <!-- Name Fields -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="firstname" class="block text-sm font-medium text-gray-700">First Name</label>
                            <input type="text" id="firstname" name="firstname" value="{{ old('firstname') }}" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="lastname" class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input type="text" id="lastname" name="lastname" value="{{ old('lastname') }}" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div>
                        <label for="middlename" class="block text-sm font-medium text-gray-700">Middle Name</label>
                        <input type="text" id="middlename" name="middlename" value="{{ old('middlename') }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Academic Information -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="campus" class="block text-sm font-medium text-gray-700">Campus</label>
                            <input type="text" id="campus" name="campus" value="{{ old('campus') }}" required placeholder="e.g., Main Campus" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="course" class="block text-sm font-medium text-gray-700">Course</label>
                            <input type="text" id="course" name="course" value="{{ old('course') }}" required placeholder="e.g., Computer Science" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label for="section" class="block text-sm font-medium text-gray-700">Section</label>
                            <input type="text" id="section" name="section" value="{{ old('section') }}" required placeholder="e.g., A, B, C" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="id_document_file" class="block text-sm font-medium text-gray-700">ID Document (Photo/PDF)</label>
                            <p class="text-xs text-gray-500 mb-2">Upload your Student ID or Employee ID for admin verification</p>
                            <div id="dropzone" class="mt-1 flex justify-center px-4 py-5 border-2 border-gray-300 border-dashed rounded-md hover:border-blue-400 transition-colors">
                                <div class="w-full max-w-full space-y-2 text-center">
                                    <svg class="mx-auto h-10 w-10 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex flex-wrap items-center justify-center gap-x-1 text-sm text-gray-600">
                                        <label for="id_document_file" class="relative cursor-pointer rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                            <span>Upload a file</span>
                                            <input id="id_document_file" name="id_document_file" type="file" accept=".jpg,.jpeg,.png,.pdf" class="sr-only" required>
                                        </label>
                                        <span>or drag and drop</span>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG, PDF up to 10MB</p>
                                    <p class="text-xs text-yellow-700 leading-snug">Account will be pending until admin verifies your ID</p>
                                    <div id="file-info" class="hidden mx-auto mt-2 max-w-full rounded-md bg-green-50 px-3 py-2 text-sm text-green-700 break-words"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div>
                        <label for="signup-email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="signup-email" name="email" value="{{ old('email') }}" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Password Fields -->
                    <div>
                        <label for="signup-password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" id="signup-password" name="password" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="confirm-password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input type="password" id="confirm-password" name="password_confirmation" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="flex items-center">
                        <input id="terms" name="terms" type="checkbox" required class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="terms" class="ml-2 block text-sm text-gray-900">
                            I agree to the <a href="{{ route('terms') }}" target="_blank" class="text-blue-600 hover:text-blue-500 underline">Terms and Conditions</a>
                        </label>
                    </div>
                    <div>
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Create Account
                        </button>
                    </div>
                </form>
                <div class="mt-4 text-center">
                    <p class="text-sm text-gray-600">
                        Already have an account? 
                        <button onclick="closeModal('signupModal'); openModal('loginModal')" class="font-medium text-blue-600 hover:text-blue-500">Sign in</button>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Modal Functionality -->
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
            document.body.style.overflow = 'auto'; // Restore scrolling
        }

        function togglePasswordVisibility(inputId, visibleIconId, hiddenIconId, button) {
            const input = document.getElementById(inputId);
            const visibleIcon = document.getElementById(visibleIconId);
            const hiddenIcon = document.getElementById(hiddenIconId);
            if (!input || !visibleIcon || !hiddenIcon) return;

            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            visibleIcon.classList.toggle('hidden', !isVisible);
            hiddenIcon.classList.toggle('hidden', isVisible);

            if (button) {
                button.setAttribute('aria-pressed', String(!isVisible));
                button.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
            }
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const loginModal = document.getElementById('loginModal');
            const signupModal = document.getElementById('signupModal');
            
            if (event.target === loginModal) {
                closeModal('loginModal');
            }
            if (event.target === signupModal) {
                closeModal('signupModal');
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal('loginModal');
                closeModal('signupModal');
            }
        });

        // File upload drag and drop functionality
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('id_document_file');
        const fileInfo = document.getElementById('file-info');

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        // Highlight drop area when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, unhighlight, false);
        });

        // Handle dropped files
        dropzone.addEventListener('drop', handleDrop, false);

        // Handle file input change
        fileInput.addEventListener('change', function(e) {
            handleFiles(e.target.files);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function highlight(e) {
            dropzone.classList.add('border-blue-500', 'bg-blue-50');
        }

        function unhighlight(e) {
            dropzone.classList.remove('border-blue-500', 'bg-blue-50');
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        function handleFiles(files) {
            if (files.length > 0) {
                const file = files[0];
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please upload only JPG, PNG, or PDF files.');
                    return;
                }

                // Validate file size (10MB)
                const maxSize = 10 * 1024 * 1024; // 10MB in bytes
                if (file.size > maxSize) {
                    alert('File size must be less than 10MB.');
                    return;
                }

                // Update file input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;

                // Show file info
                fileInfo.textContent = `Selected: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                fileInfo.title = file.name;
                fileInfo.classList.remove('hidden');
            }
        }

        // Auto-open login modal if redirected from login route
        @if(session('openLoginModal'))
            document.addEventListener('DOMContentLoaded', function() {
                openModal('loginModal');
            });
        @endif

        // Debug: Log form submission
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.querySelector('#loginModal form');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    console.log('Login form submitted');
                    console.log('Form action:', this.action);
                    console.log('Form method:', this.method);
                });
            }
        });
    </script>
</body>
</html>
