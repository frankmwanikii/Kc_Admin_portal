<?php
$d = $data ?? [];
$initial = [
    'first_name' => $d['first_name'] ?? '',
    'last_name' => $d['last_name'] ?? '',
    'email' => $d['email'] ?? '',
    'phone' => $d['phone'] ?? '',
    'gender' => $d['gender'] ?? '',
    'gender_other' => $d['gender_other'] ?? '',
    'date_of_birth' => $d['date_of_birth'] ?? '',
    'marital_status' => $d['marital_status'] ?? '',
    'spouse_name' => $d['spouse_name'] ?? '',
    'residence' => $d['residence'] ?? '',
    'city' => $d['city'] ?? '',
    'county' => $d['county'] ?? '',
    'household_name' => $d['household_name'] ?? '',
    'is_head' => !empty($d['is_head']),
    'baptized' => !empty($d['baptized']),
    'wish_to_be_baptized' => !empty($d['wish_to_be_baptized']),
    'baptism_date' => $d['baptism_date'] ?? '',
    'previous_church' => $d['previous_church'] ?? '',
    'how_heard_about_us' => $d['how_heard_about_us'] ?? '',
    'occupation' => $d['occupation'] ?? '',
    'employer' => $d['employer'] ?? '',
    'skills_talents' => $d['skills_talents'] ?? '',
    'emergency_contact_name' => $d['emergency_contact_name'] ?? '',
    'emergency_contact_phone' => $d['emergency_contact_phone'] ?? '',
    'member_notes' => $d['member_notes'] ?? '',
];
$childrenInitial = !empty($d['children']) ? $d['children'] : [];
$ministryInitial = !empty($d['ministry_interests'])
    ? array_map('trim', explode(',', (string) $d['ministry_interests']))
    : [];
