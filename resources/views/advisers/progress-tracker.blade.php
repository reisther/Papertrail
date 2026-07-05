<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Research Advisees Progress') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white border border-gray-200 rounded-2xl p-8 shadow-sm">
                <div class="flex justify-between items-start mb-8 border-b border-gray-100 pb-5">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 tracking-tight leading-none">Research Advisees Progress</h1>
                        <p class="text-sm text-gray-500 mt-2 font-normal">See how your advisees are doing in their manuscript. Track their progress in completion of their task documentations.</p>
                    </div>
                </div>

                <div class="space-y-6">
                    @forelse($courseGroups as $courseName => $courseAdvisees)
                        <details class="rounded-2xl border border-gray-200 bg-gray-50 p-4" open>
                            <summary class="flex cursor-pointer items-center justify-between gap-4 text-lg font-bold text-gray-900">
                                <span>{{ $courseName }}</span>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-gray-500">{{ $courseAdvisees->count() }} groups</span>
                            </summary>

                            <div class="mt-6 space-y-8">
                                @foreach($courseAdvisees as $advisee)
                                    @php
                                        $p = $advisee->progress;
                                        $textColor = $p < 30 ? 'text-red-500' : ($p < 75 ? 'text-amber-500' : 'text-emerald-500');
                                        $barBgColor = $p < 30 ? '#ef4444' : ($p < 75 ? '#f59e0b' : '#10b981');
                                    @endphp

                                    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                                        <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                                            <div>
                                                <h3 class="text-lg font-bold text-gray-900">{{ $advisee->groupName }}</h3>
                                                <p class="text-xs text-gray-500">Owner: {{ $advisee->ownerName }}</p>
                                            </div>
                                        </div>
                                        <div class="w-full mb-6">
                                            <div class="text-center mb-1">
                                                <span class="text-sm font-bold {{ $textColor }}">
                                                    {{ $p }}%
                                                </span>
                                            </div>
                                            <div class="w-full bg-gray-200 h-3 rounded-full overflow-hidden">
                                                <div class="h-full transition-all duration-500"
                                                     style="width: {{ $p }}%; background-color: {{ $barBgColor }};">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                                            @foreach($advisee->chapters as $chapter)
                                                <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                                                    <h4 class="text-sm font-bold text-gray-800 truncate">{{ $chapter->name }}</h4>
                                                    <p class="text-xs text-gray-500 mt-1">{{ $chapter->status }}</p>
                                                    <p class="mt-2 text-lg font-bold text-gray-900">{{ $chapter->contribution }}%</p>
                                                    <p class="mb-3 text-[11px] text-gray-400">
                                                        @if($chapter->totalTasks > 0)
                                                            {{ $chapter->totalTasks }} {{ Str::plural('task', $chapter->totalTasks) }}
                                                        @else
                                                            No tasks yet
                                                        @endif
                                                    </p>
                                                    <a href="{{ route('advisers.todo', ['chapterName' => $chapter->name, 'project_id' => $advisee->projectId]) }}"
                                                       class="text-xs text-emerald-600 font-bold hover:underline">
                                                        View List
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @empty
                        <div class="text-center py-10 text-gray-400">No active research advisees.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
