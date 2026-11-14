document.addEventListener('DOMContentLoaded', () => {
    const gpsBtn = document.getElementById('capture-gps-btn');
    const statusEl = document.getElementById('gps-status');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');

    if (!gpsBtn) return;

    gpsBtn.addEventListener('click', () => {
        if (!navigator.geolocation) {
            statusEl.textContent = 'Geolocation is not supported by your browser.';
            return;
        }

        statusEl.textContent = 'Acquiring coordinates...';
        navigator.geolocation.getCurrentPosition(
            (position) => {
                latInput.value = position.coords.latitude.toFixed(6);
                lngInput.value = position.coords.longitude.toFixed(6);
                statusEl.textContent = `Pinned: ${latInput.value}, ${lngInput.value}`;
                statusEl.classList.replace('text-stone-500', 'text-emerald-700');
            },
            () => {
                statusEl.textContent = 'Location permission denied. County selection will be used.';
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });
});
