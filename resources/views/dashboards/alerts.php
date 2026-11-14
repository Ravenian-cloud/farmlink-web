<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-6">
        <h1 class="text-2xl font-bold text-green-900 mb-1">Weather, Pest & Market Advisories</h1>
        <p class="text-xs text-stone-500">Showing active alerts prioritized for <strong><?= htmlspecialchars($county) ?> County</strong>.</p>
    </div>

    <div class="space-y-3">
        <?php foreach ($allAlerts as $alert): ?>
            <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-5">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase px-2.5 py-0.5 rounded-full
                            <?= match($alert['severity']) {
                                'critical' => 'bg-red-100 text-red-900',
                                'warning'  => 'bg-amber-100 text-amber-900',
                                default    => 'bg-blue-100 text-blue-900'
                            } ?>">
                            <?= htmlspecialchars($alert['severity']) ?> • <?= htmlspecialchars($alert['alert_type']) ?>
                        </span>
                        <span class="text-xs font-semibold text-stone-500">📍 <?= htmlspecialchars($alert['county']) ?></span>
                    </div>
                    <span class="text-xs text-stone-400"><?= htmlspecialchars(date('M d, Y', strtotime($alert['created_at']))) ?></span>
                </div>
                <h2 class="text-base font-bold text-stone-900 mb-1"><?= htmlspecialchars($alert['title']) ?></h2>
                <p class="text-sm text-stone-600 leading-relaxed"><?= nl2br(htmlspecialchars($alert['message'])) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
