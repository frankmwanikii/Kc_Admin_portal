<?php
$d = array_merge($defaults ?? [], $data ?? []);
?>
<div class="min-h-full flex items-center justify-center p-4 py-10">
    <div class="w-full max-w-2xl">
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur flex items-center justify-center text-white text-2xl font-bold mx-auto ring-1 ring-white/20">✝</div>
            <h1 class="mt-5 text-2xl sm:text-3xl font-bold text-white">Church MIS Setup</h1>
            <p class="text-white/60 mt-2 text-sm sm:text-base max-w-md mx-auto">Connect your local MySQL database and create your admin account. Settings are saved automatically to <code class="text-church-300">.env</code>.</p>
        </div>

        <?php if (!empty($error)): ?>
        <div class="mb-6 p-4 rounded-xl bg-red-500/20 border border-red-400/30 text-red-100 text-sm">
            <strong class="font-semibold">Setup failed:</strong> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="/setup" class="space-y-6">
            <!-- Database -->
            <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-church-50 text-church-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                    </div>
                    <div>
                        <h2 class="font-semibold text-church-800">MySQL Database</h2>
                        <p class="text-xs text-slate-500">Uses your locally installed MySQL server</p>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Host</label>
                        <input type="text" name="db_host" value="<?= htmlspecialchars($d['db_host'] ?? '127.0.0.1') ?>" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 outline-none text-base">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Port</label>
                        <input type="text" name="db_port" value="<?= htmlspecialchars($d['db_port'] ?? '3306') ?>" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 outline-none text-base">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Database Name</label>
                        <input type="text" name="db_name" value="<?= htmlspecialchars($d['db_name'] ?? 'church_mis') ?>" required placeholder="church_mis" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 outline-none text-base">
                        <p class="text-xs text-slate-400 mt-1">Created automatically if it doesn't exist</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Username</label>
                        <input type="text" name="db_username" value="<?= htmlspecialchars($d['db_username'] ?? 'root') ?>" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 outline-none text-base">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                        <input type="password" name="db_password" value="<?= htmlspecialchars($d['db_password'] ?? '') ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 outline-none text-base" placeholder="Leave empty if none">
                    </div>
                </div>
            </div>

            <!-- Shared forms database (website) -->
            <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                    </div>
                    <div>
                        <h2 class="font-semibold text-church-800">Website Forms Database</h2>
                        <p class="text-xs text-slate-500">Shared with Kc_website — Connect With Us submissions appear in admin Members</p>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Forms DB host</label>
                        <input type="text" name="forms_db_host" value="<?= htmlspecialchars($d['forms_db_host'] ?? $d['db_host'] ?? '127.0.0.1') ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 outline-none text-base">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Forms DB port</label>
                        <input type="text" name="forms_db_port" value="<?= htmlspecialchars($d['forms_db_port'] ?? $d['db_port'] ?? '3306') ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 outline-none text-base">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Forms database name</label>
                        <input type="text" name="forms_db_name" value="<?= htmlspecialchars($d['forms_db_name'] ?? 'kingdomcity_forms') ?>" placeholder="kingdomcity_forms" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 outline-none text-base">
                        <p class="text-xs text-slate-400 mt-1">Must match <code class="text-church-600">Kc_website/includes/database-config.php</code></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Forms DB username</label>
                        <input type="text" name="forms_db_username" value="<?= htmlspecialchars($d['forms_db_username'] ?? $d['db_username'] ?? '') ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 outline-none text-base">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Forms DB password</label>
                        <input type="password" name="forms_db_password" value="<?= htmlspecialchars($d['forms_db_password'] ?? '') ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 outline-none text-base" placeholder="Leave empty if same as main DB">
                    </div>
                </div>
            </div>

            <!-- Church & Admin -->
            <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="font-semibold text-church-800">Church & Admin Account</h2>
                        <p class="text-xs text-slate-500">Your primary administrator login</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Church Name</label>
                        <input type="text" name="church_name" value="<?= htmlspecialchars($d['church_name'] ?? '') ?>" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 outline-none text-base">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Application URL</label>
                        <input type="url" name="app_url" value="<?= htmlspecialchars($d['app_url'] ?? '') ?>" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 outline-none text-base">
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Admin Email</label>
                            <input type="email" name="admin_email" value="<?= htmlspecialchars($d['admin_email'] ?? '') ?>" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 outline-none text-base">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Admin Password</label>
                            <input type="password" name="admin_password" required minlength="8" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 outline-none text-base" placeholder="Min. 8 characters">
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full py-4 rounded-2xl bg-gradient-to-r from-church-600 to-church-700 text-white font-semibold text-lg shadow-xl shadow-church-900/30 hover:from-church-500 hover:to-church-600 transition">
                Install & Launch System
            </button>
        </form>

        <p class="text-center text-white/40 text-xs mt-6">Requires PHP MySQL PDO extension and a running MySQL service</p>
    </div>
</div>
