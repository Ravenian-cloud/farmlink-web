<div class="max-w-2xl mx-auto bg-white rounded-2xl border border-stone-200 shadow-sm p-6 sm:p-8">
    <h1 class="text-2xl font-bold text-green-900 mb-1">
        <?= $listingType === 'produce' ? 'List Farm Produce for Sale' : 'List Agricultural Input' ?>
    </h1>
    <p class="text-sm text-stone-500 mb-6">
        If you lose internet connectivity while filling this form, FarmLink will save your listing offline and sync it automatically.
    </p>

    <form action="<?= base_url('listings/store') ?>" method="POST" enctype="multipart/form-data" id="create-listing-form" class="space-y-4">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">Item / Crop Title</label>
                <input type="text" name="title" required placeholder="<?= $listingType === 'produce' ? 'e.g. Grade 1 White Maize' : 'e.g. DAP Fertilizer 50kg' ?>"
                       class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">Category</label>
                <select name="category_id" required class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm bg-white">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-stone-700 mb-1">Description & Quality Specs</label>
            <textarea name="description" rows="3" required placeholder="Include grade, moisture content, packaging, or active ingredients..."
                      class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm"></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">Unit of Measure</label>
                <input type="text" name="unit" required placeholder="kg, 90kg bag, crate, litre"
                       class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">Price per Unit (KES)</label>
                <input type="number" name="unit_price" step="0.01" min="1" required placeholder="4200"
                       class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">Available Quantity</label>
                <input type="number" name="quantity_available" step="0.01" min="1" required placeholder="50"
                       class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">Minimum Order Qty</label>
                <input type="number" name="min_order_qty" step="0.01" min="1" value="1" required
                       class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">County</label>
                <input type="text" name="county" value="<?= htmlspecialchars($user['county'] ?? '') ?>" required
                       class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">
                    <?= $listingType === 'produce' ? 'Harvest Date' : 'Expiry Date' ?>
                </label>
                <input type="date" name="harvest_or_expiry_date"
                       class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm">
            </div>
        </div>

        <div class="flex items-center justify-between bg-stone-50 p-3.5 rounded-xl border border-stone-200">
            <label class="flex items-center gap-2 text-sm font-medium text-stone-700 cursor-pointer">
                <input type="checkbox" name="is_negotiable" value="1" checked class="rounded text-green-700">
                Allow buyers to negotiate / submit price bids
            </label>

            <button type="button" id="capture-gps-btn" class="text-xs bg-white border border-stone-300 px-3 py-1.5 rounded-lg font-semibold">
                📍 Update GPS
            </button>
            <input type="hidden" name="latitude" id="latitude" value="<?= htmlspecialchars((string) ($user['latitude'] ?? '')) ?>">
            <input type="hidden" name="longitude" id="longitude" value="<?= htmlspecialchars((string) ($user['longitude'] ?? '')) ?>">
        </div>
        <p id="gps-status" class="text-xs text-stone-400"></p>

        <div>
            <label class="block text-sm font-medium text-stone-700 mb-1">Product Photo (JPG, PNG, WebP)</label>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                   class="block w-full text-sm text-stone-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-green-700 file:text-white">
        </div>

        <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-3 rounded-xl transition">
            Publish Listing to Marketplace
        </button>
    </form>
</div>
<script src="<?= base_url('assets/js/geolocation.js') ?>"></script>
