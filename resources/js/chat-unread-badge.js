function startChatUnreadBadgeUpdates() {
    const navigation = document.querySelector('[data-chat-unread-url]');
    if (!navigation || navigation.dataset.chatUnreadManagedByPage === 'true') return;

    const links = navigation.querySelectorAll('[data-chat-nav-link]');
    if (!links.length) return;

    let requestInProgress = false;
    let pollingInterval;

    const updateBadges = (count) => {
        const unreadCount = Math.max(0, Number(count) || 0);

        links.forEach((link) => {
            let badge = link.querySelector('.chat-nav-unread-count');

            if (unreadCount === 0) {
                badge?.remove();
                return;
            }

            if (!badge) {
                badge = document.createElement('span');
                badge.className = link.classList.contains('justify-between')
                    ? 'chat-nav-unread-count inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-red-600 px-1.5 text-center text-[11px] font-semibold leading-none text-white'
                    : 'chat-nav-unread-count ml-1.5 inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-red-600 px-1.5 text-center text-[11px] font-semibold leading-none text-white';
                link.appendChild(badge);
            }

            badge.dataset.unreadCount = String(unreadCount);
            badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
            badge.setAttribute('aria-label', `${unreadCount} unread chat ${unreadCount === 1 ? 'message' : 'messages'}`);
        });
    };

    const refreshBadges = async () => {
        if (requestInProgress || document.hidden) return;

        requestInProgress = true;
        try {
            const response = await fetch(navigation.dataset.chatUnreadUrl, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });

            if (response.status === 401 || response.status === 403) {
                clearInterval(pollingInterval);
                return;
            }

            if (response.ok) {
                const summary = await response.json();
                updateBadges(summary.total);
            }
        } catch (error) {
            // A later poll can recover from a temporary network failure.
        } finally {
            requestInProgress = false;
        }
    };

    refreshBadges();
    pollingInterval = setInterval(refreshBadges, 5000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refreshBadges();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startChatUnreadBadgeUpdates, { once: true });
} else {
    startChatUnreadBadgeUpdates();
}
