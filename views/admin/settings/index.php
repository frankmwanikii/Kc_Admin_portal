<div class="max-w-2xl space-y-6">
    <?php if (!empty($success)): ?>
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm">Settings saved successfully.</div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
    <div class="p-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/admin/settings" enctype="multipart/form-data" class="space-y-6" x-data="{ logoMode: '<?= !empty($logoUrl) ? 'url' : 'upload' ?>' }">
        <!-- Branding / Logo -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="font-semibold text-church-800">Church Logo</h2>
                <p class="text-sm text-slate-500 mt-0.5">Shown in the sidebar, login page, and member portal</p>
            </div>
            <div class="p-6 space-y-5">
                <?php if (!empty($currentLogo)): ?>
                <div class="flex items-center gap-4 p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <img src="<?= htmlspecialchars($currentLogo) ?>" alt="Current logo" class="w-16 h-16 rounded-xl object-contain bg-white border border-slate-200 p-1">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-700">Current logo</p>
                        <p class="text-xs text-slate-400 truncate mt-0.5"><?= htmlspecialchars($currentLogo) ?></p>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-red-600 cursor-pointer shrink-0">
                        <input type="checkbox" name="remove_logo" value="1" class="rounded text-red-600">
                        Remove
                    </label>
                </div>
                <?php endif; ?>

                <div class="flex gap-2 p-1 bg-slate-100 rounded-xl w-fit">
                    <button type="button" @click="logoMode='upload'" :class="logoMode==='upload' ? 'bg-white shadow text-church-700' : 'text-slate-500'" class="px-4 py-2 rounded-lg text-sm font-medium transition">Upload file</button>
                    <button type="button" @click="logoMode='url'" :class="logoMode==='url' ? 'bg-white shadow text-church-700' : 'text-slate-500'" class="px-4 py-2 rounded-lg text-sm font-medium transition">Image URL</button>
                </div>

                <div x-show="logoMode==='upload'" x-cloak>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Upload logo</label>
                    <div class="relative">
                        <input type="file" :name="logoMode === 'upload' ? 'church_logo' : null" accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml"
                            class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-church-50 file:text-church-700 file:font-medium hover:file:bg-church-100 file:cursor-pointer">
                    </div>
                    <p class="text-xs text-slate-400 mt-1.5">JPG, PNG, WebP, GIF or SVG. Max 2 MB. Upload overrides URL.</p>
                </div>

                <div x-show="logoMode==='url'" x-cloak>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Logo URL</label>
                    <input type="url" :name="logoMode === 'url' ? 'church_logo_url' : null" value="<?= htmlspecialchars($logoUrl ?? '') ?>"
                        placeholder="https://yoursite.com/logo.png"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 outline-none text-base">
                    <p class="text-xs text-slate-400 mt-1.5">Direct link to your logo image online</p>
                </div>
            </div>
        </div>

        <!-- Church details -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="font-semibold text-church-800">Church Details</h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Church Name</label>
                    <input type="text" name="church_name" value="<?= htmlspecialchars($churchName ?? '') ?>" required
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 outline-none text-base">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Address</label>
                    <input type="text" name="church_address" value="<?= htmlspecialchars($churchAddress ?? '') ?>"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 outline-none text-base">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone</label>
                    <input type="text" name="church_phone" value="<?= htmlspecialchars($churchPhone ?? '') ?>"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 outline-none text-base">
                </div>
            </div>
        </div>

        <button type="submit" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-church-600 text-white font-semibold hover:bg-church-700 transition shadow-lg shadow-church-600/20">
            Save Settings
        </button>
    </form>
</div>
