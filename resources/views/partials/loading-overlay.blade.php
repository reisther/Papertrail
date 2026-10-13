<dialog id="page-loading" aria-labelledby="page-loading-title" aria-describedby="page-loading-description"
        class="w-[calc(100%-2rem)] max-w-sm rounded-2xl border-0 bg-white p-8 text-center shadow-2xl backdrop:bg-slate-950/60">
    <div role="status" aria-live="polite">
        <svg class="mx-auto mb-5 h-12 w-12 animate-spin text-blue-600 motion-reduce:animate-none" aria-hidden="true" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <h2 id="page-loading-title" class="text-lg font-bold text-slate-900">Loading…</h2>
        <p id="page-loading-description" class="mt-2 text-sm leading-6 text-slate-600">Please wait a moment.</p>
    </div>
</dialog>

<script>
    (() => {
        const dialog = document.getElementById('page-loading');
        const title = document.getElementById('page-loading-title');
        const description = document.getElementById('page-loading-description');
        let pendingForm = null;

        // Keep the popup visible until the server returns a new page.
        dialog.addEventListener('cancel', event => event.preventDefault());
        document.addEventListener('submit', event => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || form.target === '_blank' || form.method === 'dialog') return;
            if (pendingForm) {
                event.preventDefault();
                return;
            }

            // Allow confirmation dialogs and other submit handlers to cancel first.
            queueMicrotask(() => {
                if (event.defaultPrevented) return;
                pendingForm = form;
                title.textContent = form.dataset.loadingTitle || 'Loading…';
                description.textContent = form.dataset.loadingMessage || 'Please wait a moment.';
                dialog.showModal();
            });
        });

        // Browsers can restore the previous page, including its open popup.
        window.addEventListener('pageshow', () => {
            dialog.close();
            pendingForm = null;
        });
    })();
</script>
