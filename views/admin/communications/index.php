<div class="space-y-6">
    <div class="flex flex-wrap gap-2">
        <a href="/admin/communications/create" class="px-5 py-2.5 rounded-xl bg-church-600 text-white text-sm font-medium hover:bg-church-700 transition">Send Message</a>
        <a href="/admin/communications/birthdays" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition">Today's Birthdays</a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-church-800">Message History</h3>
        </div>
        <?php if (empty($communications)): ?>
        <p class="p-8 text-center text-slate-500 text-sm">No messages sent yet.</p>
        <?php else: ?>
        <div class="divide-y divide-slate-50">
            <?php foreach ($communications as $c): ?>
            <div class="px-5 py-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-medium text-slate-800"><?= htmlspecialchars($c['title']) ?></p>
                        <p class="text-sm text-slate-500 mt-1 line-clamp-2"><?= htmlspecialchars($c['message']) ?></p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-church-50 text-church-700"><?= strtoupper($c['channel']) ?></span>
                        <p class="text-xs text-slate-400 mt-1"><?= $c['sent_count'] ?> sent</p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
