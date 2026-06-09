<div class="space-y-6">
    <p class="text-sm text-slate-500">Members celebrating birthdays today — send automated wishes via Communications.</p>

    <?php if (empty($members)): ?>
    <div class="bg-white rounded-2xl p-10 text-center border border-slate-100">
        <p class="text-slate-500">No birthdays today.</p>
    </div>
    <?php else: ?>
    <div class="grid sm:grid-cols-2 gap-4">
        <?php foreach ($members as $m): ?>
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-gold-400 to-gold-500 flex items-center justify-center text-white text-lg">🎂</div>
            <div>
                <p class="font-semibold text-slate-800"><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?></p>
                <p class="text-sm text-slate-500"><?= htmlspecialchars($m['email'] ?? $m['phone'] ?? '') ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
