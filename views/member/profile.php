<div class="space-y-6 max-w-2xl">
    <div>
        <h2 class="text-xl font-bold text-church-800">My Profile</h2>
        <p class="text-sm text-slate-500 mt-0.5">Your personal and household information</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="p-6 flex items-center gap-4 border-b border-slate-100">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-church-500 to-church-700 flex items-center justify-center text-white text-2xl font-bold">
                <?= strtoupper(substr($member->first_name, 0, 1) . substr($member->last_name, 0, 1)) ?>
            </div>
            <div>
                <h3 class="text-xl font-bold text-slate-800"><?= htmlspecialchars($member->fullName()) ?></h3>
                <p class="text-sm text-slate-500"><?= htmlspecialchars($member->email ?? '') ?></p>
            </div>
        </div>
        <dl class="divide-y divide-slate-50">
            <?php
            $fields = [
                'Phone' => $member->phone,
                'Gender' => ucfirst($member->gender ?? ''),
                'Date of Birth' => $member->date_of_birth ? date('F j, Y', strtotime($member->date_of_birth)) : null,
                'Marital Status' => ucfirst($member->marital_status ?? ''),
                'Occupation' => $member->occupation,
                'Joined' => $member->joined_date ? date('F j, Y', strtotime($member->joined_date)) : null,
                'Status' => ucfirst($member->membership_status),
            ];
            foreach ($fields as $label => $value):
                if (!$value) continue;
            ?>
            <div class="px-6 py-3.5 flex justify-between gap-4">
                <dt class="text-sm text-slate-500"><?= $label ?></dt>
                <dd class="text-sm font-medium text-slate-800 text-right"><?= htmlspecialchars($value) ?></dd>
            </div>
            <?php endforeach; ?>
        </dl>
    </div>

    <?php if ($household && !empty($family)): ?>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-church-800"><?= htmlspecialchars($household->name) ?> Household</h3>
            <?php if ($household->address): ?>
            <p class="text-sm text-slate-500 mt-0.5"><?= htmlspecialchars($household->address) ?><?= $household->city ? ', ' . htmlspecialchars($household->city) : '' ?></p>
            <?php endif; ?>
        </div>
        <div class="divide-y divide-slate-50">
            <?php foreach ($family as $fm): ?>
            <div class="px-6 py-3.5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center text-sm font-medium text-slate-600">
                        <?= strtoupper(substr($fm->first_name, 0, 1)) ?>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($fm->fullName()) ?></p>
                        <?php if ($fm->is_head_of_household): ?>
                        <p class="text-xs text-church-600">Head of household</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($fm->id === $member->id): ?>
                <span class="text-xs font-medium text-church-600 bg-church-50 px-2 py-0.5 rounded-full">You</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
