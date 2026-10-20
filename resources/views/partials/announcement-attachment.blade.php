@if($announcement->hasImageAttachment())
    <button type="button" onclick="this.nextElementSibling.showModal();"
            aria-label="Enlarge announcement photo" class="mt-3 block w-full cursor-zoom-in">
        <img src="{{ route('announcements.attachment', [$announcement->id, 'preview' => 1]) }}"
             alt="{{ $announcement->attachment_name ?? 'Announcement photo' }}"
             loading="lazy" class="max-h-96 w-full rounded-lg object-contain">
    </button>
    <dialog aria-label="Announcement photo preview"
            onclick="if (event.target === this) this.close();"
            class="m-auto w-full max-w-5xl overflow-y-auto rounded-xl bg-white p-0 shadow-2xl backdrop:bg-black/80">
        <div class="p-4 sm:p-6">
            <div class="mb-4 flex items-center justify-between gap-4">
                <p class="min-w-0 break-all text-sm font-semibold text-gray-900">{{ $announcement->attachment_name ?? 'Announcement photo' }}</p>
                <button type="button" onclick="this.closest('dialog').close();" autofocus
                        class="shrink-0 rounded-lg bg-gray-100 px-4 py-2 text-sm font-bold text-gray-800 hover:bg-gray-200">Close</button>
            </div>
            <img src="{{ route('announcements.attachment', [$announcement->id, 'preview' => 1]) }}"
                 alt="{{ $announcement->attachment_name ?? 'Announcement photo' }}"
                 loading="lazy" class="w-full object-contain" style="max-height: 75vh;">
            <div class="mt-4 flex justify-end">
                <a href="{{ route('announcements.attachment', $announcement->id) }}"
                   onclick="return confirm('Do you want to download this photo?');"
                   class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">Download photo</a>
            </div>
        </div>
    </dialog>
@else
    <a href="{{ route('announcements.attachment', $announcement->id) }}"
       onclick="return confirm('Do you want to download this file?');" class="mt-3 inline-flex max-w-full items-center gap-2 break-all rounded-md bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 hover:bg-blue-100">
        {{ $announcement->attachment_name ?? 'Attachment' }}
    </a>
@endif
