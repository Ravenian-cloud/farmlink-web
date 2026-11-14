<div class="max-w-5xl mx-auto space-y-6">
    <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-6">
        <h1 class="text-2xl font-extrabold text-green-900">Admin Governance, KYC & Escrow Audit</h1>
        <p class="text-xs text-stone-500 mt-1">Verify user identities, monitor escrow commissions, and broadcast county advisories.</p>
    </div>

    <!-- Escrow Financial Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <?php foreach ($escrowStats as $stat): ?>
            <div class="bg-white rounded-2xl border border-stone-200 p-5 shadow-sm">
                <p class="text-xs font-bold uppercase text-stone-400">Escrow: <?= htmlspecialchars($stat['status']) ?></p>
                <p class="text-xl font-extrabold text-stone-900 mt-1">KES <?= number_format((float) $stat['gross_kes'], 2) ?></p>
                <p class="text-xs text-emerald-700 mt-0.5">
                    Orders: <?= (int) $stat['total_orders'] ?> • Commission: KES <?= number_format((float) $stat['commission_kes'], 2) ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pending KYC Queue -->
    <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-6">
        <h2 class="text-base font-bold text-stone-900 mb-4">Pending KYC Verification Requests (<?= count($pendingKyc) ?>)</h2>
        <?php if (empty($pendingKyc)): ?>
            <p class="text-xs text-stone-400">No pending KYC documents awaiting review.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($pendingKyc as $applicant): ?>
                    <div class="p-4 rounded-xl border border-stone-200 bg-stone-50 flex flex-wrap items-center justify-between gap-4">
                        <div class="text-xs space-y-1">
                            <p class="font-bold text-sm text-stone-900"><?= htmlspecialchars($applicant['full_name']) ?> (<?= strtoupper(htmlspecialchars($applicant['role'])) ?>)</p>
                            <p class="text-stone-600"><?= htmlspecialchars($applicant['email']) ?> • <?= htmlspecialchars($applicant['phone']) ?> • <?= htmlspecialchars($applicant['county']) ?></p>
                            <?php if (!empty($applicant['kyc_document_path'])): ?>
                                <a href="<?= base_url($applicant['kyc_document_path']) ?>" target="_blank" class="text-blue-700 font-semibold underline inline-block mt-1">
                                    View Uploaded ID / Certificate ↗
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-2">
                            <form action="<?= base_url('admin/kyc/' . $applicant['id']) ?>" method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="decision" value="verified">
                                <button type="submit" class="bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold px-3.5 py-2 rounded-lg">
                                    Approve KYC
                                </button>
                            </form>
                            <form action="<?= base_url('admin/kyc/' . $applicant['id']) ?>" method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="decision" value="rejected">
                                <button type="submit" class="bg-red-100 hover:bg-red-200 text-red-900 text-xs font-semibold px-3.5 py-2 rounded-lg">
                                    Reject
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Broadcast Weather / Pest Alert -->
    <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-6">
        <h2 class="text-base font-bold text-stone-900 mb-4">Broadcast County Weather / Pest Advisory</h2>
        <form action="<?= base_url('admin/alerts') ?>" method="POST" class="space-y-3">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="text" name="county" required placeholder="County (e.g. Nakuru or All)" class="rounded-xl border border-stone-300 px-3 py-2 text-sm">
                <select name="alert_type" class="rounded-xl border border-stone-300 px-3 py-2 text-sm bg-white">
                    <option value="weather">Weather Forecast</option>
                    <option value="pest">Pest Outbreak</option>
                    <option value="disease">Crop/Livestock Disease</option>
                    <option value="market_price">Market Price Notice</option>
                </select>
                <select name="severity" class="rounded-xl border border-stone-300 px-3 py-2 text-sm bg-white">
                    <option value="info">Info</option>
                    <option value="warning">Warning</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            <input type="text" name="title" required placeholder="Alert headline..." class="w-full rounded-xl border border-stone-300 px-3 py-2 text-sm">
            <textarea name="message" rows="2" required placeholder="Detailed advisory instructions for farmers..." class="w-full rounded-xl border border-stone-300 px-3 py-2 text-sm"></textarea>
            <button type="submit" class="bg-green-700 hover:bg-green-800 text-white text-xs font-semibold px-4 py-2.5 rounded-xl">
                Publish Advisory
            </button>
        </form>
    </div>
</div>
