document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('priceTrendChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const series = window.FARMLINK_PRICE_SERIES || [];
    const labels = series.map(row => row.recorded_date);
    const prices = series.map(row => Number(row.avg_unit_price));
    const cropLabel = series[0]?.crop_name || 'Commodity Price (KES)';

    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: cropLabel,
                data: prices,
                borderColor: '#15803d',
                backgroundColor: 'rgba(21, 128, 61, 0.12)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointBackgroundColor: '#15803d'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true, labels: { boxWidth: 12, font: { size: 11 } } }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: {
                        callback: (val) => 'KES ' + Number(val).toLocaleString()
                    }
                }
            }
        }
    });
});
