<x-app-layout>
    @php
        $leaderWithoutGroup = Auth::user()->canLeadGroup() && Auth::user()->ownedProjects()->doesntExist();
        $singleTitle = request()->query('mode') === 'single';
        $mode = $singleTitle ? 'single' : 'multiple';
        $savedMatches = session("adviser_matches.{$mode}");
        $showPending = request()->query('tab') === 'pending';
    @endphp

    <div class="py-10">
        <div class="max-w-6xl mx-auto px-6">
            @if($leaderWithoutGroup)
                @include('partials.leader-create-group-card')
            @else

            <!-- Page Title -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">
                    Find an Adviser - AI Assisted Matchmaking
                </h1>

                <p class="mt-2 text-gray-600">
                    @if($activeRequest)
                        Your group has an adviser.
                    @elseif($singleTitle)
                        Enter your approved research title to find advisers whose expertise matches your topic.
                    @else
                        Submit five proposed thesis titles. PaperTrail's AI
                        will analyze your topics and match them with advisers whose
                        expertise best aligns with your research.
                    @endif
                </p>
                @unless($activeRequest)
                    <p class="mt-2 text-sm text-gray-600">You can request several advisers. Once one accepts, your other pending requests are withdrawn automatically.</p>
                @endunless
            </div>

            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="mb-6 flex flex-wrap gap-2">
                <a href="{{ route('advisers.title-submission', ['mode' => $mode, 'tab' => 'pending']) }}" class="action-button-secondary">
                    Pending Approval ({{ $currentRequests->where('status', 'pending')->count() }})
                </a>
                @unless($activeRequest)
                    @if($showPending)
                        <a href="{{ route('advisers.title-submission', ['mode' => $mode]) }}" class="action-button-secondary">{{ $singleTitle ? 'Edit Title' : 'Edit Titles' }}</a>
                    @endif
                    @if($savedMatches)
                        <a href="{{ route('advisers.matches', ['mode' => $mode]) }}" class="action-button-secondary">View Adviser Matches</a>
                    @endif
                @endunless
            </div>

            @if($showPending && $currentRequests->where('status', 'pending')->isEmpty() && !$activeRequest)
                <p class="content-card text-sm text-gray-600">No adviser requests are pending.</p>
            @endif

            @foreach($showPending ? $currentRequests : $currentRequests->where('status', 'approved') as $currentRequest)
                <div class="mb-6 bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Current adviser request</p>
                            <h3 class="mt-1 text-xl font-semibold text-gray-900">{{ $currentRequest->adviser->name }}</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ $currentRequest->adviser->course }}</p>
                        </div>
                        <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full {{ $currentRequest->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ ucfirst($currentRequest->status) }}
                        </span>
                    </div>

                    @include('partials.adviser-proposals', ['proposals' => $currentRequest->proposals])
                    @if($currentRequest->message)
                        <div class="mt-6">
                            <p class="text-sm font-medium text-gray-700">Your message</p>
                            <p class="mt-1 text-sm text-gray-600 bg-gray-50 border rounded-lg p-3">{{ $currentRequest->message }}</p>
                        </div>
                    @endif

                    @include('partials.adviser-schedule', [
                        'adviser' => $currentRequest->adviser,
                        'class' => 'mt-6 rounded-lg border border-green-100 bg-green-50 p-4',
                        'collapsed' => true,
                    ])

                    @if($currentRequest->response_message)
                        <div class="mt-6">
                            <p class="text-sm font-medium text-gray-700">Adviser response</p>
                            <p class="mt-1 text-sm text-gray-600 bg-gray-50 border rounded-lg p-3">{{ $currentRequest->response_message }}</p>
                        </div>
                    @endif

                    <p class="mt-6 text-sm text-gray-500">
                        Requested on {{ $currentRequest->requested_at->format('M j, Y \a\t g:i A') }}.
                    </p>

                    @if($currentRequest->status === 'pending')
                        <form method="POST" action="{{ route('advisers.requests.remove', $currentRequest) }}" class="mt-6" onsubmit="return confirm('Remove this adviser request?');">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="mode" value="{{ $mode }}">
                            <button type="submit" class="rounded-md bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">
                                Remove Request
                            </button>
                        </form>
                    @else
                        <p class="mt-6 text-sm font-medium text-gray-500">
                            Accepted adviser requests cannot be deleted here.
                        </p>
                    @endif
                </div>
            @endforeach
            @if(!$activeRequest && !$showPending)
                @if($latestRejectedRequest)
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                        Your request to {{ $latestRejectedRequest->adviser->name }} was rejected. You can search again and send another request.
                    </div>
                @endif

            <div class="mb-4">
                <a href="{{ route('advisers.title-submission', $singleTitle ? [] : ['mode' => 'single']) }}"
                   class="inline-flex rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                    {{ $singleTitle ? 'Use Five Proposed Titles' : 'Use an Approved Title' }}
                </a>
            </div>

            <!-- Main Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">

                <form action="{{ route('suggested-ai') }}" method="POST"
                      data-loading-title="{{ $singleTitle ? 'Analyzing your title…' : 'Analyzing your titles…' }}" data-loading-message="Looking for suitable advisers.">
                    @csrf
                    @if($singleTitle)
                        <input type="hidden" name="mode" value="single">
                    @endif

                    <h3 class="font-semibold text-gray-900 mb-4">
                        {{ $singleTitle ? 'Approved Research Title' : 'Proposed Research Titles' }}
                    </h3>

                    <div class="space-y-3">

                        @for($index = 1; $index <= ($singleTitle ? 1 : 5); $index++)
                            @php
                                $field = 'title'.$index;
                                $label = $singleTitle ? 'Approved Research Title' : 'Title '.$index;
                            @endphp
                            <div class="rounded-lg border border-gray-200 p-3">
                                <label for="{{ $field }}" class="mb-1 block text-sm font-medium">{{ $label }}</label>
                                <input id="{{ $field }}" type="text" name="{{ $field }}"
                                       placeholder="{{ $label }}"
                                       value="{{ old($field, $singleTitle ? session('adviser_match_title') : ($submission?->{$field} ?? '')) }}"
                                       class="w-full rounded-md border-gray-300 focus:ring-black focus:border-black"
                                       maxlength="255" required>
                                <label for="{{ $field }}Description" class="mb-1 mt-3 block text-sm font-medium">Description (optional)</label>
                                <textarea id="{{ $field }}Description" name="descriptions[{{ $field }}]" rows="3" maxlength="2000"
                                          class="w-full rounded-md border-gray-300 text-sm">{{ old('descriptions.'.$field, $savedMatches['proposals'][$index - 1]['description'] ?? '') }}</textarea>
                            </div>
                        @endfor
                    </div>

                    <div class="flex justify-center mt-8">
                        <button
                            type="submit"
                            class="bg-black hover:bg-gray-800 text-white px-10 py-3 rounded-md transition"
                        >
                            {{ $singleTitle ? 'Analyze Title' : 'Analyze Titles' }}
                        </button>
                    </div>

                </form>
            </div>

            <!-- Info Box -->
            <div class="mt-6 bg-gray-50 border rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="h-6 w-6 text-gray-600"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M21 12A9 9 0 1112 3a9 9 0 019 9z"/>
                    </svg>

                    <p class="text-sm text-gray-600">
                        The system analyzes keywords, concepts, and research
                        domains in your {{ $singleTitle ? 'title' : 'titles' }} and compares them with advisers'
                        registered expertise.
                    </p>
                </div>
            </div>

            @endif
            @endif

        </div>
    </div>
</x-app-layout>
