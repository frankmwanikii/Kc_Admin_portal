<div class="space-y-6">
    <div class="flex justify-between items-center">
        <p class="text-sm text-slate-500">Track service, cell group, and event attendance</p>
        <a href="/admin/attendance/create" class="px-4 py-2 rounded-xl bg-church-600 text-white text-sm font-medium hover:bg-church-700 transition">+ New Session</a>
    </div>

    <?php if (!empty($missedMembers)): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
        <h3 class="font-semibold text-amber-800 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <?= count($missedMembers) ?> members need follow-up
        </h3>
        <p class="text-sm text-amber-700 mt-1">These members have missed 3+ weeks of attendance.</p>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="text-left px-5 py-3 font-medium">Session</th>
                        <th class="text-left px-5 py-3 font-medium hidden sm:table-cell">Type</th>
                        <th class="text-left px-5 py-3 font-medium">Date</th>
                        <th class="text-right px-5 py-3 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($sessions as $s): ?>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-5 py-3.5 font-medium text-slate-800"><?= htmlspecialchars($s->title) ?></td>
                        <td class="px-5 py-3.5 hidden sm:table-cell">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-church-50 text-church-700"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $s->type))) ?></span>
                        </td>
                        <td class="px-5 py-3.5 text-slate-500"><?= date('M j, Y', strtotime($s->session_date)) ?></td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="/admin/attendance/<?= $s->id ?>" class="text-church-600 font-medium hover:underline">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
