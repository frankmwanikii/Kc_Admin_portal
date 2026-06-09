<div class="space-y-6" x-data="{ showFilters: false }">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-church-800">Giving History</h2>
            <p class="text-sm text-slate-500 mt-0.5">Your complete contribution timeline</p>
        </div>
        <div class="flex gap-2">
            <button @click="showFilters = !showFilters" class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition">
                Filter dates
            </button>
            <a href="/portal/statement?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>" class="px-4 py-2 rounded-xl bg-church-600 text-white text-sm font-medium hover:bg-church-700 transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Download PDF
            </a>
        </div>
    </div>

    <div x-show="showFilters" x-cloak x-transition class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
        <form method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
            <div class="flex-1 w-full">
                <label class="block text-sm font-medium text-slate-700 mb-1">From</label>
                <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-base">
            </div>
            <div class="flex-1 w-full">
                <label class="block text-sm font-medium text-slate-700 mb-1">To</label>
                <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-base">
            </div>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-church-600 text-white font-medium text-sm">Apply</button>
        </form>
    </div>

    <div class="bg-gradient-to-r from-emerald-50 to-church-50 rounded-2xl p-5 border border-emerald-100">
        <p class="text-sm text-slate-600">Total for selected period</p>
        <p class="text-3xl font-bold text-church-800 mt-1">KES <?= number_format($total, 2) ?></p>
    </div>

    <div class="space-y-3">
        <?php if (empty($contributions)): ?>
        <div class="bg-white rounded-2xl p-8 text-center border border-slate-100">
            <p class="text-slate-500">No contributions found for this period.</p>
        </div>
        <?php else: foreach ($contributions as $c): ?>
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm hover:shadow-md transition">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-church-50 text-church-600 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($c['fund_name']) ?></p>
                        <p class="text-sm text-slate-500 mt-0.5"><?= date('l, M j, Y', strtotime($c['contribution_date'])) ?></p>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-xs text-slate-600"><?= htmlspecialchars(ucfirst($c['payment_method'])) ?></span>
                            <?php if ($c['transaction_ref']): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-church-50 text-xs text-church-700 font-mono"><?= htmlspecialchars($c['transaction_ref']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <p class="text-lg font-bold text-emerald-600 shrink-0">KES <?= number_format((float)$c['amount'], 2) ?></p>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>
