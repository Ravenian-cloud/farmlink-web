<?php
$isBuyer  = ((int) $_SESSION['user_id'] === (int) $order['buyer_id']);
$isSeller = ((int) $_SESSION['user_id'] === (int) $order['seller_id']);
?>

<div class="max-w-3xl mx-auto space-y-6">
    <!-- Order Header & Escrow Status -->
    <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-6">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-stone-100 pb-4 mb-4">
            <div>
                <p class="text-xs text-stone-400">Order Reference</p>
                <h1 class="text-xl font-extrabold text-stone-900"><?= htmlspecialchars($order['order_reference']) ?></h1>
            </div>
            <span class="px-3.5 py-1.5 rounded-full text-xs font-bold uppercase
                <?= match($order['status']) {
                    'awaiting_payment' => 'bg-amber-100 text-amber-900',
                    'in_escrow'        => 'bg-blue-100 text-blue-900',
                    'in_transit'       => 'bg-indigo-100 text-indigo-900',
                    'delivered'        => 'bg-emerald-100 text-emerald-900',
                    default            => 'bg-stone-100 text-stone-700'
                } ?>">
                <?= str_replace('_', ' ', htmlspecialchars($order['status'])) ?>
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm mb-5">
            <div>
                <p class="text-xs text-stone-400">Item Purchased</p>
                <p class="font-bold text-stone-800"><?= htmlspecialchars($order['listing_title']) ?></p>
                <p class="text-xs text-stone-500">
                    <?= number_format((float) $order['quantity']) ?> <?= htmlspecialchars($order['unit']) ?> × KES <?= number_format((float) $order['agreed_unit_price'], 2) ?>
                </p>
            </div>
            <div class="sm:text-right">
                <p class="text-xs text-stone-400">Total Escrow Amount</p>
                <p class="text-2xl font-extrabold text-green-800">KES <?= number_format((float) $order['total_amount'], 2) ?></p>
                <?php if (!empty($order['mpesa_receipt_number'])): ?>
                    <p class="text-xs text-emerald-700 font-semibold">M-Pesa Receipt: <?= htmlspecialchars($order['mpesa_receipt_number']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Delivery & Transport Info -->
        <div class="bg-stone-50 p-4 rounded-xl border border-stone-200 text-xs space-y-1">
            <p><strong class="text-stone-700">Buyer:</strong> <?= htmlspecialchars($order['buyer_name']) ?> (<?= htmlspecialchars($order['buyer_phone']) ?>)</p>
            <p><strong class="text-stone-700">Seller:</strong> <?= htmlspecialchars($order['seller_name']) ?> (<?= htmlspecialchars($order['seller_phone']) ?>)</p>
            <p><strong class="text-stone-700">Destination:</strong> <?= htmlspecialchars($order['delivery_county']) ?> — <?= htmlspecialchars($order['delivery_address']) ?></p>
            <?php if (!empty($order['logistics_provider'])): ?>
                <p class="text-indigo-800 font-semibold pt-1">
                    🚚 Transport: <?= htmlspecialchars($order['logistics_provider']) ?> (<?= htmlspecialchars($order['logistics_contact']) ?>)
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Step 1: Buyer M-Pesa STK Push Checkout -->
    <?php if ($isBuyer && $order['status'] === 'awaiting_payment'): ?>
        <div class="bg-white rounded-2xl border-2 border-green-600 shadow-sm p-6">
            <h2 class="text-lg font-bold text-green-900 mb-1">Pay Securely via M-Pesa Escrow</h2>
            <p class="text-xs text-stone-600 mb-4">
                Your funds are held safely by FarmLink Escrow and only released to the seller once you confirm delivery of your goods.
            </p>

            <form id="mpesa-stk-form" class="space-y-4">
                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                <div>
                    <label class="block text-xs font-semibold text-stone-700 mb-1">M-Pesa Phone Number</label>
                    <input type="tel" name="mpesa_phone" value="<?= htmlspecialchars($order['buyer_phone']) ?>" required
                           class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm">
                </div>
                <button type="submit" id="mpesa-pay-btn"
                        class="w-full bg-green-700 hover:bg-green-800 text-white font-bold py-3 rounded-xl text-sm transition">
                    Send M-Pesa Prompt (KES <?= number_format((float) $order['total_amount'], 2) ?>)
                </button>
            </form>
            <div id="mpesa-status-box" class="hidden mt-4 p-3.5 rounded-xl text-xs font-medium"></div>
        </div>
    <?php endif; ?>

    <!-- Step 2: Seller Logistics Dispatch Form -->
    <?php if ($isSeller && $order['status'] === 'in_escrow'): ?>
        <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-6">
            <h2 class="text-base font-bold text-stone-900 mb-1">Dispatch Goods & Assign Transport</h2>
            <p class="text-xs text-stone-500 mb-4">
                Buyer payment of KES <?= number_format((float) $order['gross_amount'], 2) ?> is locked in escrow (Net Payout: KES <?= number_format((float) $order['net_seller_payout'], 2) ?>).
            </p>
            <form action="<?= base_url('orders/' . $order['id']) ?>" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <?= csrf_field() ?>
                <input type="text" name="logistics_provider" required placeholder="Transporter / Vehicle Reg (e.g. KDA 123X)"
                       class="rounded-xl border border-stone-300 px-3 py-2 text-sm">
                <input type="text" name="logistics_contact" required placeholder="Driver Phone (07...)"
                       class="rounded-xl border border-stone-300 px-3 py-2 text-sm">
                <button type="submit" class="bg-indigo-700 hover:bg-indigo-800 text-white font-semibold py-2 px-4 rounded-xl text-sm">
                    Mark In Transit
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Step 3: Buyer Delivery Confirmation (Releases Escrow) -->
    <?php if ($isBuyer && in_array($order['status'], ['in_escrow', 'in_transit'], true)): ?>
        <div class="bg-emerald-50 border border-emerald-300 rounded-2xl p-6">
            <h2 class="text-base font-bold text-emerald-950 mb-1">Confirm Delivery & Release Escrow Funds</h2>
            <p class="text-xs text-emerald-800 mb-4">
                Only click below after inspecting and receiving your produce or inputs in satisfactory condition.
            </p>
            <form action="<?= base_url('orders/' . $order['id'] . '/confirm-delivery') ?>" method="POST">
                <?= csrf_field() ?>
                <button type="submit" class="bg-emerald-700 hover:bg-emerald-800 text-white font-bold px-5 py-2.5 rounded-xl text-sm">
                    ✔ Confirm Goods Received & Release Payment
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Step 4: Post-Delivery Seller Rating -->
    <?php if ($isBuyer && $order['status'] === 'delivered' && !$hasReviewed): ?>
        <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-6">
            <h2 class="text-base font-bold text-stone-900 mb-2">Rate Your Experience with <?= htmlspecialchars($order['seller_name']) ?></h2>
            <form action="<?= base_url('orders/' . $order['id'] . '/review') ?>" method="POST" class="space-y-3">
                <?= csrf_field() ?>
                <select name="rating" class="rounded-xl border border-stone-300 px-3 py-2 text-sm bg-white">
                    <option value="5">★★★★★ (5 - Excellent Quality & Reliability)</option>
                    <option value="4">★★★★☆ (4 - Good)</option>
                    <option value="3">★★★☆☆ (3 - Average)</option>
                    <option value="2">★★☆☆☆ (2 - Below Expectations)</option>
                    <option value="1">★☆☆☆☆ (1 - Poor)</option>
                </select>
                <textarea name="comment" rows="2" placeholder="Share feedback on produce quality, packaging, and timeliness..."
                          class="w-full rounded-xl border border-stone-300 px-3 py-2 text-sm"></textarea>
                <button type="submit" class="bg-amber-400 hover:bg-amber-300 text-green-950 font-semibold px-4 py-2 rounded-xl text-sm">
                    Submit Review
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script src="<?= base_url('assets/js/mpesa.js') ?>"></script>