$knownGenders = ['male', 'female', 'other'];
if (!empty($initial['gender']) && !in_array($initial['gender'], $knownGenders, true)) {
    $initial['gender_other'] = $initial['gender'];
    $initial['gender'] = 'other';
}
?>
<style>
    @keyframes kc-q-in {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .kc-q-in { animation: kc-q-in 0.35s cubic-bezier(0.22, 1, 0.36, 1) both; }
    .kc-emoji {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 4.5rem;
        height: 4.5rem;
        border-radius: 1.25rem;
        font-size: 2.25rem;
        line-height: 1;
        background: linear-gradient(135deg, rgba(53,175,230,0.22) 0%, rgba(11,72,109,0.35) 100%);
        border: 1px solid rgba(255,255,255,0.12);
        box-shadow: 0 8px 32px rgba(53,175,230,0.18);
        margin-bottom: 1.25rem;
        animation: kc-emoji-pop 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    }
    @keyframes kc-emoji-pop {
        from { opacity: 0; transform: scale(0.6) rotate(-8deg); }
        to { opacity: 1; transform: scale(1) rotate(0deg); }
    }
    .kc-ms-panel { scrollbar-width: thin; scrollbar-color: rgba(53,175,230,0.5) transparent; }
    html, body { height: auto; min-height: 100%; overflow-y: auto; }
</style>

<div class="min-h-full flex flex-col"
     x-data="onboardingFlow(<?= htmlspecialchars(json_encode($initial, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($childrenInitial, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($ministryInitial, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES) ?>)"
     x-cloak
     @keydown.window="handleKey($event)">

    <div class="fixed top-0 left-0 right-0 z-30 px-4 pt-4 pb-2 bg-gradient-to-b from-[#0b486d]/95 to-transparent">
        <div class="max-w-lg mx-auto">
            <div class="flex items-center justify-between text-xs text-white/60 mb-2">
                <span class="truncate" x-text="current?.section"></span>
                <span><span x-text="qi + 1"></span> / <span x-text="queue.length"></span></span>
            </div>
            <div class="h-1 rounded-full bg-white/15 overflow-hidden">
                <div class="h-full rounded-full bg-[#35afe6] transition-all duration-500 ease-out"
                     :style="`width: ${progress}%`"></div>
            </div>
        </div>
    </div>

    <div class="flex-1 flex flex-col justify-start px-4 pt-16 max-w-lg mx-auto w-full transition-[padding] duration-300"
         :class="ministryOpen ? 'pb-56' : (current?.type === 'review' ? 'pb-36' : 'pb-32')">
        <?php if (!empty($error)): ?>
        <div class="mb-4 p-3 rounded-xl bg-red-500/20 text-red-100 text-sm border border-red-400/30 kc-q-in">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <div x-show="stepError" x-transition class="mb-4 p-3 rounded-xl bg-amber-500/20 text-amber-100 text-sm border border-amber-400/30" x-text="stepError"></div>

        <form method="POST" action="/onboard/<?= htmlspecialchars($token) ?>" id="onboard-form">
            <input type="hidden" name="first_name" :value="form.first_name">
            <input type="hidden" name="last_name" :value="form.last_name">
            <input type="hidden" name="email" :value="form.email">
            <input type="hidden" name="phone" :value="form.phone">
            <input type="hidden" name="gender" :value="genderForSubmit()">
            <input type="hidden" name="date_of_birth" :value="form.date_of_birth">
            <input type="hidden" name="marital_status" :value="form.marital_status">
            <input type="hidden" name="spouse_name" :value="form.spouse_name">
            <input type="hidden" name="residence" :value="form.residence">
            <input type="hidden" name="city" :value="form.city">
            <input type="hidden" name="county" :value="form.county">
            <input type="hidden" name="household_name" :value="form.household_name">
            <input type="hidden" name="baptism_date" :value="form.baptism_date">
            <input type="hidden" name="previous_church" :value="form.previous_church">
            <input type="hidden" name="how_heard_about_us" :value="form.how_heard_about_us">
            <input type="hidden" name="occupation" :value="form.occupation">
            <input type="hidden" name="employer" :value="form.employer">
            <input type="hidden" name="skills_talents" :value="form.skills_talents">
            <input type="hidden" name="emergency_contact_name" :value="form.emergency_contact_name">
            <input type="hidden" name="emergency_contact_phone" :value="form.emergency_contact_phone">
            <input type="hidden" name="member_notes" :value="form.member_notes">
            <template x-for="(child, ci) in children" :key="'hid-'+ci">
                <input type="hidden" :name="'children_name[]'" :value="child.name">
                <input type="hidden" :name="'children_age[]'" :value="child.age">
            </template>
            <input type="checkbox" name="baptized" value="1" x-model="form.baptized" class="sr-only" tabindex="-1">
            <input type="checkbox" name="wish_to_be_baptized" value="1" x-model="form.wish_to_be_baptized" class="sr-only" tabindex="-1">
            <input type="checkbox" name="is_head" value="1" x-model="form.is_head" class="sr-only" tabindex="-1">
            <template x-for="m in ministrySelected" :key="'min-'+m">
                <input type="hidden" name="ministry_interests[]" :value="m">
            </template>

            <div class="kc-q-in" :key="current?.id">
                <div class="kc-emoji" x-text="current?.emoji"></div>
                <h2 class="text-2xl sm:text-3xl font-bold text-white leading-snug mb-2" x-text="current?.question"></h2>
                <p class="text-white/50 text-sm mb-8" x-show="current?.hint" x-text="current?.hint"></p>
                <p class="text-white/40 text-xs mb-8" x-show="!current?.hint && !current?.required">Press Enter to skip</p>
                <p class="text-white/40 text-xs mb-8" x-show="current?.required && current?.type !== 'review'">Required</p>

                <!-- Text -->
                <template x-if="current?.type === 'text' || current?.type === 'email' || current?.type === 'tel'">
                    <input :type="current.type === 'email' ? 'email' : current.type === 'tel' ? 'tel' : 'text'"
                           x-model="form[current.field]"
                           :placeholder="current.placeholder || ''"
                           x-ref="input"
                           class="w-full bg-transparent border-0 border-b-2 border-white/30 text-white text-xl sm:text-2xl py-3 placeholder:text-white/30 focus:border-[#35afe6] focus:ring-0 outline-none transition">
                </template>

                <!-- Date -->
                <template x-if="current?.type === 'date'">
                    <input type="date" x-model="form[current.field]" x-ref="input"
                           class="w-full bg-white/10 rounded-xl px-4 py-4 text-white text-lg border border-white/20 focus:border-[#35afe6] outline-none [color-scheme:dark]">
                </template>

                <!-- Textarea -->
                <template x-if="current?.type === 'textarea'">
                    <textarea x-model="form[current.field]" rows="3" x-ref="input"
                              :placeholder="current.placeholder || ''"
                              class="w-full bg-white/10 rounded-xl px-4 py-4 text-white text-lg border border-white/20 focus:border-[#35afe6] outline-none resize-none placeholder:text-white/30"></textarea>
                </template>

                <!-- Number (child age) -->
                <template x-if="current?.type === 'number'">
                    <input type="number" x-model="children[current.childIndex].age" min="0" max="120"
                           x-ref="input" placeholder="Age"
                           class="w-full bg-transparent border-0 border-b-2 border-white/30 text-white text-xl sm:text-2xl py-3 placeholder:text-white/30 focus:border-[#35afe6] outline-none">
                </template>

                <!-- Select -->
                <template x-if="current?.type === 'select'">
                    <select x-model="form[current.field]" x-ref="input"
                            class="w-full bg-white/10 rounded-xl px-4 py-4 text-white text-lg border border-white/20 focus:border-[#35afe6] outline-none">
                        <option value="" class="text-slate-900">Choose one...</option>
                        <template x-for="opt in current.options" :key="opt.value">
                            <option :value="opt.value" class="text-slate-900" x-text="opt.label"></option>
                        </template>
                    </select>
                </template>

                <!-- Single choice cards -->
                <template x-if="current?.type === 'choice'">
                    <div class="space-y-3">
                        <template x-for="opt in current.options" :key="opt.value">
                            <button type="button"
                                    @click="pickChoice(opt.value)"
                                    class="w-full text-left px-5 py-4 rounded-xl border-2 text-lg font-medium transition-all duration-200"
                                    :class="getChoiceValue() === opt.value
                                        ? 'border-[#35afe6] bg-[#35afe6]/20 text-white scale-[1.02]'
                                        : 'border-white/20 text-white/80 hover:border-[#35afe6]/60 hover:bg-white/5'">
                                <span x-text="opt.label"></span>
                            </button>
                        </template>
                        <div x-show="current?.field === 'gender' && form.gender === 'other'" x-transition
                             class="mt-4 pt-4 border-t border-white/10">
                            <label class="block text-white/60 text-sm mb-2">Please specify how you identify</label>
                            <input type="text" x-model="form.gender_other" x-ref="otherInput"
                                   placeholder="Type here..."
                                   class="w-full bg-transparent border-0 border-b-2 border-white/30 text-white text-xl sm:text-2xl py-3 placeholder:text-white/30 focus:border-[#35afe6] outline-none">
                        </div>
                    </div>
                </template>

                <!-- Yes / No -->
                <template x-if="current?.type === 'yesno'">
                    <div class="grid grid-cols-2 gap-4">
                        <button type="button" @click="pickYesNo(true)"
                            class="py-5 rounded-xl border-2 text-lg font-semibold transition-all"
                            :class="yesNoValue === true ? 'border-[#35afe6] bg-[#35afe6]/20 text-white' : 'border-white/20 text-white/80 hover:border-[#35afe6]/50'">Yes</button>
                        <button type="button" @click="pickYesNo(false)"
                            class="py-5 rounded-xl border-2 text-lg font-semibold transition-all"
                            :class="yesNoValue === false ? 'border-[#35afe6] bg-[#35afe6]/20 text-white' : 'border-white/20 text-white/80 hover:border-[#35afe6]/50'">No</button>
                    </div>
                </template>

                <!-- Ministry collapsed multi-select -->
                <template x-if="current?.type === 'multiselect'">
                    <div @click.outside="ministryOpen = false">
                        <button type="button" @click="toggleMinistryDropdown()"
                            class="w-full flex items-center justify-between gap-3 px-4 py-4 rounded-xl bg-white/10 border text-left transition-all"
                            :class="ministryOpen ? 'border-[#35afe6] ring-2 ring-[#35afe6]/30' : 'border-white/20 hover:border-[#35afe6]/50'">
                            <span class="text-white text-base truncate"
                                  x-text="ministrySelected.length ? `${ministrySelected.length} minist${ministrySelected.length === 1 ? 'ry' : 'ries'} selected` : 'Tap to choose ministries…'"></span>
                            <svg class="w-5 h-5 text-[#35afe6] shrink-0 transition-transform duration-200" :class="ministryOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="ministryOpen" x-ref="ministryPanel" x-transition
                             class="mt-2 rounded-xl bg-[#0a3d5c] border border-[#35afe6]/40 shadow-xl shadow-black/30 overflow-hidden">
                            <template x-for="opt in current.options" :key="opt.value">
                                <button type="button" @click="toggleMinistry(opt.value)"
                                    class="w-full flex items-center justify-between gap-3 px-4 py-3.5 text-left border-b border-white/5 last:border-0 transition-colors hover:bg-white/5"
                                    :class="ministrySelected.includes(opt.value) ? 'bg-[#35afe6]/10' : ''">
                                    <span class="text-white text-base" x-text="opt.label"></span>
                                    <span class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 text-sm font-bold transition-all"
                                          :class="ministrySelected.includes(opt.value) ? 'bg-[#35afe6] text-white' : 'border border-white/25 text-transparent'">✓</span>
                                </button>
                            </template>
                        </div>

                        <div x-show="ministrySelected.length" x-transition class="flex flex-wrap gap-2 mt-4">
                            <template x-for="m in ministrySelected" :key="'chip-'+m">
                                <span class="inline-flex items-center gap-1.5 pl-3 pr-2 py-1.5 rounded-full bg-[#35afe6]/20 border border-[#35afe6]/40 text-white text-sm">
                                    <span x-text="m"></span>
                                    <button type="button" @click="toggleMinistry(m)" aria-label="Remove"
                                        class="w-5 h-5 rounded-full bg-white/10 hover:bg-red-500/40 text-white/80 hover:text-white flex items-center justify-center text-xs leading-none transition">✕</button>
                                </span>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Child name -->
                <template x-if="current?.type === 'child_name'">
                    <input type="text" x-model="children[current.childIndex].name" x-ref="input"
                           placeholder="Child's full name"
                           class="w-full bg-transparent border-0 border-b-2 border-white/30 text-white text-xl sm:text-2xl py-3 placeholder:text-white/30 focus:border-[#35afe6] outline-none">
                </template>

                <!-- Review -->
                <template x-if="current?.type === 'review'">
                    <div class="space-y-3 max-h-[50vh] overflow-y-auto kc-ms-panel pr-1 -mr-1">
                        <template x-for="section in reviewSections" :key="section.title">
                            <div class="p-4 rounded-xl bg-white/10 border border-white/10 backdrop-blur-sm">
                                <p class="text-[#35afe6] text-xs font-bold uppercase tracking-widest mb-3" x-text="section.title"></p>
                                <dl class="space-y-2.5">
                                    <template x-for="row in section.items" :key="row.label">
                                        <div class="flex justify-between gap-4 items-start">
                                            <dt class="text-white/45 text-sm shrink-0" x-text="row.label"></dt>
                                            <dd class="text-white font-medium text-sm text-right leading-snug" x-text="row.value"></dd>
                                        </div>
                                    </template>
                                </dl>
                            </div>
                        </template>
                        <p class="text-white/35 text-xs text-center pt-1 leading-relaxed">
                            Your information is shared with <?= htmlspecialchars($churchName) ?> for pastoral care and church communication.
                        </p>
                    </div>
                </template>
            </div>
        </form>
    </div>

    <div class="fixed bottom-0 left-0 right-0 z-30 px-4 pb-6 pt-4 bg-gradient-to-t from-[#0b486d] to-transparent">
        <div class="max-w-lg mx-auto flex items-center gap-3">
            <button type="button" x-show="qi > 0" @click="back()"
                class="px-4 py-3 rounded-xl text-white/60 hover:text-white text-sm font-medium transition">
                &#8592; Back
            </button>
            <button type="button" x-show="current?.type !== 'review'" @click="forward()"
                class="flex-1 py-4 rounded-xl bg-[#35afe6] hover:bg-[#2da0d9] text-white font-bold text-lg shadow-lg shadow-[#35afe6]/30 transition-all active:scale-[0.98]">
                OK &#8629;
            </button>
            <button type="submit" form="onboard-form" x-show="current?.type === 'review'"
                class="flex-1 py-4 rounded-xl bg-gradient-to-r from-[#35afe6] to-[#0b486d] text-white font-bold text-lg shadow-lg transition-all active:scale-[0.98]">
                🎊 Join the family
            </button>
        </div>
    </div>
</div>

<script>
function onboardingFlow(saved, childrenSaved, ministrySaved) {
    return {
        qi: 0,
        stepError: '',
        form: saved,
        children: childrenSaved.length ? childrenSaved : [],
        ministrySelected: ministrySaved || [],
        hasChildren: childrenSaved.length > 0 ? true : null,
        baptizedNo: false,
        ministryOpen: false,
        yesNoValue: null,
        maritalOptions: [
            { value: 'single', label: 'Single' },
            { value: 'married', label: 'Married' },
            { value: 'divorced', label: 'Divorced' },
            { value: 'prefer_not_to_say', label: 'Prefer not to say' },
        ],
        ministryOptions: [
            { value: 'Worship', label: 'Worship' },
            { value: 'Youth', label: 'Youth' },
            { value: 'Children', label: 'Children' },
            { value: 'Outreach', label: 'Outreach' },
            { value: 'Media', label: 'Media & tech' },
            { value: 'Hospitality', label: 'Hospitality' },
            { value: 'Prayer', label: 'Prayer' },
            { value: 'Ushering', label: 'Ushering' },
        ],
        heardOptions: [
            { value: 'friend_or_family', label: 'Friend or family' },
            { value: 'social_media', label: 'Social media' },
            { value: 'website', label: 'Website / online' },
            { value: 'church_event', label: 'Church event' },
            { value: 'walk_in', label: 'Walked in' },
            { value: 'other', label: 'Other' },
        ],

        get queue() {
            const q = [];
            const add = (item) => q.push(item);

            add({ id: 'first_name', section: 'Getting started', emoji: '🌟', question: "What's your first name?", type: 'text', field: 'first_name', required: true, placeholder: 'Type your first name...' });
            add({ id: 'last_name', section: 'Getting started', emoji: '💫', question: "And your last name?", type: 'text', field: 'last_name', required: true });
            add({ id: 'gender', section: 'About you', emoji: '🧑', question: 'How do you identify?', hint: 'Optional — tap a choice or press OK to skip', type: 'choice', field: 'gender', options: [{ value: 'male', label: 'Male' }, { value: 'female', label: 'Female' }, { value: 'other', label: 'Other' }] });
            add({ id: 'date_of_birth', section: 'About you', emoji: '🎂', question: "When's your birthday?", hint: 'Optional', type: 'date', field: 'date_of_birth' });
            add({ id: 'marital_status', section: 'About you', emoji: '💞', question: "What's your marital status?", type: 'choice', field: 'marital_status', required: true, options: this.maritalOptions });

            if (this.form.marital_status === 'married') {
                add({ id: 'spouse_name', section: 'Family', emoji: '💕', question: "What's your spouse's full name?", hint: 'Optional', type: 'text', field: 'spouse_name' });
            }

            add({ id: 'email', section: 'Stay in touch', emoji: '💌', question: "What's your email address?", type: 'email', field: 'email', required: true, placeholder: 'you@email.com' });
            add({ id: 'phone', section: 'Stay in touch', emoji: '📲', question: "What's your phone number?", type: 'tel', field: 'phone', required: true, placeholder: '+254 7XX XXX XXX' });
            add({ id: 'residence', section: 'Where you live', emoji: '🏡', question: 'Where do you live?', hint: 'Estate, street, or area', type: 'text', field: 'residence', required: true, placeholder: 'e.g. Baraka Estate' });
            add({ id: 'city', section: 'Where you live', emoji: '🌆', question: 'Which city or town?', type: 'text', field: 'city', required: true, placeholder: 'e.g. Nanyuki' });
            add({ id: 'county', section: 'Where you live', emoji: '🧭', question: 'Which county?', hint: 'Optional', type: 'text', field: 'county', placeholder: 'e.g. Laikipia' });

            add({ id: 'household_name', section: 'Household', emoji: '🏘️', question: 'Household or family name?', hint: 'Leave blank to use your surname', type: 'text', field: 'household_name', placeholder: 'e.g. Kamau Family' });
            add({ id: 'is_head', section: 'Household', emoji: '👑', question: 'Are you the head of your household?', type: 'yesno', field: 'is_head' });

            add({ id: 'has_children', section: 'Children', emoji: '🧸', question: 'Do you have children living with you?', type: 'yesno', field: '_has_children' });

            if (this.hasChildren === true && this.children.length > 0) {
                this.children.forEach((child, i) => {
                    add({ id: `child_name_${i}`, section: 'Children', emoji: '💛', question: i === 0 ? "What's your child's name?" : `What's your next child's name?`, type: 'child_name', childIndex: i, required: true });
                    add({ id: `child_age_${i}`, section: 'Children', emoji: '🎈', question: `How old is ${child.name || 'your child'}?`, hint: 'Optional — press OK to skip', type: 'number', childIndex: i });
                });
                add({ id: 'more_children', section: 'Children', emoji: '✨', question: 'Any more children to add?', type: 'yesno', field: '_more_children' });
            }

            add({ id: 'baptized', section: 'Faith', emoji: '🕊️', question: 'Have you been water baptized?', type: 'yesno', field: 'baptized' });
            if (this.form.baptized) {
                add({ id: 'baptism_date', section: 'Faith', emoji: '🗓️', question: 'When were you baptized?', hint: 'Optional if you do not remember', type: 'date', field: 'baptism_date' });
            } else if (this.baptizedNo) {
                add({ id: 'wish_baptized', section: 'Faith', emoji: '💧', question: 'Would you like to be baptized?', hint: 'We would love to walk this journey with you', type: 'yesno', field: 'wish_to_be_baptized' });
            }
            add({ id: 'previous_church', section: 'Faith', emoji: '🕯️', question: 'Previous church (if any)?', hint: 'Optional', type: 'text', field: 'previous_church', placeholder: 'Church name & location' });
            add({ id: 'how_heard', section: 'Faith', emoji: '💡', question: 'How did you hear about Kingdomcity?', hint: 'Optional', type: 'select', field: 'how_heard_about_us', options: this.heardOptions });
            add({ id: 'ministries', section: 'Get involved', emoji: '🤝', question: 'Which ministries interest you?', hint: 'Open the dropdown, tap to select — ✓ confirms, ✕ removes', type: 'multiselect', options: this.ministryOptions });

            add({ id: 'occupation', section: 'Work', emoji: '🎯', question: 'What do you do for work?', hint: 'Optional', type: 'text', field: 'occupation', placeholder: 'e.g. Teacher' });
            add({ id: 'employer', section: 'Work', emoji: '🚀', question: 'Where do you work?', hint: 'Optional', type: 'text', field: 'employer' });
            add({ id: 'skills', section: 'Gifts', emoji: '🎨', question: 'Any skills, talents, or gifts?', hint: 'Music, tech, teaching... optional', type: 'textarea', field: 'skills_talents' });
            add({ id: 'emergency_name', section: 'Safety', emoji: '🛡️', question: "Emergency contact — who's name?", hint: 'Optional', type: 'text', field: 'emergency_contact_name' });
            add({ id: 'emergency_phone', section: 'Safety', emoji: '☎️', question: 'Emergency contact phone number?', hint: 'Optional', type: 'tel', field: 'emergency_contact_phone' });
            add({ id: 'notes', section: 'Anything else', emoji: '✍️', question: 'Anything else we should know?', hint: 'Prayer requests, allergies... optional', type: 'textarea', field: 'member_notes' });
            add({ id: 'review', section: 'Final step', emoji: '🎊', question: 'Ready to join <?= htmlspecialchars($churchName, ENT_QUOTES) ?>?', hint: 'Review your details below — everything look good?', type: 'review' });

            return q;
        },

        get current() {
            return this.queue[this.qi] || null;
        },
        get progress() {
            if (!this.queue.length) return 0;
            return Math.round(((this.qi + 1) / this.queue.length) * 100);
        },

        get reviewSections() {
            const row = (label, value) => {
                const v = typeof value === 'string' ? value.trim() : value;
                if (v === null || v === undefined || v === '' || v === false) return null;
                return { label, value: String(v) };
            };

            const sections = [];

            const personal = [
                row('Full name', `${this.form.first_name} ${this.form.last_name}`.trim()),
                row('Identity', this.genderDisplay()),
                row('Date of birth', this.formatDate(this.form.date_of_birth)),
                row('Marital status', this.maritalLabel(this.form.marital_status)),
                row('Spouse', this.form.spouse_name),
            ].filter(Boolean);
            if (personal.length) sections.push({ title: 'About you', items: personal });

            const contact = [
                row('Email', this.form.email),
                row('Phone', this.form.phone),
                row('Residence', this.form.residence),
                row('City', this.form.city),
                row('County', this.form.county),
            ].filter(Boolean);
            if (contact.length) sections.push({ title: 'Contact & location', items: contact });

            const household = [
                row('Family name', this.form.household_name || `${this.form.last_name} Family`.trim()),
                row('Head of household', this.form.is_head ? 'Yes' : 'No'),
            ].filter(Boolean);
            const childRows = this.childrenWithNames.map(c =>
                row('Child', c.age ? `${c.name} (${c.age} yrs)` : c.name)
            );
            if (childRows.length) household.push(...childRows);
            if (household.length) sections.push({ title: 'Household', items: household });

            const faith = [
                row('Water baptized', this.form.baptized ? 'Yes' : 'No'),
                row('Baptism date', this.formatDate(this.form.baptism_date)),
                row('Wishes to be baptized', this.form.wish_to_be_baptized ? 'Yes' : null),
                row('Previous church', this.form.previous_church),
                row('Heard about us', this.heardLabel(this.form.how_heard_about_us)),
            ].filter(Boolean);
            if (faith.length) sections.push({ title: 'Faith journey', items: faith });

            if (this.ministrySelected.length) {
                sections.push({
                    title: 'Ministries',
                    items: [{ label: 'Interested in', value: this.ministrySelected.join(', ') }],
                });
            }

            const work = [
                row('Occupation', this.form.occupation),
                row('Employer', this.form.employer),
                row('Skills & gifts', this.form.skills_talents),
            ].filter(Boolean);
            if (work.length) sections.push({ title: 'Work & gifts', items: work });

            const safety = [
                row('Emergency contact', this.form.emergency_contact_name),
                row('Emergency phone', this.form.emergency_contact_phone),
            ].filter(Boolean);
            if (safety.length) sections.push({ title: 'Emergency contact', items: safety });

            if (this.form.member_notes?.trim()) {
                sections.push({ title: 'Notes', items: [{ label: 'Additional info', value: this.form.member_notes.trim() }] });
            }

            return sections;
        },

        init() {
            if (this.form.wish_to_be_baptized) this.baptizedNo = true;
            this.$watch('qi', () => {
                this.stepError = '';
                this.ministryOpen = false;
                this.yesNoValue = null;
                this.syncYesNo();
                this.$nextTick(() => {
                    const el = this.$refs.input;
                    if (el) el.focus();
                });
            });
            this.$nextTick(() => this.$refs.input?.focus());
        },

        syncYesNo() {
            const c = this.current;
            if (!c || c.type !== 'yesno') return;
            if (c.field === 'is_head') this.yesNoValue = this.form.is_head ? true : null;
            else if (c.field === 'baptized') this.yesNoValue = this.form.baptized ? true : (this.baptizedNo ? false : null);
            else if (c.field === 'wish_to_be_baptized') this.yesNoValue = this.form.wish_to_be_baptized ? true : null;
            else if (c.field === '_has_children') this.yesNoValue = this.hasChildren;
            else if (c.field === '_more_children') this.yesNoValue = null;
        },

        getChoiceValue() {
            const f = this.current?.field;
            if (!f) return '';
            if (f === 'gender' && this.form.gender === 'other') return 'other';
            return this.form[f] || '';
        },

        genderForSubmit() {
            if (this.form.gender === 'other') return (this.form.gender_other || '').trim();
            return this.form.gender || '';
        },

        pickChoice(val) {
            if (this.current?.field) {
                this.form[this.current.field] = val;
                if (this.current.field === 'gender') {
                    if (val !== 'other') {
                        this.form.gender_other = '';
                        setTimeout(() => this.forward(), 280);
                        return;
                    }
                    this.$nextTick(() => this.$refs.otherInput?.focus());
                    return;
                }
            }
            setTimeout(() => this.forward(), 280);
        },

        pickYesNo(val) {
            this.yesNoValue = val;
            const c = this.current;
            if (c.field === 'is_head') this.form.is_head = val;
            else if (c.field === 'baptized') {
                this.form.baptized = val;
                this.baptizedNo = !val;
                if (!val) {
                    this.form.baptism_date = '';
                } else {
                    this.form.wish_to_be_baptized = false;
                    this.baptizedNo = false;
                }
            } else if (c.field === 'wish_to_be_baptized') this.form.wish_to_be_baptized = val;
            else if (c.field === '_has_children') {
                this.hasChildren = val;
                if (val && this.children.length === 0) this.children.push({ name: '', age: '' });
                if (!val) this.children = [];
            } else if (c.field === '_more_children') {
                if (val) {
                    this.children.push({ name: '', age: '' });
                    const newIdx = this.children.length - 1;
                    this.$nextTick(() => {
                        const idx = this.queue.findIndex(q => q.id === `child_name_${newIdx}`);
                        if (idx !== -1) this.qi = idx;
                    });
                    return;
                }
            }
            setTimeout(() => this.forward(), 280);
        },

        toggleMinistry(val) {
            const i = this.ministrySelected.indexOf(val);
            if (i === -1) this.ministrySelected.push(val);
            else this.ministrySelected.splice(i, 1);
        },

        toggleMinistryDropdown() {
            this.ministryOpen = !this.ministryOpen;
            if (!this.ministryOpen) return;
            this.$nextTick(() => {
                setTimeout(() => this.scrollMinistryPanelIntoView(), 120);
            });
        },

        scrollMinistryPanelIntoView() {
            const panel = this.$refs.ministryPanel;
            if (!panel) return;
            const footerGap = 130;
            const rect = panel.getBoundingClientRect();
            const overflow = rect.bottom - (window.innerHeight - footerGap);
            if (overflow > 0) {
                window.scrollBy({ top: overflow + 20, behavior: 'smooth' });
            }
        },

        get childrenWithNames() {
            return this.children.filter(c => (c.name || '').trim() !== '');
        },
        maritalLabel(v) {
            const m = this.maritalOptions.find(o => o.value === v);
            return m ? m.label : '';
        },
        genderDisplay() {
            if (this.form.gender === 'other') return this.form.gender_other?.trim() || 'Other';
            if (this.form.gender === 'male') return 'Male';
            if (this.form.gender === 'female') return 'Female';
            return '';
        },
        heardLabel(v) {
            const h = this.heardOptions.find(o => o.value === v);
            return h ? h.label : v || '';
        },
        formatDate(v) {
            if (!v) return '';
            try {
                return new Date(v + 'T00:00:00').toLocaleDateString('en-KE', { day: 'numeric', month: 'short', year: 'numeric' });
            } catch { return v; }
        },

        validate() {
            const c = this.current;
            if (!c || c.type === 'review') return '';
            if (c.type === 'yesno' && c.required && this.yesNoValue === null) return 'Please choose Yes or No.';
            if (c.field === 'gender' && c.type === 'choice' && this.form.gender === 'other' && !this.form.gender_other.trim()) {
                return 'Please tell us how you identify.';
            }
            if (c.type === 'yesno' && (c.field === 'baptized' || c.field === 'wish_to_be_baptized') && this.yesNoValue === null) {
                return 'Please choose Yes or No.';
            }
            if (!c.required) return '';
            if (c.type === 'child_name') {
                if (!this.children[c.childIndex]?.name?.trim()) return 'Please enter a name, or go back and choose No for children.';
                return '';
            }
            if (c.field && c.type !== 'yesno' && c.type !== 'multiselect' && c.type !== 'child_name' && c.type !== 'number') {
                if (!String(this.form[c.field] || '').trim()) return 'This field is required.';
            }
            if (c.field === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) return 'Please enter a valid email.';
            if (c.type === 'choice' && c.required && !this.form[c.field]) return 'Please pick an option.';
            return '';
        },

        forward() {
            const err = this.validate();
            if (err) { this.stepError = err; return; }
            if (this.qi < this.queue.length - 1) {
                this.qi++;
            }
        },

        back() {
            this.stepError = '';
            if (this.qi > 0) this.qi--;
        },

        handleKey(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                if (this.current?.type === 'textarea') return;
                if (this.current?.type === 'review') return;
                if (this.current?.type === 'yesno') return;
                if (this.current?.type === 'choice') {
                    if (this.current.field === 'gender' && this.form.gender === 'other') {
                        e.preventDefault();
                        this.forward();
                    }
                    return;
                }
                e.preventDefault();
                this.forward();
            }
        },
    };
}
</script>
