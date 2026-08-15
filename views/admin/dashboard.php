<?php
use App\Core\Auth;

$monthLabel = date('F Y');
$welcomeName = Auth::user()?->fullName() ?? '';
/** Safe JSON for inline <script> — do not use htmlspecialchars (breaks JS). */
$chartJson = fn ($data) => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_THROW_ON_ERROR);

$financeYearLabels = array_column($charts['financeYear']['months'] ?? [], 'label');
$financeYearCollections = array_map('floatval', array_column($charts['financeYear']['months'] ?? [], 'collections'));
$financeYearExpenses = array_map('floatval', array_column($charts['financeYear']['months'] ?? [], 'expenses'));

$weekLabels = [];
$weekCollections = [];
$weekExpenses = [];
foreach ($charts['financeMonth']['weeks'] ?? [] as $w) {
    $weekLabels[] = date('M j', strtotime($w['week_date']));
    $weekCollections[] = (float) $w['collections'];
    $weekExpenses[] = (float) $w['expenses'];
}

$collectionMethods = [
    ['Paybill', (float) ($charts['collectionsMonth']['paybill'] ?? 0)],
    ['Cheque', (float) ($charts['collectionsMonth']['cheque'] ?? 0)],
    ['Cash', (float) ($charts['collectionsMonth']['cash'] ?? 0)],
];
$collectionMethods = array_values(array_filter($collectionMethods, fn ($m) => $m[1] > 0));

$expenseLabels = array_column($charts['expenseBreakdown'] ?? [], 'label');
$expenseAmounts = array_map('floatval', array_column($charts['expenseBreakdown'] ?? [], 'amount'));

$statusLabels = [];
$statusCounts = [];
$statusColors = [
    'new' => '#f59e0b',
    'reviewed' => '#2da0d9',
    'approved' => '#10b981',
    'rejected' => '#ef4444',
];
$statusBg = [];
foreach ($charts['membersStatus'] ?? [] as $status => $count) {
    $statusLabels[] = ucfirst($status);
    $statusCounts[] = (int) $count;
    $statusBg[] = $statusColors[$status] ?? '#94a3b8';
}

$statCards = [
    [
        'label' => 'Total members',
        'value' => number_format($stats['members']),
        'sub' => 'Registered in system',
        'icon' => 'users',
        'accent' => 'from-church-500 to-church-700',
        'href' => '/admin/members',
    ],
    [
        'label' => 'Pending review',
        'value' => number_format($stats['new_members']),
        'sub' => 'Awaiting action',
        'icon' => 'user-plus',
        'accent' => 'from-amber-400 to-orange-500',
        'href' => '/admin/members',
    ],
    [
        'label' => 'Collections',
        'value' => 'KES ' . number_format($stats['collections_month'], 0),
        'sub' => $monthLabel,
        'icon' => 'trending-up',
        'accent' => 'from-emerald-400 to-emerald-600',
        'href' => '/admin/finance?tab=collections',
    ],
    [
        'label' => 'Arrears outstanding',
        'value' => 'KES ' . number_format($stats['arrears_outstanding'], 0),
        'sub' => 'Expense arrears ' . date('Y'),
        'icon' => 'alert-circle',
        'accent' => 'from-rose-400 to-red-600',
        'href' => '/admin/finance?tab=arrears',
    ],
];
?>

