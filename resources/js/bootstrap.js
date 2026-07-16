import 'bootstrap';

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number.parseInt(import.meta.env.VITE_REVERB_PORT ?? '80', 10),
    wssPort: Number.parseInt(import.meta.env.VITE_REVERB_PORT ?? '443', 10),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
});

function clearElement(element) {
    while (element.firstChild) {
        element.removeChild(element.firstChild);
    }
}

function updateNowPerforming(container, payload) {
    if (!container) {
        return;
    }

    clearElement(container);

    const isVisible = payload?.current_phase === 'prelims'
        && payload?.current_prelims_registration_id
        && payload?.registration_name;

    if (!isVisible) {
        return;
    }

    const alert = document.createElement('div');
    alert.className = 'alert alert-info border-0 shadow-sm mb-4';

    const label = document.createElement('strong');
    label.textContent = `${container.dataset.categoryName || 'This category'} now performing:`;

    alert.appendChild(label);
    alert.append(` ${payload.registration_name}`);
    container.appendChild(alert);
}

function updatePrelimsQueue(root, payload) {
    if (!root) {
        return;
    }

    const banner = root.querySelector('[data-prelims-live-banner]');
    updateNowPerforming(banner, payload);

    const currentId = payload?.current_phase === 'prelims' && payload?.current_prelims_registration_id
        ? String(payload.current_prelims_registration_id)
        : null;

    root.querySelectorAll('[data-registration-item]').forEach((item) => {
        const indicator = item.querySelector('[data-current-indicator]');

        if (!indicator) {
            return;
        }

        const isCurrent = currentId !== null && item.dataset.registrationItem === currentId;
        indicator.classList.toggle('d-none', !isCurrent);
    });
}

function updateBracketMatch(root, payload) {
    if (!root || !payload?.match_id) {
        return;
    }

    const card = root.querySelector(`[data-match-card="${payload.match_id}"]`);
    if (!card) {
        return;
    }

    const setText = (selector, value) => {
        const node = card.querySelector(selector);
        if (node) {
            node.textContent = value;
        }
    };

    const toggleWinner = (slot, isWinner) => {
        const row = card.querySelector(`[data-match-row="${slot}"]`);
        if (!row) {
            return;
        }

        row.classList.toggle('bg-success', isWinner);
        row.classList.toggle('bg-opacity-10', isWinner);
        row.classList.toggle('text-success', isWinner);
        row.classList.toggle('fw-bold', isWinner);
    };

    setText('[data-match-score="1"]', payload.score1 ?? '-');
    setText('[data-match-score="2"]', payload.score2 ?? '-');

    if (payload.registration1_name) {
        setText('[data-match-participant-name="1"]', payload.registration1_name);
    }

    if (payload.registration2_name) {
        setText('[data-match-participant-name="2"]', payload.registration2_name);
    }

    card.dataset.matchStatus = payload.status || 'pending';

    toggleWinner(1, payload.winner_id !== null && String(payload.winner_id) === String(payload.registration1_id));
    toggleWinner(2, payload.winner_id !== null && String(payload.winner_id) === String(payload.registration2_id));
}

window.BattlePlatformRealtime = {
    channel(channelName) {
        if (!window.Echo || !channelName) {
            return null;
        }

        return window.Echo.channel(channelName);
    },
    updateNowPerforming,
    updatePrelimsQueue,
    updateBracketMatch,
};
