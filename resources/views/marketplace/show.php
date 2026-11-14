<div class="max-w-4xl mx-auto bg-white rounded-2xl border border-stone-200 shadow-sm overflow-hidden">
    <div class="grid grid-cols-1 md:grid-cols-2">
        <!-- Image Panel -->
        <div class="bg-stone-100 min-h-[280px] flex items-center justify-center">
            <?php if (!empty($listing['image_path'])): ?>
                <img src="<?= base_url($listing['image_path']) ?>" alt="<?= htmlspecialchars($listing['title']) ?>"
                     class="w-full h-full object-cover">
            <?php else: ?>
                <div class="text-center p-8 text-stone-400">
                    <span class="text-6xl block mb-2"><?= $listing['listing_type'] === 'produce' ? '🌾' : '🌱' ?></span>
                    <span class="text-sm font-medium"><?= htmlspecialchars($listing['category_name']) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Details & Action Panel -->
        <div class="p-6 sm:p-8 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold mb-2">
                    <span class="px-2.5 py-0.5 rounded-full <?= $listing['listing_type'] === 'produce' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900' ?>">
                        <?= strtoupper(htmlspecialchars($listing['listing_type'])) ?>
                    </span>
                    <span class="text-stone-500">• <?= htmlspecialchars($listing['category_name']) ?></span>
                </div>

                <h1 class="text-2xl font-bold text-stone-900 mb-2"><?= htmlspecialchars($listing['title']) ?></h1>

                <div class="flex items-baseline gap-2 mb-4">
                    <span class="text-2xl font-extrabold text-green-800">
                        KES <?= number_format((float) $listing['unit_price'], 2) ?>
                    </span>
                    <span class="text-sm text-stone-500">per <?= htmlspecialchars($listing['unit']) ?></span>
                </div>

                <p class="text-sm text-stone-600 leading-relaxed mb-5">
                    <?= nl2br(htmlspecialchars($listing['description'])) ?>
                </p>

                <div class="grid grid-cols-2 gap-3 bg-stone-50 p-3.5 rounded-xl text-xs mb-5 border border-stone-100">
                    <div>
                        <span class="text-stone-400 block">Available Stock</span>
                        <span class="font-bold text-stone-800"><?= number_format((float) $listing['quantity_available']) ?> <?= htmlspecialchars($listing['unit']) ?>s</span>
                    </div>
                    <div>
                        <span class="text-stone-400 block">Minimum Order</span>
                        <span class="font-bold text-stone-800"><?= number_format((float) $listing['min_order_qty']) ?> <?= htmlspecialchars($listing['unit']) ?>s</span>
                    </div>
                    <div>
                        <span class="text-stone-400 block">Location</span>
                        <span class="font-bold text-stone-800"><?= htmlspecialchars($listing['county']) ?></span>
                    </div>
                    <div>
                        <span class="text-stone-400 block"><?= $listing['listing_type'] === 'produce' ? 'Harvest Date' : 'Expiry Date' ?></span>
                        <span class="font-bold text-stone-800"><?= htmlspecialchars($listing['harvest_or_expiry_date'] ?? 'N/A') ?></span>
                    </div>
                </div>

                <!-- Seller Profile Card -->
                <div class="flex items-center justify-between border-t border-stone-100 pt-4 mb-6">
                    <div>
                        <p class="text-xs text-stone-400">Listed by</p>
                        <p class="text-sm font-bold text-stone-900 flex items-center gap-1.5">
                            <?= htmlspecialchars($listing['seller_name']) ?>
                            <?php if ($listing['seller_kyc'] === 'verified'): ?>
                                <span class="text-xs bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full">KYC Verified</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="text-right text-xs">
                        <span class="font-bold text-amber-600">★ <?= number_format((float) $listing['seller_rating'], 1) ?></span>
                        <span class="text-stone-400 block">(<?= (int) $listing['seller_reviews'] ?> reviews)</span>
                    </div>
                </div>
            </div>

            <!-- Action Forms (Direct Order vs. Price Bid) -->
            <?php if (empty($_SESSION['user_id'])): ?>
                <a href="<?= base_url('login') ?>" class="block text-center bg-green-700 hover:bg-green-800 text-white font-semibold py-3 rounded-xl text-sm">
                    Sign In to Buy or Submit a Bid
                </a>
            <?php elseif ((int) $_SESSION['user_id'] === (int) $listing['seller_id']): ?>
                <div class="bg-stone-100 text-stone-600 text-xs font-medium p-3 rounded-xl text-center">
                    This is your active listing. Manage incoming bids from your Dashboard.
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <!-- Direct Buy Form -->
                    <form action="<?= base_url('orders/create') ?>" method="POST" class="bg-emerald-50/70 border border-emerald-200 p-4 rounded-xl space-y-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="listing_id" value="<?= (int) $listing['id'] ?>">
                        <p class="text-xs font-bold text-emerald-900 uppercase tracking-wide">Direct Purchase (Escrow Protected)</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-xs text-stone-600 block mb-1">Quantity (<?= htmlspecialchars($listing['unit']) ?>)</label>
                                <input type="number" name="quantity" step="0.01"
                                       min="<?= (float) $listing['min_order_qty'] ?>"
                                       max="<?= (float) $listing['quantity_available'] ?>"
                                       value="<?= (float) $listing['min_order_qty'] ?>" required
                                       class="w-full rounded-lg border border-stone-300 px-3 py-1.5 text-sm bg-white">
                            </div>
                            <div>
                                <label class="text-xs text-stone-600 block mb-1">Delivery County</label>
                                <input type="text" name="delivery_county" value="<?= htmlspecialchars($_SESSION['user_county'] ?? '') ?>" required
                                       class="w-full rounded-lg border border-stone-300 px-3 py-1.5 text-sm bg-white">
                            </div>
                        </div>
                        <input type="text" name="delivery_address" placeholder="Specific market, town, or drop-off point" required
                               class="w-full rounded-lg border border-stone-300 px-3 py-1.5 text-sm bg-white">
                        <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-2.5 rounded-xl text-sm transition">
                            Proceed to M-Pesa Escrow Checkout
                        </button>
                    </form>

                    <!-- Price Negotiation / Bidding Form -->
                    <?php if ((int) $listing['is_negotiable'] === 1): ?>
                        <form action="<?= base_url('bids/submit') ?>" method="POST" class="border border-stone-200 p-4 rounded-xl space-y-3">
                            <?= csrf_field() ?>
                            <input type="hidden" name="listing_id" value="<?= (int) $listing['id'] ?>">
                            <p class="text-xs font-bold text-stone-700 uppercase tracking-wide">Negotiate / Make an Offer</p>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs text-stone-600 block mb-1">Your Offer Price (KES)</label>
                                    <input type="number" name="offered_unit_price" step="0.01" required
                                           placeholder="<?= (float) $listing['unit_price'] ?>"
                                           class="w-full rounded-lg border border-stone-300 px-3 py-1.5 text-sm">
                                </div>
                                <div>
                                    <label class="text-xs text-stone-600 block mb-1">Quantity</label>
                                    <input type="number" name="requested_qty" step="0.01"
                                           min="<?= (float) $listing['min_order_qty'] ?>"
                                           max="<?= (float) $listing['quantity_available'] ?>"
                                           value="<?= (float) $listing['min_order_qty'] ?>" required
                                           class="w-full rounded-lg border border-stone-300 px-3 py-1.5 text-sm">
                                </div>
                            </div>
                            <input type="text" name="buyer_note" placeholder="Optional note (e.g., bulk pickup at farm gate)"
                                   class="w-full rounded-lg border border-stone-300 px-3 py-1.5 text-sm">
                            <button type="submit" class="w-full bg-amber-400 hover:bg-amber-300 text-green-950 font-semibold py-2 rounded-xl text-sm transition">
                                Submit Price Offer
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($_SESSION['user_id'])): ?>
<div class="max-w-4xl mx-auto mt-6 bg-white rounded-2xl border border-stone-200 shadow-sm p-6">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-sm font-bold text-stone-800 uppercase tracking-wide">Your Negotiation Thread</h2>
        <span class="text-xs text-emerald-700 font-medium">● Live Updates</span>
    </div>
    <div id="live-bid-thread" data-listing-id="<?= (int) $listing['id'] ?>" class="space-y-2.5">
        <p class="text-xs text-stone-400">Loading offers...</p>
    </div>
</div>
<script src="<?= base_url('assets/js/bidding.js') ?>"></script>
<?php endif; ?>
