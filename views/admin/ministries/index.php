<div class="space-y-4">
    <?php if (!empty($websiteUrl)): ?>
    <p class="text-sm text-slate-500">
        Ministries are managed on the public website.
        <a href="<?= htmlspecialchars($websiteUrl) ?>" target="_blank" rel="noopener" class="text-church-600 hover:underline">Open website</a>
    </p>
    <?php else: ?>
    <p class="text-sm text-slate-500">Ministry list from church site configuration (<code class="text-xs bg-slate-100 px-1 rounded">config/church-site.php</code>).</p>
    <?php endif; ?>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($ministries as $m):
            $title = $m['title'] ?? $m['name'] ?? 'Ministry';
            $slug = $m['slug'] ?? '';
        ?>
        <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-church-500 to-church-700 flex items-center justify-center text-white font-bold text-lg">
                <?= strtoupper(substr($title, 0, 1)) ?>
            </div>
            <h3 class="font-semibold text-lg text-church-800 mt-4"><?= htmlspecialchars($title) ?></h3>
            <?php if (!empty($m['subtitle'])): ?>
            <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($m['subtitle']) ?></p>
            <?php endif; ?>
            <?php if ($slug && !empty($m['website_url'])): ?>
            <a href="<?= htmlspecialchars($m['website_url']) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-sm text-church-600 hover:underline mt-4">
                View on website
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
