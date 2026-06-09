<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="bg-gradient-to-r from-emerald-500 to-church-600 rounded-2xl p-6 text-white flex-1">
            <p class="text-emerald-100 text-sm">Total Giving This Month</p>
            <p class="text-3xl font-bold mt-1">KES <?= number_format($monthTotal, 0) ?></p>
        </div>
        <div class="flex gap-2">
            <a href="/admin/finance/create" class="px-5 py-3 rounded-xl bg-church-600 text-white font-medium text-sm hover:bg-church-700 transition whitespace-nowrap">+ Record Giving</a>
            <a href="/admin/finance/mobile-money" class="px-5 py-3 rounded-xl border border-slate-200 text-slate-600 font-medium text-sm hover:bg-slate-50 transition whitespace-nowrap">M-Pesa</a>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($byFund as $f): ?>
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
            <p class="text-sm text-slate-500"><?= htmlspecialchars($f['name']) ?></p>
            <p class="text-xl font-bold text-church-800 mt-1">KES <?= number_format((float)$f['total'], 0) ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-church-800">Recent Transactions</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="text-left px-5 py-3 font-medium">Member</th>
                        <th class="text-left px-5 py-3 font-medium hidden md:table-cell">Fund</th>
                        <th class="text-left px-5 py-3 font-medium hidden sm:table-cell">Ref</th>
                        <th class="text-left px-5 py-3 font-medium">Date</th>
                        <th class="text-right px-5 py-3 font-medium">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($recent as $g): ?>
                    <tr>
                        <td class="px-5 py-3.5 font-medium text-slate-800"><?= htmlspecialchars($g['first_name'] . ' ' . $g['last_name']) ?></td>
                        <td class="px-5 py-3.5 hidden md:table-cell text-slate-500"><?= htmlspecialchars($g['fund_name']) ?></td>
                        <td class="px-5 py-3.5 hidden sm:table-cell font-mono text-xs text-slate-400"><?= htmlspecialchars($g['transaction_ref'] ?? '—') ?></td>
                        <td class="px-5 py-3.5 text-slate-500"><?= date('M j', strtotime($g['contribution_date'])) ?></td>
                        <td class="px-5 py-3.5 text-right font-semibold text-emerald-600">KES <?= number_format((float)$g['amount'], 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
