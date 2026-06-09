<div class="grid sm:grid-cols-2 gap-4">
    <?php foreach ($ministries as $m): ?>
    <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-church-500 to-church-700 flex items-center justify-center text-white font-bold text-lg">
                <?= strtoupper(substr($m['name'], 0, 1)) ?>
            </div>
            <span class="text-xs font-medium text-slate-400 bg-slate-50 px-2.5 py-1 rounded-full"><?= (int)$m['member_count'] ?> members</span>
        </div>
        <h3 class="font-semibold text-lg text-church-800 mt-4"><?= htmlspecialchars($m['name']) ?></h3>
        <?php if ($m['description']): ?>
        <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($m['description']) ?></p>
        <?php endif; ?>
        <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap gap-4 text-sm">
            <div>
                <p class="text-xs text-slate-400">Leader</p>
                <p class="font-medium text-slate-700"><?= htmlspecialchars(trim(($m['leader_first'] ?? '') . ' ' . ($m['leader_last'] ?? '')) ?: 'Not assigned') ?></p>
            </div>
            <?php if ($m['meeting_day']): ?>
            <div>
                <p class="text-xs text-slate-400">Meets</p>
                <p class="font-medium text-slate-700"><?= htmlspecialchars($m['meeting_day']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
