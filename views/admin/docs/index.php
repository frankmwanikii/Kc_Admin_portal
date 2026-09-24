<?php
/** @var string $section */
$section = in_array(($section ?? ''), ['dashboard', 'finance'], true) ? $section : 'dashboard';
?>
<link rel="stylesheet" href="/css/admin-hub.css">
<link rel="stylesheet" href="/css/admin-docs.css">

<div class="docs-layout"
     x-data="{ section: '<?= htmlspecialchars($section, ENT_QUOTES) ?>' }"
     x-init="$watch('section', (v) => {
         const url = new URL(window.location.href);
         url.searchParams.set('section', v);
         history.replaceState({}, '', url);
         $nextTick(() => window.lucide?.createIcons());
     })">

    <div class="docs-hero">
        <p class="docs-hero__eyebrow">Help</p>
        <h2>How to use the admin</h2>
        <p>Practical guides for the dashboard and finance section — written for day-to-day church office work.</p>
    </div>

    <div class="docs-nav-wrap">
        <div class="docs-nav" role="tablist" aria-label="Documentation sections">
            <button type="button"
                    role="tab"
                    class="docs-nav-btn"
                    :class="section === 'dashboard' && 'docs-nav-btn--active'"
                    :aria-selected="section === 'dashboard'"
                    @click="section = 'dashboard'">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                Admin dashboard
            </button>
            <button type="button"
                    role="tab"
                    class="docs-nav-btn"
                    :class="section === 'finance' && 'docs-nav-btn--active'"
                    :aria-selected="section === 'finance'"
                    @click="section = 'finance'">
                <i data-lucide="wallet" class="w-4 h-4"></i>
                Finance
            </button>
        </div>
    </div>

    <!-- ── Admin dashboard ─────────────────────────────────────────── -->
    <div x-show="section === 'dashboard'" x-cloak class="docs-panel" role="tabpanel">
        <h2 class="docs-section-title">Admin dashboard</h2>
        <p class="docs-section-lead">
            The dashboard is your home screen after login. It summarises members, collections, bills, and recent activity so you can see what needs attention without opening every section.
        </p>

        <div class="docs-block">
            <h3>Signing in</h3>
            <ol class="docs-steps">
                <li>Open the portal and go to <strong>Login</strong>.</li>
                <li>Sign in with an <strong>admin</strong> email and password (the account created at setup, or one added under Staff).</li>
                <li>You land on <strong>Dashboard</strong> at <code>/admin</code>.</li>
            </ol>
        </div>

        <div class="docs-block">
            <h3>Finding your way around</h3>
            <p>The left sidebar groups everything into four areas:</p>
            <ul class="docs-list">
                <li><strong>Overview</strong> — Dashboard</li>
                <li><strong>Management</strong> — Members, Staff, Inventory, Communications</li>
                <li><strong>Finance</strong> — Overview, Sundays, Bills, Budget, Reports</li>
                <li><strong>System</strong> — Settings and Docs (this page)</li>
            </ul>
            <p>On phones, open the menu with the top-left button. On desktop you can collapse the sidebar to icons only.</p>
            <div class="docs-callout">
                <i data-lucide="info"></i>
                <span>A number badge on <strong>Members</strong> means new website submissions are waiting for review.</span>
            </div>
        </div>

        <div class="docs-block">
            <h3>What the dashboard shows</h3>
            <div class="docs-grid">
                <div class="docs-card">
                    <h4>Total members</h4>
                    <p>Everyone registered in the system. Click to open Members.</p>
                </div>
                <div class="docs-card">
                    <h4>Pending review</h4>
                    <p>New Connect With Us submissions that still need a decision.</p>
                </div>
                <div class="docs-card">
                    <h4>Collections</h4>
                    <p>Sunday giving recorded for the current calendar month (KES).</p>
                </div>
                <div class="docs-card">
                    <h4>Bills outstanding</h4>
                    <p>Total still owed on unpaid bills for this year.</p>
                </div>
            </div>
            <p style="margin-top:0.9rem">Below the cards you’ll find:</p>
            <ul class="docs-list">
                <li><strong>Financial charts</strong> — year trend, payment methods, weekly cash flow, and expense categories from live finance data.</li>
                <li><strong>Members charts</strong> — registrations over the last 6 months and status breakdown (new / reviewed / approved / rejected).</li>
                <li><strong>Recent registrations</strong> — latest submissions; click a row to open that person.</li>
                <li><strong>Quick actions</strong> — shortcuts to outstanding bills, Record Sunday, operating statement, inventory, communications, and settings.</li>
            </ul>
        </div>

        <div class="docs-block">
            <h3>Everyday admin tasks</h3>
            <ol class="docs-steps">
                <li><strong>Review new members</strong> — open Members, filter by status <em>New</em>, open a person, then mark reviewed / approved / archived.</li>
                <li><strong>Add someone manually</strong> — on Members, use <em>Add member</em> and fill the form (same fields as website Connect forms).</li>
                <li><strong>Manage staff logins</strong> — Staff lets you add or remove people who can access this admin area.</li>
                <li><strong>Track inventory</strong> — Inventory stores church assets and counts.</li>
                <li><strong>Send messages</strong> — Communications for bulk SMS/email; Birthdays lists upcoming dates.</li>
                <li><strong>Update church details</strong> — Settings for logo, address, SMS provider, and the website forms database.</li>
            </ol>
            <div class="docs-link-row">
                <a href="/admin" class="docs-link"><i data-lucide="layout-dashboard"></i> Open dashboard</a>
                <a href="/admin/members" class="docs-link"><i data-lucide="users"></i> Members</a>
                <a href="/admin/settings" class="docs-link"><i data-lucide="settings"></i> Settings</a>
            </div>
        </div>
    </div>

    <!-- ── Finance ─────────────────────────────────────────────────── -->
    <div x-show="section === 'finance'" x-cloak class="docs-panel" role="tabpanel">
        <h2 class="docs-section-title">Finance</h2>
        <p class="docs-section-lead">
            Finance is where you record Sunday collections and expenses, track bills the church owes, set a monthly budget, and export statements. Everything links automatically — record giving once, and Overview, Sundays, Budget, and Reports stay in sync.
        </p>

        <div class="docs-block">
            <h3>Finance menu at a glance</h3>
            <div class="docs-grid">
                <div class="docs-card">
                    <h4>Overview</h4>
                    <p>Year KPIs, charts, true net position, this month’s Sundays, and budget snapshot.</p>
                </div>
                <div class="docs-card">
                    <h4>Sundays</h4>
                    <p>Week-by-week collections and expenses for each Sunday service.</p>
                </div>
                <div class="docs-card">
                    <h4>Bills</h4>
                    <p>Amounts the church owes — paid, due, and balance still owing.</p>
                </div>
                <div class="docs-card">
                    <h4>Budget</h4>
                    <p>Plan income and expenses for the month, then compare against actuals.</p>
                </div>
                <div class="docs-card">
                    <h4>Reports</h4>
                    <p>Operating statement and year-over-year income &amp; expenditure (print / PDF / CSV).</p>
                </div>
            </div>
        </div>

        <div class="docs-block">
            <h3>Record a Sunday (most common task)</h3>
            <p>Use this after each service to enter giving and cash spending.</p>
            <ol class="docs-steps">
                <li>Go to <strong>Finance → Overview</strong> or <strong>Sundays</strong>, then click <strong>Record</strong> (or use Quick actions → Record Sunday on the dashboard).</li>
                <li>Pick the <strong>month</strong>, then choose the <strong>Sunday date</strong> chip for that service.</li>
                <li>Enter <strong>collections</strong> by method — Cash, Paybill, and Cheque. Leave a method blank if unused.</li>
                <li>Enter <strong>expenses</strong> by category (Administration, Ministry, Finance, etc.). You can save collections only, expenses only, or both.</li>
                <li>Save. Totals update on Overview, Sundays, Budget vs actual, and Reports.</li>
            </ol>
            <div class="docs-callout docs-callout--tip">
                <i data-lucide="lightbulb"></i>
                <span>From Overview or Sundays you can also open a Sunday row and click <strong>Edit</strong> to change amounts already saved.</span>
            </div>
            <div class="docs-link-row">
                <a href="/admin/finance/sunday?return_tab=ledger" class="docs-link"><i data-lucide="calendar-days"></i> Record Sunday</a>
                <a href="/admin/finance?tab=ledger" class="docs-link"><i data-lucide="list"></i> Sundays list</a>
            </div>
        </div>

        <div class="docs-block">
            <h3>Overview</h3>
            <p>Use Overview for a year-level health check:</p>
            <ul class="docs-list">
                <li><strong>Collections / Expenses / Operating balance</strong> — totals for the selected year.</li>
                <li><strong>Outstanding bills</strong> — unpaid balances from the Bills tab.</li>
                <li><strong>True net position</strong> — operating balance minus outstanding bills.</li>
                <li><strong>Charts</strong> — collections vs expenses vs budget, expense mix, and payment methods.</li>
                <li><strong>This month</strong> — change the month picker to see each Sunday’s in / out / balance.</li>
            </ul>
            <div class="docs-callout">
                <i data-lucide="triangle-alert"></i>
                <span>If a yellow warning appears, some Sunday amounts were excluded from totals (for example incomplete or inconsistent rows). Open the affected Sunday and fix the entry.</span>
            </div>
        </div>

        <div class="docs-block">
            <h3>Bills (what the church owes)</h3>
            <ol class="docs-steps">
                <li>Open <strong>Finance → Bills</strong>.</li>
                <li>Click <strong>+ New Bill</strong>, choose a category and expense item, set month incurred, amount due, and any amount already paid.</li>
                <li>Search or filter by month/year to find a bill later.</li>
                <li>Record further payments on a bill until the balance owing is zero (status updates automatically).</li>
            </ol>
            <p>Bills do <em>not</em> replace Sunday expense recording — use Sundays for cash spent from offerings, and Bills for invoices/payables still outstanding.</p>
        </div>

        <div class="docs-block">
            <h3>Budget</h3>
            <ol class="docs-steps">
                <li>Open <strong>Finance → Budget</strong> and choose the financial year and focus month.</li>
                <li>Click <strong>Set Budget</strong> (edit mode) and enter planned income and expense lines for that month.</li>
                <li>Save, then return to Budget vs actual to see how Sunday spending compares to the plan (on track / over / under).</li>
            </ol>
            <div class="docs-callout docs-callout--tip">
                <i data-lucide="lightbulb"></i>
                <span>Budget lines drive the “budget” series on Overview charts and the monthly on-track meter. Actuals come from Sunday records.</span>
            </div>
        </div>

        <div class="docs-block">
            <h3>Reports</h3>
            <ul class="docs-list">
                <li><strong>Operating statement</strong> — collections and spending for a week, month, or year. Switch view with the report tools, then Print, download PDF, or CSV.</li>
                <li><strong>Position</strong> — year-over-year consolidated income and expenditure for leadership review.</li>
            </ul>
            <div class="docs-link-row">
                <a href="/admin/finance?tab=dashboard" class="docs-link"><i data-lucide="pie-chart"></i> Finance overview</a>
                <a href="/admin/finance?tab=bills" class="docs-link"><i data-lucide="receipt"></i> Bills</a>
                <a href="/admin/finance?tab=budget" class="docs-link"><i data-lucide="wallet"></i> Budget</a>
                <a href="/admin/finance?tab=reports&amp;sub=statement" class="docs-link"><i data-lucide="file-bar-chart"></i> Operating statement</a>
            </div>
        </div>

        <div class="docs-block">
            <h3>Suggested weekly rhythm</h3>
            <ol class="docs-steps">
                <li><strong>Sunday / Monday</strong> — Record that week’s collections and expenses.</li>
                <li><strong>During the week</strong> — Add or update any new Bills as invoices arrive; log payments when settled.</li>
                <li><strong>Month end</strong> — Check Budget vs actual, then export the monthly operating statement for elders or finance committee.</li>
            </ol>
        </div>
    </div>
</div>
