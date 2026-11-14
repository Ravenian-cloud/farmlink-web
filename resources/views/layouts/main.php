<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['lang'] ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="theme-color" content="#15803d">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <title><?= htmlspecialchars($title ?? 'FarmLink') ?> | FarmLink</title>
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        window.FARMLINK = {
            baseUrl: "<?= base_url() ?>",
            csrfToken: "<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>",
            isLoggedIn: <?= !empty($_SESSION['user_id']) ? 'true' : 'false' ?>
        };
    </script>
</head>
<body class="bg-stone-50 text-stone-900 min-h-screen flex flex-col pb-16 md:pb-0">

    <!-- Offline Network Status Banner -->
    <div id="offline-banner" class="hidden bg-amber-600 text-white text-xs font-medium text-center py-2 px-4 sticky top-0 z-50">
        <?= htmlspecialchars(__('offline_banner')) ?>
    </div>

    <!-- Top Navigation Bar -->
    <header class="bg-green-800 text-white shadow-md sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="<?= base_url('marketplace') ?>" class="flex items-center gap-2 font-bold text-xl tracking-tight">
                <span class="bg-amber-400 text-green-950 px-2 py-0.5 rounded-md font-extrabold">FL</span>
                <span>FarmLink</span>
            </a>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a href="<?= base_url('marketplace') ?>" class="hover:text-amber-300 transition"><?= htmlspecialchars(__('marketplace')) ?></a>
                <a href="<?= base_url('community') ?>" class="hover:text-amber-300 transition"><?= htmlspecialchars(__('community')) ?></a>
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <a href="<?= base_url('dashboard') ?>" class="hover:text-amber-300 transition"><?= htmlspecialchars(__('dashboard')) ?></a>
                    <a href="<?= base_url('alerts') ?>" class="hover:text-amber-300 transition"><?= htmlspecialchars(__('alerts')) ?></a>
                    <?php if (in_array($_SESSION['user_role'] ?? '', ['farmer', 'supplier'], true)): ?>
                        <a href="<?= base_url('listings/create') ?>" class="bg-amber-400 text-green-950 px-3.5 py-1.5 rounded-lg font-semibold hover:bg-amber-300 transition">
                            + <?= htmlspecialchars(__('sell_produce')) ?>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>

            <!-- Language Switcher & Auth Controls -->
            <div class="flex items-center gap-3 text-sm">
                <div class="flex items-center bg-green-900 rounded-lg p-0.5 text-xs font-semibold">
                    <a href="<?= base_url('lang/en') ?>" class="px-2 py-1 rounded <?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'bg-white text-green-900' : 'text-green-200' ?>">EN</a>
                    <a href="<?= base_url('lang/sw') ?>" class="px-2 py-1 rounded <?= ($_SESSION['lang'] ?? 'en') === 'sw' ? 'bg-white text-green-900' : 'text-green-200' ?>">SW</a>
                </div>

                <?php if (!empty($_SESSION['user_id'])): ?>
                    <a href="<?= base_url('kyc') ?>" class="hidden sm:inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full <?= ($_SESSION['kyc_status'] ?? '') === 'verified' ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-green-950 font-semibold' ?>">
                        <?= strtoupper(htmlspecialchars($_SESSION['kyc_status'] ?? 'UNVERIFIED')) ?>
                    </a>
                    <form action="<?= base_url('logout') ?>" method="POST" class="inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="text-green-100 hover:text-white text-xs underline ml-1">
                            <?= htmlspecialchars(__('logout')) ?>
                        </button>
                    </form>
                <?php else: ?>
                    <a href="<?= base_url('login') ?>" class="text-green-100 hover:text-white font-medium"><?= htmlspecialchars(__('login')) ?></a>
                    <a href="<?= base_url('register') ?>" class="bg-amber-400 text-green-950 px-3 py-1.5 rounded-lg font-semibold hover:bg-amber-300 transition">
                        <?= htmlspecialchars(__('register')) ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Flash Notifications -->
    <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="bg-emerald-50 border border-emerald-300 text-emerald-900 px-4 py-3 rounded-lg text-sm mb-3">
                <?= htmlspecialchars($_SESSION['flash_success']) ?>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="bg-red-50 border border-red-300 text-red-900 px-4 py-3 rounded-lg text-sm mb-3">
                <?= htmlspecialchars($_SESSION['flash_error']) ?>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>
    </div>

    <!-- Main Page Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <?= $content ?>
    </main>

    <!-- Mobile App Bottom Navigation Bar -->
    <nav class="md:hidden fixed bottom-0 inset-x-0 bg-white border-t border-stone-200 flex justify-around items-center h-16 z-40 text-xs font-medium text-stone-600">
        <a href="<?= base_url('marketplace') ?>" class="flex flex-col items-center justify-center py-1 hover:text-green-700">
            <span class="font-bold text-base">🌾</span>
            <span><?= htmlspecialchars(__('marketplace')) ?></span>
        </a>
        <?php if (!empty($_SESSION['user_id'])): ?>
            <a href="<?= base_url('dashboard') ?>" class="flex flex-col items-center justify-center py-1 hover:text-green-700">
                <span class="font-bold text-base">📊</span>
                <span><?= htmlspecialchars(__('dashboard')) ?></span>
            </a>
            <?php if (in_array($_SESSION['user_role'] ?? '', ['farmer', 'supplier'], true)): ?>
                <a href="<?= base_url('listings/create') ?>" class="flex flex-col items-center justify-center -mt-4 bg-green-700 text-white rounded-full w-12 h-12 shadow-lg">
                    <span class="text-xl font-bold">+</span>
                </a>
            <?php endif; ?>
            <a href="<?= base_url('alerts') ?>" class="flex flex-col items-center justify-center py-1 hover:text-green-700">
                <span class="font-bold text-base">🌦️</span>
                <span><?= htmlspecialchars(__('alerts')) ?></span>
            </a>
        <?php endif; ?>
        <a href="<?= base_url('community') ?>" class="flex flex-col items-center justify-center py-1 hover:text-green-700">
            <span class="font-bold text-base">💬</span>
            <span><?= htmlspecialchars(__('community')) ?></span>
        </a>
    </nav>

    <script src="<?= base_url('assets/js/offline-db.js') ?>"></script>
    <script src="<?= base_url('assets/js/sync-manager.js') ?>"></script>
    <script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>
