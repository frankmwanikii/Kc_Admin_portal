<div class="space-y-6">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <?php
        $cards = [
            ['Active Members', $stats['members'], 'text-church-600', 'bg-church-50'],
            ['Households', $stats['households'], 'text-emerald-600', 'bg-emerald-50'],
            ['Giving (Month)', 'KES ' . number_format($stats['giving_month'], 0), 'text-amber-600', 'bg-amber-50'],
            ['Present Today', $stats['attendance_today'], 'text-violet-600', 'bg-violet-50'],
        ];
        foreach ($cards as [$label, $value, $color, $bg]):
        ?>
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
            <p class="text-sm text-slate-500"><?= $label ?></p>
            <p class="text-2xl font-bold <?= $color ?> mt-1"><?= $value ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Recent Giving -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-semibold text-church-800">Recent Contributions</h3>
                <a href="/admin/finance" class="text-sm text-church-600 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-slate-50">
                <?php foreach ($recentGiving as $g): ?>
                <div class="px-5 py-3 flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($g['first_name'] . ' ' . $g['last_name']) ?></p>
                        <p class="text-xs text-slate-400"><?= htmlspecialchars($g['fund_name']) ?> · <?= date('M j', strtotime($g['contribution_date'])) ?></p>
                    </div>
                    <span class="text-sm font-semibold text-emerald-600">KES <?= number_format((float)$g['amount'], 0) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Missed Attendance -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="font-semibold text-church-800">Needs Follow-up</h3>
                <p class="text-xs text-slate-400 mt-0.5">Members absent 3+ weeks</p>
            </div>
            <div class="divide-y divide-slate-50">
                <?php if (empty($missedMembers)): ?>
                <p class="px-5 py-4 text-sm text-slate-500">All members accounted for.</p>
                <?php else: foreach ($missedMembers as $m): ?>
                <div class="px-5 py-3 flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($m->fullName()) ?></p>
                        <p class="text-xs text-slate-400"><?= htmlspecialchars($m->phone ?? 'No phone') ?></p>
                    </div>
                    <span class="text-xs font-medium text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">Follow up</span>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Ministries -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-semibold text-church-800">Ministries Overview</h3>
            <a href="/admin/ministries" class="text-sm text-church-600 hover:underline">Manage</a>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-slate-50">
            <?php foreach ($ministries as $min): ?>
            <div class="p-5">
                <p class="font-medium text-slate-800"><?= htmlspecialchars($min['name']) ?></p>
                <p class="text-xs text-slate-400 mt-1"><?= htmlspecialchars(($min['leader_first'] ?? '') . ' ' . ($min['leader_last'] ?? 'No leader')) ?></p>
                <p class="text-sm text-church-600 mt-2"><?= (int)$min['member_count'] ?> members</p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
