<div class="space-y-6 max-w-3xl">
    <a href="/admin/households" class="inline-flex items-center gap-1 text-sm text-church-600 hover:underline">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to households
    </a>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h2 class="text-xl font-bold text-church-800"><?= htmlspecialchars($household->name) ?></h2>
        <?php if ($household->address): ?>
        <p class="text-slate-500 mt-1"><?= htmlspecialchars($household->address) ?><?= $household->city ? ', ' . htmlspecialchars($household->city) : '' ?></p>
        <?php endif; ?>
        <?php if ($household->phone): ?>
        <p class="text-sm text-slate-400 mt-2"><?= htmlspecialchars($household->phone) ?></p>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-church-800">Family Members</h3>
        </div>
        <div class="divide-y divide-slate-50">
            <?php foreach ($members as $m): ?>
            <div class="px-5 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center font-medium text-slate-600">
                        <?= strtoupper(substr($m->first_name, 0, 1)) ?>
                    </div>
                    <div>
                        <p class="font-medium text-slate-800"><?= htmlspecialchars($m->fullName()) ?></p>
                        <p class="text-xs text-slate-400"><?= htmlspecialchars($m->email ?? $m->phone ?? '') ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <?php if ($m->is_head_of_household): ?>
                    <span class="text-xs font-medium text-church-600 bg-church-50 px-2 py-0.5 rounded-full">Head</span>
                    <?php endif; ?>
                    <span class="text-xs text-slate-400"><?= ucfirst($m->membership_status) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