<div class="space-y-8 admin-dashboard">
    <!-- Welcome banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-church-800 via-church-700 to-church-900 text-white shadow-lg shadow-church-900/20">
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 20% 50%, white 1px, transparent 1px); background-size: 24px 24px;"></div>
        <div class="relative px-6 py-7 sm:px-8 sm:py-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-white/60 text-sm font-medium"><?= date('l, F j, Y') ?></p>
                <h2 class="text-2xl sm:text-3xl font-bold mt-1 tracking-tight">Welcome back<?= $welcomeName !== '' ? ', ' . htmlspecialchars($welcomeName) : '' ?></h2>
                <p class="text-white/70 text-sm mt-2 max-w-lg">Overview of members, finances, and church activity for <?= htmlspecialchars($churchName ?? 'your church') ?>.</p>
            </div>
            <div class="flex flex-wrap gap-2 shrink-0">
                <a href="/admin/finance" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/15 hover:bg-white/25 text-sm font-medium backdrop-blur transition">
                    <i data-lucide="wallet" class="w-4 h-4"></i> Finance
                </a>
                <a href="/admin/members" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-church-800 hover:bg-white/90 text-sm font-medium transition shadow-sm">
                    <i data-lucide="users" class="w-4 h-4"></i> Members
                </a>
            </div>
        </div>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <?php foreach ($statCards as $card): ?>
        <a href="<?= $card['href'] ?>" class="group relative bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md hover:border-church-100 transition-all duration-200 overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br <?= $card['accent'] ?> opacity-10 rounded-bl-full transition-opacity group-hover:opacity-20"></div>
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm text-slate-500 font-medium"><?= $card['label'] ?></p>
                    <p class="text-2xl font-bold text-slate-900 mt-1 truncate"><?= $card['value'] ?></p>
                    <p class="text-xs text-slate-400 mt-1"><?= $card['sub'] ?></p>
                </div>
                <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-gradient-to-br <?= $card['accent'] ?> text-white shadow-sm shrink-0">
                    <i data-lucide="<?= $card['icon'] ?>" class="w-5 h-5"></i>
                </span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Financial charts -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-church-800">Financial overview</h3>
                <p class="text-sm text-slate-500">Collections vs expenses from live finance data</p>
            </div>
            <a href="/admin/finance" class="text-sm text-church-600 hover:text-church-700 font-medium inline-flex items-center gap-1">
                Open finance <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
        <div class="grid lg:grid-cols-5 gap-4">
            <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="font-medium text-slate-800"><?= date('Y') ?> — monthly trend</h4>
                    <div class="flex items-center gap-4 text-xs admin-dashboard-chart-legend">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Collections</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span> Expenses</span>
                    </div>
                </div>
                <div class="h-64 sm:h-72">
                    <canvas id="chartFinanceYear"></canvas>
                </div>
            </div>
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h4 class="font-medium text-slate-800 mb-1"><?= $monthLabel ?> — by payment method</h4>
                <p class="text-xs text-slate-400 mb-4">Weekly collection totals</p>
                <div class="h-52 flex items-center justify-center">
                    <canvas id="chartCollections"></canvas>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-3 text-center">
                    <div>
                        <p class="text-xs text-slate-400">Month expenses</p>
                        <p class="text-sm font-semibold text-rose-600">KES <?= number_format($stats['weekly_month'], 0) ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Net balance</p>
                        <p class="text-sm font-semibold <?= ($stats['month_balance'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' ?>">
                            KES <?= number_format($stats['month_balance'], 0) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4 mt-4">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h4 class="font-medium text-slate-800 mb-1"><?= $monthLabel ?> — weekly cash flow</h4>
                <p class="text-xs text-slate-400 mb-4">Sunday-by-Sunday reconciliation</p>
                <div class="h-56">
                    <canvas id="chartWeeklyFlow"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h4 class="font-medium text-slate-800 mb-1"><?= $monthLabel ?> — expense breakdown</h4>
                <p class="text-xs text-slate-400 mb-4">Top spending categories</p>
                <div class="h-56">
                    <canvas id="chartExpenses"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Member charts -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-church-800">Members</h3>
                <p class="text-sm text-slate-500">Registration trends and status breakdown</p>
            </div>
            <a href="/admin/members" class="text-sm text-church-600 hover:text-church-700 font-medium inline-flex items-center gap-1">
                Manage members <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
        <div class="grid lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h4 class="font-medium text-slate-800 mb-1">Registrations — last 6 months</h4>
                <p class="text-xs text-slate-400 mb-4">New member sign-ups over time</p>
                <div class="h-56">
                    <canvas id="chartMembersTrend"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h4 class="font-medium text-slate-800 mb-1">Status breakdown</h4>
                <p class="text-xs text-slate-400 mb-4">Current pipeline</p>
                <div class="h-48 flex items-center justify-center">
                    <canvas id="chartMembersStatus"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent members + quick links -->
    <div class="grid lg:grid-cols-5 gap-4">
        <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h3 class="font-semibold text-church-800">Recent registrations</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Latest member submissions</p>
                </div>
                <a href="/admin/members" class="text-sm text-church-600 hover:text-church-700 font-medium">View all</a>
            </div>
            <?php if (empty($recentMembers)): ?>
            <div class="px-5 py-12 text-center">
                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="users" class="w-6 h-6 text-slate-400"></i>
                </div>
                <p class="text-sm text-slate-500">No registrations yet.</p>
            </div>
            <?php else: ?>
            <div class="divide-y divide-slate-50">
                <?php foreach ($recentMembers as $m):
                    $status = $m['status'] ?? 'new';
                    $statusStyle = match ($status) {
                        'new' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                        'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                        'rejected' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                        default => 'bg-slate-50 text-slate-600 ring-slate-500/10',
                    };
                    $initials = strtoupper(substr(trim($m['submitter_name'] ?? '?'), 0, 1));
                ?>
                <a href="/admin/members/<?= (int) $m['id'] ?>" class="px-5 py-3.5 flex items-center gap-4 hover:bg-church-50/30 transition group">
                    <span class="flex items-center justify-center w-10 h-10 rounded-full bg-church-100 text-church-700 text-sm font-semibold shrink-0 group-hover:bg-church-200 transition">
                        <?= htmlspecialchars($initials) ?>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-800 truncate"><?= htmlspecialchars($m['submitter_name'] ?? '—') ?></p>
                        <p class="text-xs text-slate-400 truncate"><?= htmlspecialchars($m['submitter_phone'] ?? '') ?> · <?= date('M j, Y', strtotime($m['created_at'])) ?></p>
                    </div>
                    <span class="text-xs font-medium px-2.5 py-1 rounded-full ring-1 ring-inset <?= $statusStyle ?> shrink-0"><?= ucfirst($status) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-church-800">Quick actions</h3>
                <p class="text-xs text-slate-400 mt-0.5">Jump to common tasks</p>
            </div>
            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-2">
                <?php
                $links = [
                    ['/admin/finance?tab=arrears', 'Expense arrears', 'alert-circle', 'text-rose-600 bg-rose-50'],
                    ['/admin/finance?tab=weekly', 'Weekly expenses', 'receipt', 'text-church-600 bg-church-50'],
                    ['/admin/finance?tab=collections', 'Collections', 'banknote', 'text-emerald-600 bg-emerald-50'],
                    ['/admin/inventory', 'Inventory (' . (int) ($stats['inventory'] ?? 0) . ')', 'package', 'text-violet-600 bg-violet-50'],
                    ['/admin/communications', 'Communications', 'megaphone', 'text-amber-600 bg-amber-50'],
                    ['/admin/settings', 'Settings', 'settings', 'text-slate-600 bg-slate-100'],
                ];
                foreach ($links as [$href, $label, $icon, $style]):
                ?>
                <a href="<?= $href ?>" class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-100 hover:border-church-200 hover:bg-church-50/20 transition group">
                    <span class="flex items-center justify-center w-9 h-9 rounded-lg <?= $style ?> shrink-0">
                        <i data-lucide="<?= $icon ?>" class="w-4 h-4"></i>
                    </span>
                    <span class="text-sm font-medium text-slate-700 group-hover:text-church-800"><?= $label ?></span>
                    <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 ml-auto group-hover:text-church-400 transition"></i>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js failed to load');
        return;
    }

    const fmtKes = (v) => 'KES ' + Number(v).toLocaleString('en-KE', { maximumFractionDigits: 0 });
    const gridColor = 'rgba(148, 163, 184, 0.15)';
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0b486d',
                padding: 10,
                cornerRadius: 8,
                titleFont: { size: 12, weight: '600' },
                bodyFont: { size: 12 },
            },
        },
    };

    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.color = '#64748b';

    const yearLabels = <?= $chartJson($financeYearLabels) ?>;
    if (yearLabels.length) {
        new Chart(document.getElementById('chartFinanceYear'), {
            type: 'bar',
            data: {
                labels: yearLabels,
                datasets: [
                    {
                        label: 'Collections',
                        data: <?= $chartJson($financeYearCollections) ?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.75)',
                        borderRadius: 6,
                        order: 2,
                    },
                    {
                        label: 'Expenses',
                        data: <?= $chartJson($financeYearExpenses) ?>,
                        type: 'line',
                        borderColor: '#fb7185',
                        backgroundColor: 'rgba(251, 113, 133, 0.1)',
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: '#fb7185',
                        tension: 0.35,
                        fill: true,
                        order: 1,
                    },
                ],
            },
            options: {
                ...defaultOptions,
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        grid: { color: gridColor },
                        ticks: { callback: (v) => fmtKes(v) },
                    },
                },
                plugins: {
                    ...defaultOptions.plugins,
                    legend: { display: true, position: 'top', align: 'end', labels: { boxWidth: 10, usePointStyle: true } },
                    tooltip: {
                        ...defaultOptions.plugins.tooltip,
                        callbacks: { label: (ctx) => ctx.dataset.label + ': ' + fmtKes(ctx.parsed.y) },
                    },
                },
            },
        });
    } else {
        document.getElementById('chartFinanceYear').parentElement.innerHTML = '<p class="text-sm text-slate-400 flex items-center justify-center h-full">No financial data for <?= date('Y') ?> yet.</p>';
    }

    const methodLabels = <?= $chartJson(array_column($collectionMethods, 0)) ?>;
    const methodData = <?= $chartJson(array_column($collectionMethods, 1)) ?>;
    if (methodData.length) {
        new Chart(document.getElementById('chartCollections'), {
            type: 'doughnut',
            data: {
                labels: methodLabels,
                datasets: [{
                    data: methodData,
                    backgroundColor: ['#2da0d9', '#8b5cf6', '#10b981'],
                    borderWidth: 0,
                    hoverOffset: 6,
                }],
            },
            options: {
                ...defaultOptions,
                cutout: '65%',
                plugins: {
                    ...defaultOptions.plugins,
                    legend: { display: true, position: 'bottom', labels: { boxWidth: 10, padding: 12 } },
                    tooltip: {
                        ...defaultOptions.plugins.tooltip,
                        callbacks: { label: (ctx) => ctx.label + ': ' + fmtKes(ctx.parsed) },
                    },
                },
            },
        });
    } else {
        document.getElementById('chartCollections').parentElement.innerHTML = '<p class="text-sm text-slate-400">No collections recorded this month.</p>';
    }

    const weekLabels = <?= $chartJson($weekLabels) ?>;
    if (weekLabels.length) {
        new Chart(document.getElementById('chartWeeklyFlow'), {
            type: 'bar',
            data: {
                labels: weekLabels,
                datasets: [
                    {
                        label: 'Collections',
                        data: <?= $chartJson($weekCollections) ?>,
                        backgroundColor: 'rgba(45, 160, 217, 0.8)',
                        borderRadius: 4,
                    },
                    {
                        label: 'Expenses',
                        data: <?= $chartJson($weekExpenses) ?>,
                        backgroundColor: 'rgba(251, 113, 133, 0.7)',
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                ...defaultOptions,
                scales: {
                    x: { grid: { display: false } },
                    y: { grid: { color: gridColor }, ticks: { callback: (v) => fmtKes(v) } },
                },
                plugins: {
                    ...defaultOptions.plugins,
                    legend: { display: true, position: 'top', align: 'end', labels: { boxWidth: 10 } },
                    tooltip: {
                        ...defaultOptions.plugins.tooltip,
                        callbacks: { label: (ctx) => ctx.dataset.label + ': ' + fmtKes(ctx.parsed.y) },
                    },
                },
            },
        });
    } else {
        document.getElementById('chartWeeklyFlow').parentElement.innerHTML = '<p class="text-sm text-slate-400 flex items-center justify-center h-full">No weekly data for this month yet.</p>';
    }

    const expLabels = <?= $chartJson($expenseLabels) ?>;
    if (expLabels.length) {
        new Chart(document.getElementById('chartExpenses'), {
            type: 'bar',
            data: {
                labels: expLabels,
                datasets: [{
                    data: <?= $chartJson($expenseAmounts) ?>,
                    backgroundColor: [
                        'rgba(11, 72, 109, 0.85)',
                        'rgba(45, 160, 217, 0.85)',
                        'rgba(139, 92, 246, 0.85)',
                        'rgba(245, 158, 11, 0.85)',
                        'rgba(16, 185, 129, 0.85)',
                        'rgba(251, 113, 133, 0.85)',
                    ],
                    borderRadius: 6,
                }],
            },
            options: {
                indexAxis: 'y',
                ...defaultOptions,
                scales: {
                    x: { grid: { color: gridColor }, ticks: { callback: (v) => fmtKes(v) } },
                    y: { grid: { display: false } },
                },
                plugins: {
                    ...defaultOptions.plugins,
                    tooltip: {
                        ...defaultOptions.plugins.tooltip,
                        callbacks: { label: (ctx) => fmtKes(ctx.parsed.x) },
                    },
                },
            },
        });
    } else {
        document.getElementById('chartExpenses').parentElement.innerHTML = '<p class="text-sm text-slate-400 flex items-center justify-center h-full">No expenses recorded this month.</p>';
    }

    new Chart(document.getElementById('chartMembersTrend'), {
        type: 'line',
        data: {
            labels: <?= $chartJson($charts['membersTrend']['labels'] ?? []) ?>,
            datasets: [{
                label: 'Registrations',
                data: <?= $chartJson($charts['membersTrend']['counts'] ?? []) ?>,
                borderColor: '#2da0d9',
                backgroundColor: 'rgba(45, 160, 217, 0.12)',
                borderWidth: 2.5,
                pointRadius: 5,
                pointBackgroundColor: '#2da0d9',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                tension: 0.4,
                fill: true,
            }],
        },
        options: {
            ...defaultOptions,
            scales: {
                x: { grid: { display: false } },
                y: { grid: { color: gridColor }, beginAtZero: true, ticks: { stepSize: 1 } },
            },
            plugins: {
                ...defaultOptions.plugins,
                tooltip: {
                    ...defaultOptions.plugins.tooltip,
                    callbacks: { label: (ctx) => ctx.parsed.y + ' registration' + (ctx.parsed.y !== 1 ? 's' : '') },
                },
            },
        },
    });

    const statusLabels = <?= $chartJson($statusLabels) ?>;
    if (statusLabels.length) {
        new Chart(document.getElementById('chartMembersStatus'), {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: <?= $chartJson($statusCounts) ?>,
                    backgroundColor: <?= $chartJson($statusBg) ?>,
                    borderWidth: 0,
                    hoverOffset: 6,
                }],
            },
            options: {
                ...defaultOptions,
                cutout: '60%',
                plugins: {
                    ...defaultOptions.plugins,
                    legend: { display: true, position: 'bottom', labels: { boxWidth: 10, padding: 10 } },
                },
            },
        });
    } else {
        document.getElementById('chartMembersStatus').parentElement.innerHTML = '<p class="text-sm text-slate-400">No members yet.</p>';
    }

    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>
