<x-app-layout>
    <div class="app-page">
        <div class="content-card">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="app-heading">Select Advisers</h1>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('advisers.title-submission', ['mode' => $mode, 'tab' => 'pending']) }}" class="action-button-secondary">
                        Pending Approval ({{ $currentRequests->where('status', 'pending')->count() }})
                    </a>
                    <a href="{{ route('advisers.title-submission', ['mode' => $mode]) }}" class="action-button-secondary">
                        {{ $singleTitle ? 'Edit Title' : 'Edit Titles' }}
                    </a>
                </div>
            </div>

            @if($errors->any())
                <p role="alert" class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-800">{{ $errors->first() }}</p>
            @endif

            @include('partials.adviser-proposals', ['proposals' => $match['proposals']])

            @if($recommendedAdvisers->isNotEmpty())
                @php
                    $assigned = $currentRequests->contains('status', 'approved');
                    $availableIds = $assigned ? [] : $recommendedAdvisers->whereNotIn('id', $currentRequests->pluck('adviser_id'))->pluck('id')->map(fn ($id) => (string) $id)->all();
                @endphp
                <form method="POST" action="{{ route('advisers.send-request') }}" class="mt-6" x-data="{ selected: [] }">
                    @csrf
                    <input type="hidden" name="mode" value="{{ $mode }}">
                    <input type="hidden" name="match_token" value="{{ $match['token'] }}">
                    <fieldset>
                        <legend class="text-lg font-semibold text-gray-900">AI Suggested Advisers</legend>
                        <div class="mb-4 mt-1 flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm text-gray-600">Select everyone you’d like to request, then send your requests together.</p>
                            @if(count($availableIds) > 1)
                                <button type="button" class="action-button-secondary"
                                        @click="selected = @js($availableIds)">Select All Available</button>
                            @endif
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($recommendedAdvisers as $adviser)
                                @php
                                    $existing = $currentRequests->firstWhere('adviser_id', $adviser->id);
                                @endphp
                                <div class="min-w-0 rounded-xl border border-green-200 bg-green-50 p-4">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="flex min-w-0 items-center gap-2">
                                            @include('partials.user-avatar', ['user' => $adviser, 'size' => 'h-10 w-10', 'textSize' => 'text-sm', 'bg' => 'bg-green-100', 'text' => 'text-green-700'])
                                            <h2 class="min-w-0 break-words font-semibold">{{ $adviser->name }}</h2>
                                        </div>
                                        <span class="rounded-full bg-green-700 px-2 py-1 text-xs font-semibold text-white">{{ $adviser->match_score }}%</span>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-600">{{ $adviser->course }}</p>
                                    <p class="mb-3 mt-2 break-words text-sm text-gray-700">{{ $adviser->reason }}</p>
                                    @if($existing || $assigned)
                                        <p class="text-sm font-semibold text-gray-600">{{ $existing?->status === 'pending' ? 'Pending Approval' : 'Adviser Assigned' }}</p>
                                    @else
                                        <label class="flex min-h-11 cursor-pointer items-center gap-2 text-sm font-semibold">
                                            <input type="checkbox" name="adviser_ids[]" value="{{ $adviser->id }}" x-model="selected" class="rounded border-gray-300 text-green-700">
                                            Select adviser
                                        </label>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                    @if($availableIds)
                        <label for="requestMessage" class="mb-1 mt-5 block text-sm font-medium">Message to selected advisers (optional)</label>
                        <textarea id="requestMessage" name="message" rows="3" maxlength="500" class="w-full max-w-xl rounded-md border-gray-300 text-sm">{{ old('message') }}</textarea>
                        <div class="mt-3">
                            <button type="submit" class="action-button">Send Selected Requests</button>
                        </div>
                    @endif
                </form>
            @else
                <p class="mt-5 rounded-lg bg-yellow-50 p-4 text-sm text-yellow-900">No close adviser expertise match was found. You can edit your research titles and try again.</p>
            @endif
        </div>
    </div>
</x-app-layout>
