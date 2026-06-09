<div class="max-w-lg">
    <form method="POST" action="/admin/finance" class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm space-y-5">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Member</label>
            <select name="member_id" required class="w-full px-4 py-3 rounded-xl border border-slate-200 text-base focus:ring-2 focus:ring-church-500 outline-none">
                <option value="">Select member...</option>
                <?php foreach ($members as $m): ?>
                <option value="<?= $m->id ?>"><?= htmlspecialchars($m->fullName()) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Fund</label>
            <select name="fund_id" required class="w-full px-4 py-3 rounded-xl border border-slate-200 text-base focus:ring-2 focus:ring-church-500 outline-none">
                <?php foreach ($funds as $f): ?>
                <option value="<?= $f->id ?>"><?= htmlspecialchars($f->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Amount (KES)</label>
            <input type="number" name="amount" step="0.01" min="0" required class="w-full px-4 py-3 rounded-xl border border-slate-200 text-base focus:ring-2 focus:ring-church-500 outline-none">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Payment Method</label>
                <select name="payment_method" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-base focus:ring-2 focus:ring-church-500 outline-none">
                    <option value="mpesa">M-Pesa</option>
                    <option value="cash">Cash</option>
                    <option value="bank">Bank Transfer</option>
                    <option value="cheque">Cheque</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Date</label>
                <input type="date" name="contribution_date" value="<?= date('Y-m-d') ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-base focus:ring-2 focus:ring-church-500 outline-none">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Transaction Reference</label>
            <input type="text" name="transaction_ref" placeholder="Auto-generated if empty" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-base focus:ring-2 focus:ring-church-500 outline-none">
        </div>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="send_sms" value="1" checked class="w-4 h-4 rounded text-church-600 focus:ring-church-500">
            <span class="text-sm text-slate-600">Send SMS acknowledgment</span>
        </label>
        <button type="submit" class="w-full py-3.5 rounded-xl bg-church-600 text-white font-semibold hover:bg-church-700 transition">Record Contribution</button>
    </form>
</div>
