<div class="max-w-2xl">
    <form method="POST" action="/admin/attendance" class="space-y-6">
        <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm space-y-5">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Session Title</label>
                <input type="text" name="title" required placeholder="Sunday Morning Service" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-base focus:ring-2 focus:ring-church-500 outline-none">
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Type</label>
                    <select name="type" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-base focus:ring-2 focus:ring-church-500 outline-none">
                        <option value="service">Sunday Service</option>
                        <option value="cell_group">Cell Group</option>
                        <option value="event">Special Event</option>
                        <option value="midweek">Mid-week Service</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Date</label>
                    <input type="date" name="session_date" value="<?= date('Y-m-d') ?>" required class="w-full px-4 py-3 rounded-xl border border-slate-200 text-base focus:ring-2 focus:ring-church-500 outline-none">
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Start Time</label>
                    <input type="time" name="start_time" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-base focus:ring-2 focus:ring-church-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Location</label>
                    <input type="text" name="location" placeholder="Main Sanctuary" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-base focus:ring-2 focus:ring-church-500 outline-none">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="font-semibold text-church-800">Mark Present</h3>
                <p class="text-xs text-slate-400 mt-0.5">Select all members who attended</p>
            </div>
            <div class="max-h-80 overflow-y-auto divide-y divide-slate-50">
                <?php foreach ($members as $m): ?>
                <label class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="present[]" value="<?= $m->id ?>" class="w-4 h-4 rounded border-slate-300 text-church-600 focus:ring-church-500">
                    <span class="text-sm font-medium text-slate-800"><?= htmlspecialchars($m->fullName()) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="w-full sm:w-auto px-8 py-3 rounded-xl bg-church-600 text-white font-semibold hover:bg-church-700 transition">Save Attendance</button>
    </form>
</div>
