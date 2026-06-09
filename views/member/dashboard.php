<div class="space-y-6">
    <!-- Welcome -->
    <div class="bg-gradient-to-br from-church-700 to-church-900 rounded-2xl p-6 sm:p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-40 h-40 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <p class="text-church-200 text-sm">Welcome back</p>
        <h2 class="text-2xl sm:text-3xl font-bold mt-1"><?= htmlspecialchars($member->first_name) ?></h2>
        <?php if ($household): ?>
        <p class="text-church-200 mt-2 text-sm flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <?= htmlspecialchars($household->name) ?> Household
        </p>
        <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
            <p class="text-sm text-slate-500">Giving (YTD)</p>
            <p class="text-2xl font-bold text-church-800 mt-1">KES <?= number_format($totalGiving, 0) ?></p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
            <p class="text-sm text-slate-500">Active Pledges</p>
            <p class="text-2xl font-bold text-church-800 mt-1"><?= count($pledges) ?></p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm col-span-2 lg:col-span-1">
            <p class="text-sm text-slate-500">Member Since</p>
            <p class="text-2xl font-bold text-church-800 mt-1"><?= $member->joined_date ? date('M Y', strtotime($member->joined_date)) : '—' ?></p>
        </div>
    </div>

    <!-- Recent Giving -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-church-800">Recent Giving</h3>
            <a href="/portal/giving" class="text-sm text-church-600 font-medium hover:underline">View all</a>
        </div>
        <div class="divide-y divide-slate-50">
            <?php if (empty($recentGiving)): ?>
            <p class="p-5 text-sm text-slate-500">No contributions recorded yet.</p>
            <?php else: foreach ($recentGiving as $c): ?>
            <div class="px-5 py-4 flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="font-medium text-slate-800 truncate"><?= htmlspecialchars($c['fund_name']) ?></p>
                    <p class="text-xs text-slate-400 mt-0.5"><?= date('M j, Y', strtotime($c['contribution_date'])) ?> · <?= htmlspecialchars($c['transaction_ref'] ?? '—') ?></p>
                </div>
                <span class="font-semibold text-emerald-600 shrink-0">KES <?= number_format((float)$c['amount'], 0) ?></span>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Pledges preview -->
    <?php if (!empty($pledges)): ?>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-church-800">Pledge Progress</h3>
            <a href="/portal/pledges" class="text-sm text-church-600 font-medium hover:underline">Details</a>
        </div>
        <div class="p-5 space-y-4">
            <?php foreach (array_slice($pledges, 0, 2) as $p):
                $pct = $p['pledged_amount'] > 0 ? min(100, ($p['amount_paid'] / $p['pledged_amount']) * 100) : 0;
            ?>
            <div>
                <div class="flex justify-between text-sm mb-1.5">
                    <span class="font-medium text-slate-700"><?= htmlspecialchars($p['campaign_title']) ?></span>
                    <span class="text-slate-500"><?= number_format($pct, 0) ?>%</span>
                </div>
                <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-church-500 to-church-600 rounded-full transition-all" style="width: <?= $pct ?>%"></div>
                </div>
                <p class="text-xs text-slate-400 mt-1">KES <?= number_format((float)$p['amount_paid'], 0) ?> of <?= number_format((float)$p['pledged_amount'], 0) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
