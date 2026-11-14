<div class="max-w-md mx-auto bg-white rounded-2xl shadow-sm border border-stone-200 p-6 sm:p-8 mt-6">
    <h1 class="text-2xl font-bold text-green-900 mb-1"><?= htmlspecialchars(__('login')) ?></h1>
    <p class="text-sm text-stone-500 mb-6"><?= htmlspecialchars(__('app_tagline')) ?></p>

    <form action="<?= base_url('login') ?>" method="POST" class="space-y-4">
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium text-stone-700 mb-1">Email Address</label>
            <input type="email" name="email" required placeholder="farmer@farmlink.co.ke"
                   class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
        </div>

        <div>
            <label class="block text-sm font-medium text-stone-700 mb-1">Password</label>
            <input type="password" name="password" required placeholder="••••••••"
                   class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
        </div>

        <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-2.5 rounded-xl transition">
            <?= htmlspecialchars(__('login')) ?>
        </button>
    </form>

    <p class="text-xs text-center text-stone-500 mt-5">
        New to FarmLink? <a href="<?= base_url('register') ?>" class="text-green-700 font-semibold hover:underline"><?= htmlspecialchars(__('register')) ?></a>
    </p>
</div>
