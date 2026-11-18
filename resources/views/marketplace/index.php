<div class="mb-6">
    <!-- Hero Banner & Quick Tabs -->
    <div class="relative overflow-hidden bg-gradient-to-r from-green-900 to-green-700 text-white rounded-2xl p-6 sm:p-8 shadow-sm mb-6">
        <img src="<?= base_url('assets/img/hero-farm.svg') ?>" alt="Farmer working in a green field"
             class="absolute inset-y-0 right-0 h-full w-2/5 object-cover opacity-90 hidden sm:block"
             loading="eager">
        <div class="absolute inset-0 bg-gradient-to-r from-green-900 via-green-900/95 to-transparent hidden sm:block"></div>
        <div class="relative max-w-2xl">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight mb-2">
                Direct Farm Produce & Certified Agribusiness Inputs
            </h1>
            <p class="text-green-100 text-sm sm:text-base mb-5">
                Trade directly with verified farmers and suppliers. Negotiate fair prices with M-Pesa Escrow protection.
            </p>
            <div class="flex flex-wrap gap-2">
                <a href="<?= base_url('marketplace') ?>"
                   class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold <?= ($filters['type'] ?? '') === '' ? 'bg-amber-400 text-green-950' : 'bg-green-800/80 text-white hover:bg-green-800' ?>">
                    All Listings
                </a>
                <a href="<?= base_url('marketplace?type=produce') ?>"
                   class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold <?= ($filters['type'] ?? '') === 'produce' ? 'bg-amber-400 text-green-950' : 'bg-green-800/80 text-white hover:bg-green-800' ?>">
                    🌾 Farm Produce
                </a>
                <a href="<?= base_url('marketplace?type=input') ?>"
                   class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold <?= ($filters['type'] ?? '') === 'input' ? 'bg-amber-400 text-green-950' : 'bg-green-800/80 text-white hover:bg-green-800' ?>">
                    🌱 Seeds, Fertilizers & Inputs
                </a>
            </div>
        </div>
    </div>

    <!-- Search, County & GPS Nearby Filter Bar -->
    <form action="<?= base_url('marketplace') ?>" method="GET" id="filter-form"
          class="bg-white p-4 rounded-2xl border border-stone-200 shadow-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <input type="hidden" name="type" value="<?= htmlspecialchars($filters['type'] ?? '') ?>">
        <input type="hidden" name="lat" id="filter-lat" value="<?= htmlspecialchars($filters['lat'] ?? '') ?>">
        <input type="hidden" name="lng" id="filter-lng" value="<?= htmlspecialchars($filters['lng'] ?? '') ?>">

        <input type="text" name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>"
               placeholder="Search maize, onions, DAP, seeds..."
               class="rounded-xl border border-stone-300 px-3.5 py-2 text-sm focus:ring-2 focus:ring-green-600 focus:outline-none">

        <select name="category_id" class="rounded-xl border border-stone-300 px-3.5 py-2 text-sm bg-white focus:ring-2 focus:ring-green-600 focus:outline-none">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>" <?= ((string) ($filters['category_id'] ?? '') === (string) $cat['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="county" class="rounded-xl border border-stone-300 px-3.5 py-2 text-sm bg-white focus:ring-2 focus:ring-green-600 focus:outline-none">
            <option value="">All Counties</option>
            <?php foreach (['Nakuru', 'Uasin Gishu', 'Kiambu', 'Nairobi', 'Meru', 'Machakos', 'Kisumu', 'Nyeri', 'Trans Nzoia', 'Narok'] as $county): ?>
                <option value="<?= $county ?>" <?= (($filters['county'] ?? '') === $county) ? 'selected' : '' ?>>
                    <?= $county ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="flex gap-2">
            <select name="radius_km" id="radius-select" class="w-full rounded-xl border border-stone-300 px-3 py-2 text-sm bg-white">
                <option value="">Any Distance</option>
                <option value="25" <?= (($filters['radius_km'] ?? '') === '25') ? 'selected' : '' ?>>Within 25 km</option>
                <option value="50" <?= (($filters['radius_km'] ?? '') === '50') ? 'selected' : '' ?>>Within 50 km</option>
                <option value="100" <?= (($filters['radius_km'] ?? '') === '100') ? 'selected' : '' ?>>Within 100 km</option>
            </select>
            <button type="button" id="near-me-btn" title="Use My GPS Location"
                    class="px-3 py-2 rounded-xl border border-stone-300 bg-stone-50 hover:bg-stone-100 text-sm font-semibold">
                📍
            </button>
        </div>

        <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-semibold py-2 px-4 rounded-xl text-sm transition">
            Filter Results
        </button>
    </form>
</div>

