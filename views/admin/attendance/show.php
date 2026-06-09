<div class="space-y-6 max-w-3xl">
    <a href="/admin/attendance" class="inline-flex items-center gap-1 text-sm text-church-600 hover:underline">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back
    </a>

    <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm">
        <div class="flex flex-wrap gap-2 mb-3">
            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-church-50 text-church-700"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $session->type))) ?></span>
        </div>
        <h2 class="text-xl font-bold text-church-800"><?= htmlspecialchars($session->title) ?></h2>
        <p class="text-slate-500 mt-1"><?= date('l, F j, Y', strtotime($session->session_date)) ?><?= $session->start_time ? ' at ' . date('g:i A', strtotime($session->start_time)) : '' ?></p>
        <?php if ($session->location): ?>
        <p class="text-sm text-slate-400 mt-1"><?= htmlspecialchars($session->location) ?></p>
        <?php endif; ?>
        <p class="text-sm font-medium text-emerald-600 mt-3"><?= count(array_filter($records, fn($r) => $r['status'] === 'present')) ?> present</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-church-800">Attendance Roll</h3>
        </div>
        <div class="divide-y divide-slate-50">
            <?php foreach ($records as $r): ?>
            <div class="px-5 py-3 flex items-center justify-between">
                <p class="font-medium text-slate-800"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></p>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full <?= $r['status'] === 'present' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' ?>">
                    <?= ucfirst($r['status']) ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
