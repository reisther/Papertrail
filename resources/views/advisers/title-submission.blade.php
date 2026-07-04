<x-app-layout>
    @php
        $leaderWithoutGroup = Auth::user()->canLeadGroup() && Auth::user()->ownedProjects()->doesntExist();
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
                        Your adviser request is already submitted. You can submit another set of titles only if the request is rejected.
                    @else
                        Submit up to five (5) proposed thesis titles. PaperTrail's AI
                        will analyze your topics and match them with advisers whose
                        expertise best aligns with your research.
                    @endif
                </p>
            </div>

            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                    {{ $errors->first() }}
                </div>
            @endif

            @if($activeRequest)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Current adviser request</p>
                            <h3 class="mt-1 text-xl font-semibold text-gray-900">{{ $activeRequest->adviser->name }}</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ $activeRequest->adviser->course }}</p>
                        </div>
                        <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full {{ $activeRequest->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ ucfirst($activeRequest->status) }}
                        </span>
                    </div>

                    @if($activeRequest->message)
                        <div class="mt-6">
                            <p class="text-sm font-medium text-gray-700">Your message</p>
                            <p class="mt-1 text-sm text-gray-600 bg-gray-50 border rounded-lg p-3">{{ $activeRequest->message }}</p>
                        </div>
                    @endif

                    @include('partials.adviser-schedule', [
                        'adviser' => $activeRequest->adviser,
                        'class' => 'mt-6 rounded-lg border border-green-100 bg-green-50 p-4',
                        'collapsed' => true,
                    ])

                    @if($activeRequest->response_message)
                        <div class="mt-6">
                            <p class="text-sm font-medium text-gray-700">Adviser response</p>
                            <p class="mt-1 text-sm text-gray-600 bg-gray-50 border rounded-lg p-3">{{ $activeRequest->response_message }}</p>
                        </div>
                    @endif

                    <p class="mt-6 text-sm text-gray-500">
                        Requested on {{ $activeRequest->created_at->format('M j, Y \a\t g:i A') }}.
                    </p>

                    <form method="POST" action="{{ route('advisers.requests.remove', $activeRequest) }}" class="mt-6" onsubmit="return confirm('Remove this adviser request?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-md bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">
                            Remove Request
                        </button>
                    </form>
                </div>
            @else
                @if($latestRejectedRequest)
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                        Your request to {{ $latestRejectedRequest->adviser->name }} was rejected. You can submit another set of titles and request again.
                    </div>
                @endif

            <!-- Main Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">

                <form action="{{ route('suggested-ai') }}" method="POST">
                    @csrf

                    <h3 class="font-semibold text-gray-900 mb-4">
                        Proposed Research Titles
                    </h3>

                    <div class="space-y-3">

                        <input
                            type="text"
                            name="title1"
                            placeholder="Title 1"
                            value="{{ old('title1', $submission->title1 ?? '') }}"
                            class="w-full rounded-md border-gray-300 focus:ring-black focus:border-black"
                            required
                        >

                        <input
                            type="text"
                            name="title2"
                            placeholder="Title 2"
                            value="{{ old('title2', $submission->title2 ?? '') }}"
                            class="w-full rounded-md border-gray-300 focus:ring-black focus:border-black"
                            required
                        >

                        <input
                            type="text"
                            name="title3"
                            placeholder="Title 3"
                            value="{{ old('title3', $submission->title3 ?? '') }}"
                            class="w-full rounded-md border-gray-300 focus:ring-black focus:border-black"
                            required
                        >

                        <input
                            type="text"
                            name="title4"
                            placeholder="Title 4"
                            value="{{ old('title4', $submission->title4 ?? '') }}"
                            class="w-full rounded-md border-gray-300 focus:ring-black focus:border-black"
                            required
                        >

                        <input
                            type="text"
                            name="title5"
                            placeholder="Title 5"
                            value="{{ old('title5', $submission->title5 ?? '') }}"
                            class="w-full rounded-md border-gray-300 focus:ring-black focus:border-black"
                            required
                        >
                    </div>

                    <div class="flex justify-center mt-8">
                        <button
                            type="submit"
                            class="bg-black hover:bg-gray-800 text-white px-10 py-3 rounded-md transition"
                        >
                            Analyze Titles
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
                        domains in your titles and compares them with advisers'
                        registered expertise.
                    </p>
                </div>
            </div>

            @endif
            @endif

        </div>
    </div>
</x-app-layout>
