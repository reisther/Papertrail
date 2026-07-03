<x-app-layout>
    <div x-data="{
        showModal: false,
        taskCount: 1,
        get tasks() { return Array.from({ length: this.taskCount }, (_, i) => i + 1) }
    }">
        @php
            $defaultChapter = (int) filter_var($chapterName ?? '', FILTER_SANITIZE_NUMBER_INT);
            $defaultChapter = $defaultChapter >= 1 && $defaultChapter <= 5 ? $defaultChapter : 1;
        @endphp
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg p-8">
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
                        <a href="{{ $canManageTasks ? route('teacher.dashboard') : route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-semibold rounded-md transition">Back</a>
                    </div>

                    @if($projects->isNotEmpty())
                        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            @if($canToggleTasks)
                                <form method="GET" action="{{ route('todo.index') }}" class="flex min-w-0 flex-1 items-center gap-3">
                                    <label for="chapter" class="shrink-0 text-sm font-semibold text-gray-700">Chapter</label>
                                    <select id="chapter" name="chapter" onchange="this.form.submit()" class="min-w-0 flex-1 rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        @foreach(range(1, 5) as $chapterOption)
                                            <option value="{{ $chapterOption }}" @selected(($selectedChapter ?? 1) === $chapterOption)>
                                                Chapter {{ $chapterOption }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            @else
                                <form method="GET" action="{{ route('todo.index') }}" class="flex min-w-0 flex-1 items-center gap-3">
                                    <label for="project_id" class="shrink-0 text-sm font-semibold text-gray-700">Group Code</label>
                                    <select id="project_id" name="project_id" onchange="this.form.submit()" class="min-w-0 flex-1 rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        @foreach($projects as $project)
                                            <option value="{{ $project->id }}" @selected($selectedProject && $selectedProject->id === $project->id)>
                                                {{ $project->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            @endif

                            @if($canManageTasks)
                                <button @click="showModal = true" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md shadow-sm transition">
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
                                $chapterContribution = $total > 0 ? round(($completed / $total) * 20, 2) : 0;
                            @endphp
                            <div class="mb-4 flex items-end justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">Chapter {{ $chapter }}</h3>
                                    <p class="text-sm text-gray-500">{{ $completed }}/{{ $total }} tasks completed</p>
                                </div>
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700">
                                    @if($canToggleTasks)
                                        {{ $chapterProgress }}% completed
                                    @else
                                        {{ $chapterContribution }}% of total progress
                                    @endif
                                </span>
                            </div>
                            <div class="bg-white border border-gray-200 rounded-lg p-6 mb-8 shadow-sm">
                                <div class="space-y-3">
                                    @foreach($tasks as $task)
                                        <div class="p-4 border border-gray-100 rounded-md">
                                            @if($canManageTasks)
                                                <form method="POST" action="{{ route('todo.update', $task) }}" class="grid grid-cols-1 gap-3 sm:grid-cols-[8rem_1fr_auto_auto] sm:items-center">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select name="chapter" class="rounded-md border-gray-200 bg-gray-50 text-sm">
                                                        @foreach(range(1, 5) as $editChapter)
                                                            <option value="{{ $editChapter }}" @selected($task->chapter === $editChapter)>Chapter {{ $editChapter }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="text" name="title" value="{{ $task->title }}" class="rounded-md border-gray-200 bg-gray-50 text-sm" required>
                                                    <div class="text-xs {{ $task->is_completed ? 'text-emerald-600' : 'text-gray-400 italic' }}">
                                                        <span>{{ $task->is_completed ? 'Completed by leader' : 'Waiting for leader' }}</span>
                                                        @if($task->completion_note)
                                                            <span class="block text-gray-500 not-italic">{{ $task->completion_note }}</span>
                                                        @endif
                                                    </div>
                                                    <button type="submit" class="w-20 rounded-md bg-blue-600 px-3 py-2 text-center text-xs font-semibold text-white hover:bg-blue-700">Save</button>
                                                </form>
                                                <form method="POST" action="{{ route('todo.destroy', $task) }}" class="mt-2 flex justify-end">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="w-20 rounded-md bg-red-600 px-3 py-2 text-center text-xs font-semibold text-white hover:bg-red-700">
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
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span class="{{ $task->is_completed ? 'text-gray-400 line-through' : 'text-gray-700' }}">{{ $task->title }}</span>
                                                        <span class="text-xs {{ $task->is_completed ? 'text-emerald-600' : 'text-gray-400 italic' }}">
                                                            {{ $task->is_completed ? 'Completed' : 'Not yet checked' }}
                                                        </span>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @if($canManageTasks && $selectedProject)
                                <form method="POST" action="{{ route('todo.store') }}" class="mt-4 rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4">
                                    @csrf
                                    @if($selectedProject->group_course)
                                        <input type="hidden" name="assignment_scope" value="course">
                                        <input type="hidden" name="course" value="{{ $selectedProject->group_course }}">
                                    @else
                                        <input type="hidden" name="assignment_scope" value="project">
                                        <input type="hidden" name="project_id" value="{{ $selectedProject->id }}">
                                    @endif
                                    <input type="hidden" name="chapter" value="{{ $chapter }}">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                                        <label class="text-xs font-bold text-gray-600 sm:w-24 sm:shrink-0">Add Task</label>
                                        <input type="text" name="tasks[]" class="w-full min-w-0 rounded-md border-gray-200 bg-white text-sm sm:flex-1" placeholder="New task for Chapter {{ $chapter }}" required>
                                        <button type="submit" class="w-full rounded-md bg-gray-800 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-900 sm:w-auto">Add</button>
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
                            <label class="text-xs font-bold text-gray-700 sm:w-24 sm:shrink-0">Chapter</label>
                            <select name="chapter" class="w-full min-w-0 border-gray-200 bg-gray-50 rounded-lg py-2 px-3 text-sm sm:flex-1" required>
                                @foreach(range(1, 5) as $chapter)
                                    <option value="{{ $chapter }}" @selected($chapter === $defaultChapter)>Chapter {{ $chapter }}</option>
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
