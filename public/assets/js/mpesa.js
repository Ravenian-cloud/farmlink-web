document.addEventListener('DOMContentLoaded', () => {
    const stkForm = document.getElementById('mpesa-stk-form');
    if (!stkForm) return;

    const statusBox = document.getElementById('mpesa-status-box');
    const payBtn = document.getElementById('mpesa-pay-btn');

    stkForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        payBtn.disabled = true;
        payBtn.textContent = 'Sending M-Pesa STK Push...';

        const formData = new FormData(stkForm);
        const orderId = formData.get('order_id');

        try {
            const res = await fetch(`${window.FARMLINK.baseUrl}/payment/mpesa/stk-push`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.FARMLINK.csrfToken
                },
                body: formData
            });

            const data = await res.json();
            statusBox.classList.remove('hidden');

            if (data.status === 'success') {
                statusBox.className = 'mt-4 p-3.5 rounded-xl text-xs font-medium bg-emerald-100 text-emerald-900';
                statusBox.textContent = data.message;

                if (data.simulated) {
                    setTimeout(() => window.location.reload(), 1200);
                    return;
                }

                // Poll order payment status every 4 seconds for live Daraja callback
                const poll = setInterval(async () => {
                    const check = await fetch(`${window.FARMLINK.baseUrl}/api/payment/status/${orderId}`);
                    const checkData = await check.json();
                    if (checkData.order_status === 'in_escrow') {
                        clearInterval(poll);
                        window.location.reload();
                    }
                }, 4000);
            } else {
                statusBox.className = 'mt-4 p-3.5 rounded-xl text-xs font-medium bg-red-100 text-red-900';
                statusBox.textContent = data.message || 'M-Pesa request failed.';
                payBtn.disabled = false;
                payBtn.textContent = 'Retry M-Pesa Prompt';
            }
        } catch (err) {
            payBtn.disabled = false;
            payBtn.textContent = 'Retry M-Pesa Prompt';
        }
    });
});
