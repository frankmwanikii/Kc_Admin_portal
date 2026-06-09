<div class="min-h-screen flex items-center justify-center p-4">
    <div class="text-center max-w-md">
        <p class="text-6xl font-bold text-red-200">403</p>
        <h1 class="text-xl font-semibold text-church-800 mt-2">Access denied</h1>
        <p class="text-slate-500 mt-2 text-sm"><?= htmlspecialchars($message ?? 'You do not have permission to view this page.') ?></p>
        <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
            <?php if (!empty($showAdminLink)): ?>
            <a href="/admin" class="inline-block px-5 py-2.5 rounded-xl bg-church-600 text-white text-sm font-medium hover:bg-church-700 transition">Go to Admin Dashboard</a>
            <?php endif; ?>
            <a href="/login" class="inline-block px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition">Sign in</a>
            <a href="/" class="inline-block px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition">Home</a>
        </div>
    </div>
</div>
