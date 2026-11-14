<div class="max-w-lg mx-auto bg-white rounded-2xl shadow-sm border border-stone-200 p-6 sm:p-8 mt-4">
    <h1 class="text-2xl font-bold text-green-900 mb-1"><?= htmlspecialchars(__('register')) ?></h1>
    <p class="text-sm text-stone-500 mb-6">Join Kenya's direct agricultural trade & input ecosystem.</p>

    <form action="<?= base_url('register') ?>" method="POST" class="space-y-4">
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium text-stone-700 mb-1">I am registering as a:</label>
            <div class="grid grid-cols-3 gap-2">
                <label class="cursor-pointer">
                    <input type="radio" name="role" value="farmer" checked class="peer sr-only">
                    <div class="text-center py-2.5 px-2 rounded-xl border border-stone-300 text-xs sm:text-sm font-semibold peer-checked:bg-green-700 peer-checked:text-white peer-checked:border-green-700">
                        🌾 <?= htmlspecialchars(__('role_farmer')) ?>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="role" value="buyer" class="peer sr-only">
                    <div class="text-center py-2.5 px-2 rounded-xl border border-stone-300 text-xs sm:text-sm font-semibold peer-checked:bg-green-700 peer-checked:text-white peer-checked:border-green-700">
                        🛒 <?= htmlspecialchars(__('role_buyer')) ?>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="role" value="supplier" class="peer sr-only">
                    <div class="text-center py-2.5 px-2 rounded-xl border border-stone-300 text-xs sm:text-sm font-semibold peer-checked:bg-green-700 peer-checked:text-white peer-checked:border-green-700">
                        🌱 <?= htmlspecialchars(__('role_supplier')) ?>
                    </div>
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">Full Name / Agribusiness</label>
                <input type="text" name="full_name" required placeholder="e.g. Kamau Njoroge"
                       class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-green-600 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">M-Pesa Phone Number</label>
                <input type="tel" name="phone" required placeholder="0712345678"
                       class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-green-600 focus:outline-none">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-stone-700 mb-1">Email Address</label>
            <input type="email" name="email" required placeholder="you@example.com"
                   class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-green-600 focus:outline-none">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">County</label>
                <select name="county" required class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm bg-white focus:ring-2 focus:ring-green-600 focus:outline-none">
                    <option value="">Select County...</option>
                    <option value="Nakuru">Nakuru</option>
                    <option value="Uasin Gishu">Uasin Gishu</option>
                    <option value="Kiambu">Kiambu</option>
                    <option value="Nairobi">Nairobi</option>
                    <option value="Meru">Meru</option>
                    <option value="Machakos">Machakos</option>
                    <option value="Kisumu">Kisumu</option>
                    <option value="Nyeri">Nyeri</option>
                    <option value="Trans Nzoia">Trans Nzoia</option>
                    <option value="Narok">Narok</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">Sub-County / Town</label>
                <input type="text" name="sub_county" placeholder="e.g. Rongai"
                       class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-green-600 focus:outline-none">
            </div>
        </div>

        <!-- GPS Location Capture for Nearby Matching -->
        <div class="bg-stone-100 p-3 rounded-xl flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-stone-700">Farm / Store GPS Pin (Optional)</p>
                <p id="gps-status" class="text-xs text-stone-500">Helps match nearby buyers & transport.</p>
            </div>
            <button type="button" id="capture-gps-btn" class="text-xs bg-white border border-stone-300 px-3 py-1.5 rounded-lg font-semibold hover:bg-stone-50">
                📍 Pin Location
            </button>
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">
        </div>

        <div>
            <label class="block text-sm font-medium text-stone-700 mb-1">Password (Min 8 characters)</label>
            <input type="password" name="password" required minlength="8" placeholder="••••••••"
                   class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-green-600 focus:outline-none">
        </div>

        <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-2.5 rounded-xl transition">
            <?= htmlspecialchars(__('register')) ?>
        </button>
    </form>
</div>
<script src="<?= base_url('assets/js/geolocation.js') ?>"></script>
