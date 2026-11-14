document.addEventListener('DOMContentLoaded', () => {
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
