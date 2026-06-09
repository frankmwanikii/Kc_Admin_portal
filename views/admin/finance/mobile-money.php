<div class="space-y-6">
    <div class="bg-church-50 border border-church-100 rounded-2xl p-5">
        <h3 class="font-semibold text-church-800">Mobile Money Integration</h3>
        <p class="text-sm text-church-600 mt-1">Import M-Pesa or other mobile money statements to automatically match and record contributions. Configure webhook endpoints in your payment provider dashboard.</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-church-800">Statement Queue</h3>
        </div>
        <?php if (empty($statements)): ?>
        <p class="p-8 text-center text-slate-500 text-sm">No mobile money statements received yet.</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="text-left px-5 py-3">Ref</th>
                        <th class="text-left px-5 py-3">Phone</th>
                        <th class="text-right px-5 py-3">Amount</th>
                        <th class="text-left px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($statements as $s): ?>
                    <tr>
                        <td class="px-5 py-3 font-mono text-xs"><?= htmlspecialchars($s['transaction_ref']) ?></td>
                        <td class="px-5 py-3"><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                        <td class="px-5 py-3 text-right font-medium">KES <?= number_format((float)$s['amount'], 0) ?></td>
                        <td class="px-5 py-3"><span class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-50 text-amber-700"><?= ucfirst($s['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
