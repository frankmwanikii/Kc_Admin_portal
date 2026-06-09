<div class="space-y-6">
    <div>
        <h2 class="text-xl font-bold text-church-800">Pledge Tracker</h2>
        <p class="text-sm text-slate-500 mt-0.5">Track your progress on active fundraisers</p>
    </div>

    <?php if (empty($pledges)): ?>
    <div class="bg-white rounded-2xl p-10 text-center border border-slate-100">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <p class="text-slate-600 font-medium">No active pledges</p>
        <p class="text-sm text-slate-400 mt-1">When you pledge to a fundraiser, it will appear here.</p>
    </div>
    <?php else: foreach ($pledges as $p):
        $pct = $p['pledged_amount'] > 0 ? min(100, ($p['amount_paid'] / $p['pledged_amount']) * 100) : 0;
        $remaining = max(0, $p['pledged_amount'] - $p['amount_paid']);
    ?>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="p-5 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-semibold text-lg text-church-800"><?= htmlspecialchars($p['campaign_title']) ?></h3>
                    <?php if ($p['campaign_description']): ?>
                    <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($p['campaign_description']) ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($p['end_date']): ?>
                <span class="shrink-0 text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">Due <?= date('M j, Y', strtotime($p['end_date'])) ?></span>
                <?php endif; ?>
            </div>

            <div class="mt-6">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-slate-600">Progress</span>
                    <span class="font-semibold text-church-700"><?= number_format($pct, 1) ?>%</span>
                </div>
                <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-church-500 via-church-600 to-emerald-500 rounded-full transition-all duration-500" style="width: <?= $pct ?>%"></div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mt-6 pt-5 border-t border-slate-100">
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wide">Pledged</p>
                    <p class="font-bold text-slate-800 mt-0.5">KES <?= number_format((float)$p['pledged_amount'], 0) ?></p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wide">Paid</p>
                    <p class="font-bold text-emerald-600 mt-0.5">KES <?= number_format((float)$p['amount_paid'], 0) ?></p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wide">Remaining</p>
                    <p class="font-bold text-amber-600 mt-0.5">KES <?= number_format($remaining, 0) ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>
