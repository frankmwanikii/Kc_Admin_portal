<div class="max-w-lg">
    <form method="POST" action="/admin/communications/send" class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm space-y-5">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Title</label>
            <input type="text" name="title" placeholder="Sunday Service Reminder" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-base focus:ring-2 focus:ring-church-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Channel</label>
            <select name="channel" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-base focus:ring-2 focus:ring-church-500 outline-none">
                <option value="sms">SMS only</option>
                <option value="email">Email only</option>
                <option value="both">SMS + Email</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Message</label>
            <textarea name="message" rows="5" required placeholder="Type your announcement..." class="w-full px-4 py-3 rounded-xl border border-slate-200 text-base focus:ring-2 focus:ring-church-500 outline-none resize-none"></textarea>
        </div>
        <button type="submit" class="w-full py-3.5 rounded-xl bg-church-600 text-white font-semibold hover:bg-church-700 transition">Send to All Members</button>
    </form>
</div>
