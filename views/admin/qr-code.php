<div class="max-w-md mx-auto text-center space-y-6">
    <div class="bg-white rounded-2xl border border-slate-100 p-8 shadow-sm">
        <h2 class="text-lg font-semibold text-church-800 mb-2">Member Onboarding QR</h2>
        <p class="text-sm text-slate-500 mb-6">Print and place at church entrance. New members scan to self-register.</p>
        <img src="<?= $qrDataUri ?>" alt="Onboarding QR Code" class="mx-auto rounded-xl border border-slate-100 p-2">
        <p class="mt-4 text-xs text-slate-400 break-all font-mono"><?= htmlspecialchars($url) ?></p>
    </div>
    <p class="text-sm text-slate-500">Members who complete registration receive an email with a secure portal login link.</p>
</div>