<!-- Listings Grid -->
<?php if (empty($listings)): ?>
    <div class="bg-white rounded-2xl border border-stone-200 p-12 text-center">
        <p class="text-stone-600 font-semibold mb-1">No active listings match your filters.</p>
        <p class="text-xs text-stone-400">Try clearing your county or distance filter to view all available produce and inputs.</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($listings as $item): ?>
            <article class="bg-white rounded-2xl border border-stone-200 shadow-sm overflow-hidden flex flex-col justify-between hover:shadow-md transition">
                <div>
                    <!-- Listing Image or Category Placeholder -->
                    <div class="h-44 bg-stone-100 relative overflow-hidden">
                        <?php if (!empty($item['image_path'])): ?>
                            <img src="<?= base_url($item['image_path']) ?>" alt="<?= htmlspecialchars($item['title']) ?>"
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <?php
                                $categorySlug = strtolower(trim((string) $item['category_name']));
                                $fallbackImage = 'assets/img/seed.jpg';
                                if (str_contains($categorySlug, 'cereal') || str_contains(strtolower((string) $item['title']), 'maize')) {
                                    $fallbackImage = 'assets/img/maize.jpg';
                                } elseif (str_contains($categorySlug, 'vegetable') || str_contains(strtolower((string) $item['title']), 'onion')) {
                                    $fallbackImage = 'assets/img/onions.jpg';
                                } elseif (str_contains($categorySlug, 'fertiliz')) {
                                    $fallbackImage = 'assets/img/fertilizer.jpg';
                                }
                            ?>
                            <img src="<?= base_url($fallbackImage) ?>"
                                 alt="<?= htmlspecialchars($item['title']) ?>"
                                 class="w-full h-full object-cover"
                                 loading="lazy">
                        <?php endif; ?>

                        <span class="absolute top-3 left-3 text-xs font-bold px-2.5 py-1 rounded-full <?= $item['listing_type'] === 'produce' ? 'bg-emerald-700 text-white' : 'bg-amber-400 text-green-950' ?>">
                            <?= $item['listing_type'] === 'produce' ? 'Farm Produce' : 'Agri-Input' ?>
                        </span>

                        <?php if (isset($item['distance_km'])): ?>
                            <span class="absolute top-3 right-3 text-xs font-semibold px-2.5 py-1 rounded-full bg-stone-900/80 text-white">
                                <?= number_format((float) $item['distance_km'], 1) ?> km away
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="p-4">
                        <div class="flex items-center justify-between text-xs text-stone-500 mb-1">
                            <span><?= htmlspecialchars($item['category_name']) ?></span>
                            <span>📍 <?= htmlspecialchars($item['county']) ?></span>
                        </div>

                        <h2 class="font-bold text-base text-stone-900 line-clamp-1 mb-1">
                            <a href="<?= base_url('marketplace/listing/' . $item['id']) ?>" class="hover:text-green-700">
                                <?= htmlspecialchars($item['title']) ?>
                            </a>
                        </h2>

                        <p class="text-xs text-stone-600 line-clamp-2 mb-3">
                            <?= htmlspecialchars($item['description']) ?>
                        </p>

                        <div class="flex items-baseline justify-between border-t border-stone-100 pt-3">
                            <div>
                                <span class="text-lg font-extrabold text-green-800">
                                    KES <?= number_format((float) $item['unit_price'], 2) ?>
                                </span>
                                <span class="text-xs text-stone-500">/ <?= htmlspecialchars($item['unit']) ?></span>
                            </div>
                            <span class="text-xs font-medium text-stone-600">
                                Stock: <?= number_format((float) $item['quantity_available']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3 bg-stone-50 border-t border-stone-100 flex items-center justify-between">
                    <div class="text-xs">
                        <p class="font-semibold text-stone-800 flex items-center gap-1">
                            <?= htmlspecialchars($item['seller_name']) ?>
                            <?php if ($item['seller_kyc'] === 'verified'): ?>
                                <span class="text-emerald-600" title="KYC Verified">✔</span>
                            <?php endif; ?>
                        </p>
                        <p class="text-stone-400">★ <?= number_format((float) $item['seller_rating'], 1) ?></p>
                    </div>

                    <a href="<?= base_url('marketplace/listing/' . $item['id']) ?>"
                       class="bg-green-700 hover:bg-green-800 text-white text-xs font-semibold px-3.5 py-2 rounded-xl transition">
                        <?= ($_SESSION['user_role'] ?? '') === 'admin' ? 'View Listing' : ($item['is_negotiable'] ? 'Bid / Buy' : 'Buy Now') ?>
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
document.getElementById('near-me-btn')?.addEventListener('click', () => {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
        return;
    }
    navigator.geolocation.getCurrentPosition((pos) => {
        document.getElementById('filter-lat').value = pos.coords.latitude.toFixed(6);
        document.getElementById('filter-lng').value = pos.coords.longitude.toFixed(6);
        const radiusSelect = document.getElementById('radius-select');
        if (!radiusSelect.value) radiusSelect.value = '50';
        document.getElementById('filter-form').submit();
    }, () => {
        alert('Unable to retrieve your GPS location.');
    });
});
</script>
