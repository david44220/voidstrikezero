/**
 * VOIDSTRIKE ARENA — Client Platform Interactive Controller
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Navigation Drawer
    const menuToggle = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('.nav__links');
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', () => {
            const isOpen = navLinks.classList.toggle('open');
            menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    // 2. Alert auto-dismiss
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // 3. Clipboard copy buttons
    document.querySelectorAll('[data-copy]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const textToCopy = btn.getAttribute('data-copy');
            try {
                await navigator.clipboard.writeText(textToCopy);
                const originalText = btn.textContent;
                btn.textContent = 'COPIED!';
                btn.classList.add('btn--primary');
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.classList.remove('btn--primary');
                }, 2000);
            } catch (err) {
                console.error('Failed to copy text: ', err);
            }
        });
    });

    // 4. Register PWA Service Worker
    if ('serviceWorker' in navigator && window.location.protocol.startsWith('http')) {
        navigator.serviceWorker.register('/service-worker.js')
            .then(reg => {
                // Service worker active
            })
            .catch(err => {
                console.debug('Service Worker registration skipped: ', err);
            });
    }
});

// Helper for authenticated API calls with CSRF
window.vsFetch = async (url, options = {}) => {
    const metaCsrf = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = metaCsrf ? metaCsrf.getAttribute('content') : '';

    options.headers = options.headers || {};
    if (csrfToken) {
        options.headers['X-CSRF-Token'] = csrfToken;
    }
    options.headers['Accept'] = 'application/json';

    return fetch(url, options);
};
