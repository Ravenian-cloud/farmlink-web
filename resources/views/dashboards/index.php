<?php $userId = (int) $_SESSION['user_id']; ?>

<div class="space-y-6">
    <!-- Top Welcome & KPI Summary -->
    <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-green-700">
                <?= strtoupper(htmlspecialchars($user['role'])) ?> DASHBOARD • <?= htmlspecialchars($user['county']) ?>
            </span>
            <h1 class="text-2xl font-extrabold text-stone-900 mt-0.5"><?= htmlspecialchars($user['full_name']) ?></h1>
            <p class="text-xs text-stone-500 mt-1">
                Trust Rating: <strong class="text-amber-600">★ <?= number_format((float) $user['rating_avg'], 1) ?></strong> (<?= (int) $user['rating_count'] ?> reviews)
                • KYC: <strong><?= strtoupper(htmlspecialchars($user['kyc_status'])) ?></strong>
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <?php if (in_array($user['role'], ['farmer', 'supplier'], true)): ?>
                <a href="<?= base_url('listings/create') ?>" class="bg-green-700 hover:bg-green-800 text-white text-xs font-semibold px-4 py-2.5 rounded-xl">
                    + New Listing
                </a>
            <?php endif; ?>
            <a href="<?= base_url('marketplace') ?>" class="bg-stone-100 hover:bg-stone-200 text-stone-800 text-xs font-semibold px-4 py-2.5 rounded-xl">
                Browse Marketplace
            </a>
        </div>
    </div>

    <!-- County Weather & Pest Alert Banner -->
    <?php if (!empty($alerts)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php foreach (array_slice($alerts, 0, 2) as $alert): ?>
                <div class="p-4 rounded-2xl border text-xs <?= $alert['severity'] === 'critical' ? 'bg-red-50 border-red-300 text-red-950' : 'bg-amber-50 border-amber-300 text-amber-950' ?>">
                    <div class="flex items-center justify-between font-bold mb-1">
                        <span>⚠️ <?= htmlspecialchars($alert['title']) ?></span>
                        <span class="uppercase text-[10px] px-2 py-0.5 rounded-full bg-white/80"><?= htmlspecialchars($alert['alert_type']) ?></span>
                    </div>
                    <p class="leading-relaxed"><?= htmlspecialchars($alert['message']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Active Bids & Price Negotiations -->
    <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-6">
        <h2 class="text-base font-bold text-stone-900 mb-4">Active Price Bids & Negotiations (<?= count($bids) ?>)</h2>
        <?php if (empty($bids)): ?>
            <p class="text-xs text-stone-400">No price negotiations yet.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($bids as $bid): ?>
                    <?php
                    $isSeller = ((int) $bid['seller_id'] === $userId);
                    $isBuyer  = ((int) $bid['buyer_id'] === $userId);
                    ?>
                    <div class="p-4 rounded-xl border border-stone-200 bg-stone-50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="text-xs space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-sm text-stone-900"><?= htmlspecialchars($bid['listing_title']) ?></span>
                                <span class="px-2 py-0.5 rounded-full font-bold uppercase bg-amber-100 text-amber-900"><?= htmlspecialchars($bid['status']) ?></span>
                            </div>
                            <p class="text-stone-600">
                                <?= $isSeller ? 'Buyer: ' . htmlspecialchars($bid['buyer_name']) : 'Seller: ' . htmlspecialchars($bid['seller_name']) ?>
                                • Qty: <strong><?= number_format((float) $bid['requested_qty']) ?> <?= htmlspecialchars($bid['unit']) ?></strong>
                                • Offer: <strong>KES <?= number_format((float) $bid['offered_unit_price'], 2) ?></strong>
                                <?php if ($bid['counter_unit_price'] !== null): ?>
                                    • Counter: <strong class="text-blue-700">KES <?= number_format((float) $bid['counter_unit_price'], 2) ?></strong>
                                <?php endif; ?>
                            </p>
                        </div>

                        <!-- Negotiation Action Controls -->
                        <?php if ($isSeller && $bid['status'] === 'pending'): ?>
                            <div class="flex flex-wrap items-center gap-2">
                                <form action="<?= base_url('bids/' . $bid['id'] . '/respond') ?>" method="POST" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">
                                        Accept Offer
                                    </button>
                                </form>
                                <form action="<?= base_url('bids/' . $bid['id'] . '/respond') ?>" method="POST" class="flex items-center gap-1">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="counter">
                                    <input type="number" name="counter_unit_price" step="0.01" required placeholder="Counter KES"
                                           class="w-24 rounded-lg border border-stone-300 px-2 py-1 text-xs bg-white">
                                    <button type="submit" class="bg-blue-700 text-white text-xs font-semibold px-2.5 py-1.5 rounded-lg">
                                        Counter
                                    </button>
                                </form>
                                <form action="<?= base_url('bids/' . $bid['id'] . '/respond') ?>" method="POST" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="bg-stone-200 hover:bg-red-100 text-stone-700 text-xs font-semibold px-2.5 py-1.5 rounded-lg">
                                        Decline
                                    </button>
                                </form>
                            </div>
                        <?php elseif ($isBuyer && $bid['status'] === 'countered'): ?>
                            <div class="flex items-center gap-2">
                                <form action="<?= base_url('bids/' . $bid['id'] . '/respond') ?>" method="POST" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">
                                        Accept Counter (KES <?= number_format((float) $bid['counter_unit_price']) ?>)
                                    </button>
                                </form>
                                <form action="<?= base_url('bids/' . $bid['id'] . '/respond') ?>" method="POST" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="bg-stone-200 text-stone-700 text-xs font-semibold px-2.5 py-1.5 rounded-lg">
                                        Reject
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Orders & Escrow Tracker -->
    <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-6">
        <h2 class="text-base font-bold text-stone-900 mb-4">Orders & Escrow Ledger (<?= count($orders) ?>)</h2>
        <?php if (empty($orders)): ?>
            <p class="text-xs text-stone-400">No orders placed or received yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-stone-200 text-stone-500 uppercase">
                        <tr>
                            <th class="py-2 pr-3">Ref</th>
                            <th class="py-2 pr-3">Item</th>
                            <th class="py-2 pr-3">Total (KES)</th>
                            <th class="py-2 pr-3">Status</th>
                            <th class="py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php foreach ($orders as $ord): ?>
                            <tr>
                                <td class="py-3 pr-3 font-bold"><?= htmlspecialchars($ord['order_reference']) ?></td>
                                <td class="py-3 pr-3"><?= htmlspecialchars($ord['listing_title']) ?> (<?= number_format((float) $ord['quantity']) ?> <?= htmlspecialchars($ord['unit']) ?>)</td>
                                <td class="py-3 pr-3 font-semibold">KES <?= number_format((float) $ord['total_amount'], 2) ?></td>
                                <td class="py-3 pr-3 uppercase font-bold text-green-800"><?= str_replace('_', ' ', htmlspecialchars($ord['status'])) ?></td>
                                <td class="py-3 text-right">
                                    <a href="<?= base_url('orders/' . $ord['id']) ?>" class="text-green-700 font-semibold hover:underline">Manage →</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- AI Market Price Analytics & Input Recommendations -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-stone-200 shadow-sm p-6">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-base font-bold text-stone-900">AI Price Trend & Demand Forecast</h2>
                    <p class="text-xs text-stone-500">Regional commodity price trajectory in <?= htmlspecialchars($user['county']) ?></p>
                </div>
                <?php if (!empty($trends['forecast_price'])): ?>
                    <span class="text-xs font-bold px-3 py-1 rounded-full bg-emerald-100 text-emerald-900">
                        Next-Week Forecast: KES <?= number_format((float) $trends['forecast_price'], 2) ?> (<?= strtoupper($trends['trend_direction']) ?>)
                    </span>
                <?php endif; ?>
            </div>
            <div class="h-64">
                <canvas id="priceTrendChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-6">
            <h2 class="text-base font-bold text-stone-900 mb-1">Recommended Farm Inputs</h2>
            <p class="text-xs text-stone-500 mb-4">Matched for <?= htmlspecialchars($user['county']) ?> agro-ecological zone</p>
            <div class="space-y-3">
                <?php foreach ($inputs as $inp): ?>
                    <a href="<?= base_url('marketplace/listing/' . $inp['id']) ?>" class="block p-3 rounded-xl border border-stone-200 hover:border-green-600 transition">
                        <p class="text-xs font-bold text-stone-900"><?= htmlspecialchars($inp['title']) ?></p>
                        <p class="text-xs text-green-800 font-semibold mt-0.5">KES <?= number_format((float) $inp['unit_price'], 2) ?> / <?= htmlspecialchars($inp['unit']) ?></p>
                        <p class="text-[11px] text-stone-400 mt-0.5"><?= htmlspecialchars($inp['supplier_name']) ?> • <?= htmlspecialchars($inp['county']) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    window.FARMLINK_PRICE_SERIES = <?= json_encode($trends['series'], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= base_url('assets/js/charts.js') ?>"></script>
