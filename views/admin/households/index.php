<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-sm text-slate-500"><?= count($households) ?> households registered</p>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($households as $h): ?>
        <a href="/admin/households/<?= $h['id'] ?>" class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm hover:shadow-md hover:border-church-200 transition group">
            <div class="flex items-start justify-between">
                <div class="w-11 h-11 rounded-xl bg-church-50 text-church-600 flex items-center justify-center group-hover:bg-church-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                </div>
                <span class="text-xs font-medium text-slate-400 bg-slate-50 px-2 py-0.5 rounded-full"><?= (int)$h['member_count'] ?> members</span>
            </div>
            <h3 class="font-semibold text-church-800 mt-3"><?= htmlspecialchars($h['name']) ?></h3>
            <?php if ($h['address']): ?>
            <p class="text-sm text-slate-500 mt-1 truncate"><?= htmlspecialchars($h['address']) ?><?= $h['city'] ? ', ' . htmlspecialchars($h['city']) : '' ?></p>
            <?php endif; ?>
            <?php if ($h['phone']): ?>
            <p class="text-xs text-slate-400 mt-2"><?= htmlspecialchars($h['phone']) ?></p>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>
