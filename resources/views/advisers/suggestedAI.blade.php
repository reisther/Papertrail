<x-app-layout>
    <div class="app-page">
        <div class="content-card" x-data="{ adviserId: '', adviserName: '' }">
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
                @endphp
                <section class="mt-6">
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">AI Suggested Advisers</h2>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($recommendedAdvisers as $adviser)
                                @php
                                    $existing = $currentRequests->firstWhere('adviser_id', $adviser->id);
                                @endphp
                                <div class="flex min-w-0 flex-col rounded-lg border-2 border-green-500 bg-green-50 p-4 sm:p-6">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="flex min-w-0 items-center gap-2">
                                            @include('partials.user-avatar', ['user' => $adviser, 'size' => 'h-10 w-10', 'textSize' => 'text-sm', 'bg' => 'bg-green-100', 'text' => 'text-green-700'])
                                            <h2 class="min-w-0 break-words font-semibold">{{ $adviser->name }}</h2>
                                        </div>
                                        <span class="rounded-full bg-green-700 px-2 py-1 text-xs font-semibold text-white">{{ $adviser->match_score }}%</span>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-600">{{ $adviser->course }}</p>
                                    <p class="mb-3 mt-2 break-words text-sm text-gray-700">{{ $adviser->reason }}</p>
                                    <h3 class="mb-2 text-sm font-semibold text-gray-900">Specializations</h3>
                                    <ul class="mb-4 flex flex-wrap gap-2 text-xs">
                                        @forelse($specializations[$adviser->id] as $specialization)
                                            <li class="max-w-full break-words rounded-full bg-white px-2.5 py-1 font-semibold text-green-700 ring-1 ring-green-200">{{ $specialization }}</li>
                                        @empty
                                            <li class="text-gray-600">No specializations listed.</li>
                                        @endforelse
                                    </ul>
                                    @if($existing || $assigned)
                                        <button type="button" disabled class="mt-auto w-full rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-600">
                                            {{ $existing?->status === 'pending' ? 'Pending Approval' : 'Adviser Assigned' }}
                                        </button>
                                    @else
                                        <button type="button" class="mt-auto w-full rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700"
                                                @click="adviserId = @js($adviser->id); adviserName = @js($adviser->name); $refs.requestMessage.value = ''; $refs.requestDialog.showModal()">
                                            Send Request
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                </section>

                <dialog x-ref="requestDialog" aria-labelledby="adviserRequestTitle"
                        class="w-[calc(100%-2rem)] max-w-md rounded-xl bg-white p-5 shadow-xl backdrop:bg-gray-900/60">
                    <form method="POST" action="{{ route('advisers.send-request') }}">
                        @csrf
                        <input type="hidden" name="mode" value="{{ $mode }}">
                        <input type="hidden" name="match_token" value="{{ $match['token'] }}">
                        <input type="hidden" name="adviser_ids[]" :value="adviserId">
                        <h2 id="adviserRequestTitle" class="break-words text-lg font-semibold">Send Request to <span x-text="adviserName"></span></h2>
                        <label for="requestMessage" class="mb-1 mt-4 block text-sm font-medium">Message (optional)</label>
                        <textarea id="requestMessage" x-ref="requestMessage" name="message" rows="3" maxlength="500" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                        <div class="mt-4 flex flex-wrap justify-end gap-2">
                            <button type="button" class="action-button-secondary" @click="$refs.requestDialog.close()">Cancel</button>
                            <button type="submit" class="action-button">Send Request</button>
                        </div>
                    </form>
                </dialog>
            @else
                <p class="mt-5 rounded-lg bg-yellow-50 p-4 text-sm text-yellow-900">No close adviser expertise match was found. You can edit your research titles and try again.</p>
            @endif
        </div>
    </div>
</x-app-layout>
