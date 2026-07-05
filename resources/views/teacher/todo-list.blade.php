<x-app-layout>
    <div x-data="{
        showModal: false,
        taskCount: 1,
        get tasks() { return Array.from({ length: this.taskCount }, (_, i) => i + 1) }
    }">
        @php
            $manuscriptStages = $manuscriptStages ?? collect([
                0 => 'Concept Paper',
                1 => 'Chapter 1',
                2 => 'Chapter 2',
                3 => 'Chapter 3',
                4 => 'Chapter 4',
                5 => 'Chapter 5',
            ]);
            $stageWeight = 100 / max($manuscriptStages->count(), 1);
            $defaultChapter = $manuscriptStages->search($chapterName ?? '', strict: true);
            $defaultChapter = $defaultChapter !== false
                ? (int) $defaultChapter
                : (int) filter_var($chapterName ?? '', FILTER_SANITIZE_NUMBER_INT);
            $defaultChapter = $manuscriptStages->has($defaultChapter) ? $defaultChapter : 0;
        @endphp
        <div class="py-8 sm:py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg p-5 sm:p-8">
                    <div class="flex flex-col gap-4 mb-8 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">
                                {{ $canManageTasks ? (($selectedProject?->title ?? 'Group Code') . ' To-Do Lists') : 'Group To-Do Lists' }}
                            </h2>
                            <p class="text-sm text-gray-500 mt-1">
                                @if($canManageTasks)
                                    Create and revise per-chapter tasks for students.
                                @else
                                    Mark tasks complete as your group finishes them.
                                @endif
                            </p>
                        </div>
                        <a href="{{ $canManageTasks ? route('teacher.dashboard') : route('dashboard') }}" class="inline-flex w-full items-center justify-center rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-300 sm:w-auto">Back</a>
                    </div>

                    @if($projects->isNotEmpty())
                        <div class="mb-8 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            @if($canFilterTasks ?? $canToggleTasks)
                                <form method="GET" action="{{ route('todo.index') }}" class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                                    <label for="chapter" class="shrink-0 text-sm font-semibold text-gray-700">Task Stage</label>
                                    <select id="chapter" name="chapter" onchange="this.form.submit()" class="w-full min-w-0 rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 sm:flex-1">
                                        @foreach($manuscriptStages as $chapterOption => $stageName)
                                            <option value="{{ $chapterOption }}" @selected(($selectedChapter ?? 0) === $chapterOption)>
                                                {{ $stageName }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            @else
                                <form method="GET" action="{{ route('todo.index') }}" class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                                    <label for="project_id" class="shrink-0 text-sm font-semibold text-gray-700">Group Code</label>
                                    <select id="project_id" name="project_id" onchange="this.form.submit()" class="w-full min-w-0 rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 sm:flex-1">
                                        @foreach($projects as $project)
                                            <option value="{{ $project->id }}" @selected($selectedProject && $selectedProject->id === $project->id)>
                                                {{ $project->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            @endif

                            @if($canManageTasks)
                                <button @click="showModal = true" class="inline-flex w-full items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 sm:w-auto">
                                    + Create List
                                </button>
                            @endif
                        </div>
                    @else
                        <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            {{ $canManageTasks ? 'No advised groups yet. Assign a group to this adviser before creating task lists.' : 'No adviser-created tasks are available for your group yet.' }}
                        </p>
                    @endif

                    <div class="mt-10 pt-6">
                        @forelse($todos as $chapter => $tasks)
                            @php
                                $completed = $tasks->where('is_completed', true)->count();
                                $total = $tasks->count();
                                $chapterProgress = $total > 0 ? round(($completed / $total) * 100, 2) : 0;
                                $chapterContribution = $total > 0 ? round(($completed / $total) * $stageWeight, 2) : 0;
                                $stageName = $manuscriptStages[(int) $chapter] ?? "Chapter {$chapter}";
                            @endphp
                            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between sm:gap-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">{{ $stageName }}</h3>
                                    <p class="text-sm text-gray-500">{{ $completed }}/{{ $total }} tasks completed</p>
                                </div>
                                <span class="w-fit rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700">
                                    @if($canToggleTasks)
                                        {{ $chapterProgress }}% completed
                                    @else
                                        {{ $chapterContribution }}% of total progress
                                    @endif
                                </span>
                            </div>
                            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                                <div class="space-y-3">
                                    @foreach($tasks as $task)
                                        <div class="p-4 border border-gray-100 rounded-md">
                                            @if($canManageTasks)
                                                <form method="POST" action="{{ route('todo.update', $task) }}" class="grid grid-cols-1 gap-3 lg:grid-cols-[8rem_minmax(0,1fr)_8rem_5rem] lg:items-center">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select name="chapter" class="w-full min-w-0 rounded-md border-gray-200 bg-gray-50 text-sm">
                                                        @foreach($manuscriptStages as $editChapter => $editStageName)
                                                            <option value="{{ $editChapter }}" @selected($task->chapter === $editChapter)>{{ $editStageName }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="text" name="title" value="{{ $task->title }}" class="w-full min-w-0 rounded-md border-gray-200 bg-gray-50 text-sm" required>
                                                    <div class="text-xs {{ $task->is_completed ? 'text-emerald-600' : 'text-gray-400 italic' }}">
                                                        <span>{{ $task->is_completed ? 'Completed by leader' : 'Waiting for leader' }}</span>
                                                        @if($task->completion_note)
                                                            <span class="block text-gray-500 not-italic">{{ $task->completion_note }}</span>
                                                        @endif
                                                    </div>
                                                    <button type="submit" class="w-full rounded-md bg-blue-600 px-3 py-2 text-center text-xs font-semibold text-white hover:bg-blue-700 lg:w-20">Save</button>
                                                </form>
                                                <form method="POST" action="{{ route('todo.destroy', $task) }}" class="mt-2 flex justify-end">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="w-full rounded-md bg-red-600 px-3 py-2 text-center text-xs font-semibold text-white hover:bg-red-700 lg:w-20">
                                                        Delete
                                                    </button>
                                                </form>
                                            @else
                                                @if($canToggleTasks)
                                                    <form method="POST" action="{{ route('todo.toggle', $task) }}" class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_16rem_auto] sm:items-center">
                                                        @csrf
                                                        @method('PATCH')
                                                        <label class="flex items-center gap-3">
                                                            <input type="hidden" name="is_completed" value="0">
                                                            <input type="checkbox" name="is_completed" value="1" @checked($task->is_completed) class="rounded border-gray-300 text-blue-600">
                                                            <span class="{{ $task->is_completed ? 'text-gray-400 line-through' : 'text-gray-700' }}">{{ $task->title }}</span>
                                                        </label>
                                                        <select name="completion_user_id" class="rounded-md border-gray-200 bg-gray-50 text-sm">
                                                            <option value="">Who finished this?</option>
                                                            @foreach($completionUsers ?? collect() as $completionUser)
                                                                <option value="{{ $completionUser->id }}" @selected($task->completion_note === $completionUser->name)>
                                                                    {{ $completionUser->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <button type="submit" class="rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Update</button>
                                                    </form>
                                                @else
                                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                                                        <span class="{{ $task->is_completed ? 'text-gray-400 line-through' : 'text-gray-700' }}">{{ $task->title }}</span>
                                                        <div class="text-xs sm:text-right {{ $task->is_completed ? 'text-emerald-600' : 'text-gray-400 italic' }}">
                                                            <span>{{ $task->is_completed ? 'Completed' : 'Not yet checked' }}</span>
                                                            @if($task->completion_note)
                                                                <span class="block text-gray-500 not-italic">Finished by {{ $task->completion_note }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @if($canManageTasks && $selectedProject)
                                <form method="POST" action="{{ route('todo.store') }}" class="mb-12 mt-6 rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4">
                                    @csrf
                                    @if($selectedProject->group_course)
                                        <input type="hidden" name="assignment_scope" value="course">
                                        <input type="hidden" name="course" value="{{ $selectedProject->group_course }}">
                                    @else
                                        <input type="hidden" name="assignment_scope" value="project">
                                        <input type="hidden" name="project_id" value="{{ $selectedProject->id }}">
                                    @endif
                                    <input type="hidden" name="chapter" value="{{ $chapter }}">
                                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:gap-3">
                                        <label class="text-xs font-bold text-gray-600 md:w-24 md:shrink-0">Add Task</label>
                                        <input type="text" name="tasks[]" class="w-full min-w-0 rounded-md border-gray-200 bg-white text-sm sm:flex-1" placeholder="New task for {{ $stageName }}" required>
                                        <button type="submit" class="w-full rounded-md bg-gray-800 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-900 md:w-auto">Add</button>
                                    </div>
                                </form>
                            @endif
                        @empty
                            <p class="text-gray-400 text-sm italic">No to-do lists created yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        @if($canManageTasks)
        <div x-show="showModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center px-4 bg-gray-900/40 backdrop-blur-sm" x-transition>
            <div @click="showModal = false" class="fixed inset-0"></div>
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-5 sm:p-6 relative z-10 max-h-[85vh] overflow-y-auto">
                <h3 class="text-lg font-bold text-gray-900 mb-6">Create To-Do List</h3>
                <form action="{{ route('todo.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="assignment_scope" value="course">
                    <div class="space-y-4">
                        <div class="flex flex-col gap-1.5 sm:flex-row sm:items-center sm:gap-4">
                            <label class="text-xs font-bold text-gray-700 sm:w-24 sm:shrink-0">Assign To</label>
                            <select name="course" class="w-full min-w-0 border-gray-200 bg-gray-50 rounded-lg py-2 px-3 text-sm sm:flex-1" required>
                                @foreach($courses as $course)
                                    <option value="{{ $course }}">{{ $course }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-1.5 sm:flex-row sm:items-center sm:gap-4">
                            <label class="text-xs font-bold text-gray-700 sm:w-24 sm:shrink-0">Task Stage</label>
                            <select name="chapter" class="w-full min-w-0 border-gray-200 bg-gray-50 rounded-lg py-2 px-3 text-sm sm:flex-1" required>
                                @foreach($manuscriptStages as $chapter => $stageName)
                                    <option value="{{ $chapter }}" @selected($chapter === $defaultChapter)>{{ $stageName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-1.5 sm:flex-row sm:items-center sm:gap-4">
                            <label class="text-xs font-bold text-gray-700 sm:w-24 sm:shrink-0"># of Tasks</label>
                            <input type="number" x-model="taskCount" min="1" class="w-full min-w-0 border-gray-200 bg-gray-50 rounded-lg py-2 px-3 text-sm sm:flex-1">
                        </div>
                        <div class="space-y-3 pt-2 border-t mt-4">
                            <template x-for="i in tasks" :key="i">
                                <div class="flex flex-col gap-1.5 sm:flex-row sm:items-center sm:gap-4">
                                    <label class="text-xs font-bold text-gray-700 sm:w-24 sm:shrink-0">Task <span x-text="i"></span></label>
                                    <input type="text" name="tasks[]" class="w-full min-w-0 border-gray-200 bg-gray-50 rounded-lg py-2 px-3 text-sm sm:flex-1" required>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t mt-6 pt-4 sm:flex-row sm:items-center sm:justify-end">
                        <button type="button" @click="showModal = false" class="px-4 py-2 bg-gray-100 text-gray-700 text-xs font-bold rounded-lg">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded-lg">Create</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>
</x-app-layout>
