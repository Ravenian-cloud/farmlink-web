<div class="max-w-lg mx-auto bg-white rounded-2xl shadow-sm border border-stone-200 p-6 sm:p-8 mt-4">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-bold text-green-900"><?= htmlspecialchars(__('kyc_verification')) ?></h1>
        <span class="text-xs font-bold px-3 py-1 rounded-full bg-amber-100 text-amber-900">
            Status: <?= strtoupper(htmlspecialchars($user['kyc_status'] ?? 'unverified')) ?>
        </span>
    </div>

    <p class="text-sm text-stone-600 mb-6">
        Upload a clear copy of your National ID, Cooperative Certificate, or Agrovet Business Permit to build buyer trust and unlock escrow payouts.
    </p>

    <form action="<?= base_url('kyc/upload') ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
        <?= csrf_field() ?>

        <div class="border-2 border-dashed border-stone-300 rounded-xl p-6 text-center bg-stone-50">
            <input type="file" name="kyc_document" accept=".jpg,.jpeg,.png,.pdf" required
                   class="block w-full text-sm text-stone-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-green-700 file:text-white hover:file:bg-green-800">
            <p class="text-xs text-stone-400 mt-2">Accepted formats: JPG, PNG, PDF (Max 5MB)</p>
        </div>

        <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-2.5 rounded-xl transition">
            Submit Verification Document
        </button>
    </form>
</div>
