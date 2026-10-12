<x-app-layout>
    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl bg-white p-5 shadow-sm sm:p-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-blue-600">Group checklist</p>
                        <h1 class="mt-1 text-2xl font-bold text-slate-900">Defense Revisions</h1>
                        <p class="mt-2 text-sm text-slate-600">Record each panel suggestion by chapter, then note what changed and the page where the revision appears. Leaders maintain this list; advisers and members can follow the progress.</p>
                    </div>
                    <a href="{{ auth()->user()->isTeacher() ? route('teacher.dashboard') : route('dashboard') }}" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Back</a>
                </div>

                <div class="mt-6 flex flex-wrap gap-2 border-b border-slate-200 pb-4" aria-label="Checklist sections">
                    <a href="{{ route('todo.index', array_filter(['project_id' => $selectedProject?->id, 'chapter' => $selectedChapter], fn ($value) => $value !== null)) }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Adviser Tasks</a>
                    <span class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white" aria-current="page">Defense Revisions</span>
                </div>

                @if(session('success'))
                    <p class="mt-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</p>
                @endif

                @if($errors->any())
                    <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <p class="font-semibold">Please check the revision details:</p>
                        <ul class="mt-1 list-inside list-disc">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($projects->isEmpty())
                    <p class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">No group project is available yet. Create a group or join an advised group to use defense revisions.</p>
                @else
                    <form method="GET" action="{{ route('defense-revisions.index') }}" class="mt-6 flex flex-col gap-2 sm:flex-row sm:items-center">
                        <label for="revision_project_id" class="text-sm font-semibold text-slate-700">Group</label>
                        <input type="hidden" name="chapter" value="{{ $selectedChapter }}">
                        <select id="revision_project_id" name="project_id" onchange="this.form.submit()" class="w-full rounded-lg border-slate-300 text-sm sm:max-w-sm">
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" @selected($selectedProject?->id === $project->id)>{{ $project->title }}</option>
                            @endforeach
                        </select>
                    </form>

                    <nav class="mt-6 flex flex-wrap gap-2" aria-label="Manuscript chapters">
                        @foreach($stages as $chapter => $stageName)
                            <a href="{{ route('defense-revisions.index', ['project_id' => $selectedProject->id, 'chapter' => $chapter]) }}"
                               class="rounded-full px-3 py-2 text-xs font-semibold {{ $selectedChapter === $chapter ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                               @if($selectedChapter === $chapter) aria-current="page" @endif>
                                {{ $stageName }} <span class="opacity-75">({{ $chapterCounts[$chapter] ?? 0 }})</span>
                            </a>
                        @endforeach
                    </nav>

                    <div class="mt-8 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">{{ $stages[$selectedChapter] }}</h2>
                            <p class="text-sm text-slate-500">{{ $revisions->where('is_completed', true)->count() }} of {{ $revisions->count() }} suggestions revised</p>
                        </div>
                        @if($selectedProject->isArchivedForUser(auth()->user()))
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Archived · view only</span>
                        @endif
                    </div>

                    @if($canManage)
                        <form method="POST" action="{{ route('defense-revisions.store') }}" class="mt-5 rounded-xl border border-blue-200 bg-blue-50 p-4 sm:p-5">
                            @csrf
                            <input type="hidden" name="project_id" value="{{ $selectedProject->id }}">
                            <input type="hidden" name="chapter" value="{{ $selectedChapter }}">
                            <label for="panel_name" class="block text-sm font-bold text-slate-800">Panel / panelist name</label>
                            <input id="panel_name" name="panel_name" type="text" maxlength="150" value="{{ old('_editing_revision_id') ? '' : old('panel_name') }}" required class="mt-2 block w-full rounded-lg border-slate-300 text-sm" placeholder="e.g., Dr. Cruz or Final Defense Panel">
                            <label for="panel_suggestion" class="mt-4 block text-sm font-bold text-slate-800">Add panel suggestion</label>
                            <p class="mt-1 text-xs text-slate-600">Enter one comment or requested change from the defense panel. You can add the revision and page number afterward.</p>
                            <textarea id="panel_suggestion" name="panel_suggestion" rows="3" maxlength="5000" required class="mt-3 block w-full rounded-lg border-slate-300 text-sm" placeholder="Example: Clarify the sampling method in Chapter 3.">{{ old('_editing_revision_id') ? '' : old('panel_suggestion') }}</textarea>
                            <div class="mt-3 flex justify-end">
                                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">+ Add to this chapter</button>
                            </div>
                        </form>
                    @endif

                    <div class="mt-6 space-y-4">
                        @forelse($revisions as $revision)
                            @php($isEditingAfterError = (string) old('_editing_revision_id') === (string) $revision->id)
                            <article x-data="{ editing: @js($isEditingAfterError) }" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-bold text-slate-900">Panel: {{ $revision->panel_name ?: 'Not recorded' }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Added by {{ $revision->creator?->name ?? 'Deleted User' }} · {{ $revision->created_at->format('M j, Y') }}</p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $revision->is_completed ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ $revision->is_completed ? 'Revised' : 'To do' }}</span>
                                        @if($canManage)
                                            <button type="button" x-on:click="editing = ! editing" x-bind:aria-expanded="editing.toString()" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100">Edit</button>
                                            <form method="POST" action="{{ route('defense-revisions.destroy', $revision) }}" onsubmit="return confirm('Delete this defense revision task and its comment and page details?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-700 hover:bg-red-100">Delete task</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                <div x-show="!editing" class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-slate-50 text-sm">
                                    <section class="p-4">
                                        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Panel suggestion / comment</h3>
                                        <p class="mt-2 whitespace-pre-wrap leading-6 text-slate-900">{{ $revision->panel_suggestion }}</p>
                                    </section>
                                    <section class="border-t border-slate-200 p-4">
                                        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Revision comment: what was revised</h3>
                                        <p class="mt-2 whitespace-pre-wrap leading-6 text-slate-900">{{ $revision->revision_note ?: 'Not recorded yet.' }}</p>
                                    </section>
                                    <section class="border-t border-slate-200 p-4">
                                        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Page number(s)</h3>
                                        <p class="mt-2 text-slate-900">{{ $revision->page_reference ?: 'Not recorded yet.' }}</p>
                                    </section>
                                </div>

                                @if($canManage)
                                    <form x-show="editing" style="display: none;" method="POST" action="{{ route('defense-revisions.update', $revision) }}" class="mt-4 space-y-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="_editing_revision_id" value="{{ $revision->id }}">
                                        <div>
                                            <label for="panel_name_{{ $revision->id }}" class="block text-sm font-semibold text-slate-700">Panel / panelist name</label>
                                            <input id="panel_name_{{ $revision->id }}" name="panel_name" type="text" maxlength="150" value="{{ $isEditingAfterError ? old('panel_name') : $revision->panel_name }}" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm" placeholder="e.g., Dr. Cruz or Final Defense Panel">
                                        </div>
                                        <div>
                                            <label for="suggestion_{{ $revision->id }}" class="block text-sm font-semibold text-slate-700">Panel suggestion / comment</label>
                                            <textarea id="suggestion_{{ $revision->id }}" name="panel_suggestion" rows="2" maxlength="5000" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm">{{ $isEditingAfterError ? old('panel_suggestion') : $revision->panel_suggestion }}</textarea>
                                        </div>
                                        <div>
                                            <label for="revision_{{ $revision->id }}" class="block text-sm font-semibold text-slate-700">Revision comment: what was revised</label>
                                            <textarea id="revision_{{ $revision->id }}" name="revision_note" rows="3" maxlength="5000" class="mt-1 block w-full rounded-lg border-slate-300 text-sm" placeholder="Describe the change made in the manuscript.">{{ $isEditingAfterError ? old('revision_note') : $revision->revision_note }}</textarea>
                                        </div>
                                        <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
                                            <div>
                                                <label for="pages_{{ $revision->id }}" class="block text-sm font-semibold text-slate-700">Page number(s)</label>
                                                <input id="pages_{{ $revision->id }}" name="page_reference" type="text" maxlength="100" value="{{ $isEditingAfterError ? old('page_reference') : $revision->page_reference }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm" placeholder="e.g., 12 or 12–14">
                                            </div>
                                            <label class="flex items-center gap-2 pb-2 text-sm font-semibold text-slate-700">
                                                <input type="hidden" name="is_completed" value="0">
                                                <input type="checkbox" name="is_completed" value="1" @checked($isEditingAfterError ? old('is_completed') : $revision->is_completed) class="rounded border-slate-300 text-blue-600">
                                                Mark revised
                                            </label>
                                        </div>
                                        <p class="text-xs text-slate-500">A revision comment and page number are required when marking an item revised.</p>
                                        <div class="flex justify-end gap-2">
                                            <button type="button" x-on:click="editing = false" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                                            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Save revision</button>
                                        </div>
                                    </form>
                                @endif
                            </article>
                        @empty
                            <p class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">No panel suggestions recorded for {{ $stages[$selectedChapter] }} yet.</p>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
