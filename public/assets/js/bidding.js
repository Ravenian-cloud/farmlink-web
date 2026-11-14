document.addEventListener('DOMContentLoaded', () => {
    const threadContainer = document.getElementById('live-bid-thread');
    if (!threadContainer || !window.FARMLINK?.isLoggedIn) return;

    const listingId = threadContainer.dataset.listingId;
    const baseUrl = window.FARMLINK.baseUrl;

    async function fetchBids() {
        if (!navigator.onLine) return;

        try {
            const response = await fetch(`${baseUrl}/api/bids/listing/${listingId}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) return;

            const data = await response.json();
            if (data.status === 'success') {
                renderBids(data.bids);
            }
        } catch (err) {
            console.warn('Bid polling paused:', err);
        }
    }

    function renderBids(bids) {
        if (!bids || bids.length === 0) {
            threadContainer.innerHTML = '<p class="text-xs text-stone-400">No active negotiations on this item yet.</p>';
            return;
        }

        const badgeColors = {
            pending: 'bg-amber-100 text-amber-900',
            countered: 'bg-blue-100 text-blue-900',
            accepted: 'bg-emerald-100 text-emerald-900',
            rejected: 'bg-red-100 text-red-900'
        };

        threadContainer.innerHTML = bids.map(bid => {
            const badge = badgeColors[bid.status] || 'bg-stone-100 text-stone-700';
            const counterHtml = bid.counter_unit_price
                ? `<p class="text-xs text-blue-800 font-semibold mt-1">Seller Counter: KES ${Number(bid.counter_unit_price).toLocaleString()} / ${escapeHtml(bid.unit)}</p>`
                : '';

            return `
                <div class="p-3 rounded-xl border border-stone-200 bg-stone-50 text-xs">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-bold text-stone-800">${escapeHtml(bid.buyer_name)}</span>
                        <span class="px-2 py-0.5 rounded-full font-semibold uppercase ${badge}">${escapeHtml(bid.status)}</span>
                    </div>
                    <p class="text-stone-700">
                        Offer: <strong>KES ${Number(bid.offered_unit_price).toLocaleString()}</strong> × ${Number(bid.requested_qty)} ${escapeHtml(bid.unit)}
                    </p>
                    ${counterHtml}
                    ${bid.buyer_note ? `<p class="text-stone-500 italic mt-1">"${escapeHtml(bid.buyer_note)}"</p>` : ''}
                </div>
            `;
        }).join('');
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    fetchBids();
    setInterval(fetchBids, 12000);
});
