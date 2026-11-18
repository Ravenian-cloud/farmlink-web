document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    const themeLabel = themeToggle?.querySelector('[data-theme-label]');
    const root = document.documentElement;
    const themePreference = window.matchMedia('(prefers-color-scheme: dark)');
    let hasUserThemePreference = root.dataset.themePreference === 'user';

    function setTheme(isNight, persist = false) {
        root.dataset.theme = isNight ? 'night' : 'day';
        document.querySelector('meta[name="theme-color"]')?.setAttribute('content', isNight ? '#0d1720' : '#1e6449');
        if (persist) {
            hasUserThemePreference = true;
            root.dataset.themePreference = 'user';
            try {
                localStorage.setItem('farmlink-theme', isNight ? 'night' : 'day');
            } catch (error) {
                // The selected theme still applies for this page when storage is unavailable.
            }
        }
        if (themeToggle) {
            themeToggle.setAttribute('aria-pressed', String(isNight));
            themeToggle.setAttribute('aria-label', `Switch to ${isNight ? 'light' : 'dark'} mode`);
        }
        if (themeLabel) themeLabel.textContent = isNight ? 'Light' : 'Dark';
    }

    if (themeToggle) {
        const initialTheme = root.dataset.theme === 'night';
        themeToggle.setAttribute('aria-pressed', String(initialTheme));
        themeToggle.setAttribute('aria-label', `Switch to ${initialTheme ? 'light' : 'dark'} mode`);
        if (themeLabel) themeLabel.textContent = initialTheme ? 'Light' : 'Dark';
        themeToggle.addEventListener('click', () => {
            setTheme(root.dataset.theme !== 'night', true);
        });
    }

    const updateSystemTheme = (event) => {
        if (!hasUserThemePreference) setTheme(event.matches);
    };
    if (typeof themePreference.addEventListener === 'function') {
        themePreference.addEventListener('change', updateSystemTheme);
    } else {
        themePreference.addListener(updateSystemTheme);
    }

    const offlineBanner = document.getElementById('offline-banner');

    function updateNetworkStatus() {
        if (!offlineBanner) return;
        if (navigator.onLine) {
            offlineBanner.classList.add('hidden');
        } else {
            offlineBanner.classList.remove('hidden');
        }
    }

    window.addEventListener('online', updateNetworkStatus);
    window.addEventListener('offline', updateNetworkStatus);
    updateNetworkStatus();

    // Register FarmLink PWA Service Worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register(`${window.FARMLINK.baseUrl}/sw.js`)
            .catch((err) => console.warn('Service Worker registration failed:', err));
    }
});
