document.addEventListener('DOMContentLoaded', () => {
    const listingForm = document.getElementById('create-listing-form');
    const communityForm = document.getElementById('community-post-form');

    function interceptOfflineForm(formElement, actionType) {
        if (!formElement) return;

        formElement.addEventListener('submit', async (e) => {
            if (navigator.onLine) return; // Normal server POST when online

            e.preventDefault();
            const formData = new FormData(formElement);
            const payload = {};

            formData.forEach((value, key) => {
                if (key !== '_token' && !(value instanceof File)) {
                    payload[key] = value;
                }
            });

            if (formElement.querySelector('[name="is_negotiable"]')) {
                payload.is_negotiable = formElement.querySelector('[name="is_negotiable"]').checked ? 1 : 0;
            }

            await window.FarmLinkOfflineDB.enqueue(actionType, payload);
            formElement.reset();
            alert('Saved offline! Your entry will sync automatically to FarmLink once your internet connection returns.');
        });
    }

    interceptOfflineForm(listingForm, 'create_listing');
    interceptOfflineForm(communityForm, 'create_forum_post');

    async function flushOfflineQueue() {
        if (!navigator.onLine || !window.FARMLINK?.isLoggedIn || !window.FarmLinkOfflineDB) return;

        const items = await window.FarmLinkOfflineDB.getAll();
        if (items.length === 0) return;

        try {
            const response = await fetch(`${window.FARMLINK.baseUrl}/api/sync/push`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.FARMLINK.csrfToken
                },
                body: JSON.stringify({ items })
            });

            if (!response.ok) return;
            const result = await response.json();

            if (result.status === 'success' && Array.isArray(result.synced_uuids)) {
                for (const uuid of result.synced_uuids) {
                    await window.FarmLinkOfflineDB.remove(uuid);
                }
                if (result.synced_uuids.length > 0) {
                    console.info(`FarmLink Sync: ${result.synced_uuids.length} offline record(s) synchronized.`);
                }
            }
        } catch (err) {
            console.warn('Background sync deferred:', err);
        }
    }

    window.addEventListener('online', flushOfflineQueue);
    flushOfflineQueue();
});
