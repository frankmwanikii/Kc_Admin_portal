<?php
$d = $data ?? [];
$initial = [
    'first_name' => $d['first_name'] ?? '',
    'last_name' => $d['last_name'] ?? '',
    'spouse_name' => $d['spouse_name'] ?? '',
    'phone' => $d['phone'] ?? '',
    'email' => $d['email'] ?? '',
    'review' => $d['review'] ?? '',
    'how_heard_about_us' => $d['how_heard_about_us'] ?? '',
];
$childrenInitial = !empty($d['children'])
    ? array_map(fn($n) => ['name' => $n], $d['children'])
    : [];
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
    html, body { height: 100%; min-height: 100%; overflow-y: auto; }
</style>

<div class="min-h-dvh flex flex-col"
     x-data="visitorFlow(<?= htmlspecialchars(json_encode($initial, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($childrenInitial, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES) ?>)"
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

    <main class="flex-1 flex flex-col justify-center px-4 pt-20 pb-10">
        <div class="w-full max-w-lg mx-auto">
        <?php if (!empty($error)): ?>
        <div class="mb-4 p-3 rounded-xl bg-red-500/20 text-red-100 text-sm border border-red-400/30 kc-q-in">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <div x-show="stepError" x-transition class="mb-4 p-3 rounded-xl bg-amber-500/20 text-amber-100 text-sm border border-amber-400/30" x-text="stepError"></div>

        <form method="POST" action="/visit" id="visitor-form">
            <input type="hidden" name="first_name" :value="form.first_name">
            <input type="hidden" name="last_name" :value="form.last_name">
            <input type="hidden" name="spouse_name" :value="form.spouse_name">
            <input type="hidden" name="phone" :value="form.phone">
            <input type="hidden" name="email" :value="form.email">
            <input type="hidden" name="review" :value="form.review">
            <input type="hidden" name="how_heard_about_us" :value="form.how_heard_about_us">
            <template x-for="(child, ci) in children" :key="'hid-'+ci">
                <input type="hidden" :name="'children_name[]'" :value="child.name">
            </template>

            <div class="kc-q-in" :key="current?.id">
                <div class="kc-emoji" x-text="current?.emoji"></div>
                <h2 class="text-2xl sm:text-3xl font-bold text-white leading-snug mb-2" x-text="current?.question"></h2>
                <p class="text-white/50 text-sm mb-8" x-show="current?.hint" x-text="current?.hint"></p>
                <p class="text-white/40 text-xs mb-8" x-show="!current?.hint && !current?.required">Press Enter to skip</p>
                <p class="text-white/40 text-xs mb-8" x-show="current?.required && current?.type !== 'review'">Required</p>

                <template x-if="current?.type === 'text' || current?.type === 'email' || current?.type === 'tel'">
                    <input :type="current.type === 'email' ? 'email' : current.type === 'tel' ? 'tel' : 'text'"
                           x-model="form[current.field]"
                           :placeholder="current.placeholder || ''"
                           x-ref="input"
                           class="w-full bg-transparent border-0 border-b-2 border-white/30 text-white text-xl sm:text-2xl py-3 placeholder:text-white/30 focus:border-[#35afe6] focus:ring-0 outline-none transition">
                </template>

                <template x-if="current?.type === 'textarea'">
                    <textarea x-model="form[current.field]" rows="4" x-ref="input"
                              :placeholder="current.placeholder || ''"
                              class="w-full bg-white/10 rounded-xl px-4 py-4 text-white text-lg border border-white/20 focus:border-[#35afe6] outline-none resize-none placeholder:text-white/30"></textarea>
                </template>

                <template x-if="current?.type === 'select'">
                    <select x-model="form[current.field]" x-ref="input"
                            class="w-full bg-white/10 rounded-xl px-4 py-4 text-white text-lg border border-white/20 focus:border-[#35afe6] outline-none">
                        <option value="" class="text-slate-900">Choose one...</option>
                        <template x-for="opt in current.options" :key="opt.value">
                            <option :value="opt.value" class="text-slate-900" x-text="opt.label"></option>
                        </template>
                    </select>
                </template>

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

                <template x-if="current?.type === 'child_name'">
                    <input type="text" x-model="children[current.childIndex].name" x-ref="input"
                           placeholder="Child's full name"
                           class="w-full bg-transparent border-0 border-b-2 border-white/30 text-white text-xl sm:text-2xl py-3 placeholder:text-white/30 focus:border-[#35afe6] outline-none">
                </template>

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
                            Your information is shared with <?= htmlspecialchars($churchName) ?> for pastoral follow-up only.
                        </p>
                    </div>
                </template>
            </div>

            <div class="mt-8 flex items-center gap-3">
                <button type="button" x-show="qi > 0" @click="back()"
                    class="px-4 py-3 rounded-xl text-white/60 hover:text-white text-sm font-medium transition">
                    &#8592; Back
                </button>
                <button type="button" x-show="current?.type !== 'review'" @click="forward()"
                    class="flex-1 py-4 rounded-xl bg-[#35afe6] hover:bg-[#2da0d9] text-white font-bold text-lg shadow-lg shadow-[#35afe6]/30 transition-all active:scale-[0.98]">
                    OK &#8629;
                </button>
                <button type="submit" x-show="current?.type === 'review'"
                    class="flex-1 py-4 rounded-xl bg-gradient-to-r from-[#35afe6] to-[#0b486d] text-white font-bold text-lg shadow-lg transition-all active:scale-[0.98]">
                    🙏 Submit visitor card
                </button>
            </div>
        </form>
        </div>
    </main>
</div>

<script>
function visitorFlow(saved, childrenSaved) {
    return {
        qi: 0,
        stepError: '',
        form: saved,
        children: childrenSaved.length ? childrenSaved : [],
        hasSpouse: saved.spouse_name ? true : null,
        hasChildren: childrenSaved.length > 0 ? true : null,
        yesNoValue: null,
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

            add({ id: 'first_name', section: 'Welcome', emoji: '👋', question: "What's your first name?", type: 'text', field: 'first_name', required: true, placeholder: 'Type your first name...' });
            add({ id: 'last_name', section: 'Welcome', emoji: '👋', question: "And your last name?", type: 'text', field: 'last_name', required: true });
            add({ id: 'has_spouse', section: 'Family', emoji: '💕', question: 'Did you visit with your spouse?', type: 'yesno', field: '_has_spouse' });

            if (this.hasSpouse === true) {
                add({ id: 'spouse_name', section: 'Family', emoji: '💕', question: "What's your spouse's full name?", hint: 'Optional — press OK to skip', type: 'text', field: 'spouse_name', placeholder: 'Spouse full name' });
            }

            add({ id: 'has_children', section: 'Family', emoji: '🧸', question: 'Did you visit with children?', type: 'yesno', field: '_has_children' });

            if (this.hasChildren === true && this.children.length > 0) {
                this.children.forEach((child, i) => {
                    add({ id: `child_name_${i}`, section: 'Family', emoji: '💛', question: i === 0 ? "What's your child's name?" : "What's your next child's name?", type: 'child_name', childIndex: i, required: true });
                });
                add({ id: 'more_children', section: 'Family', emoji: '✨', question: 'Any more children to add?', type: 'yesno', field: '_more_children' });
            }

            add({ id: 'phone', section: 'Stay in touch', emoji: '📲', question: "What's your phone number?", type: 'tel', field: 'phone', required: true, placeholder: '+254 7XX XXX XXX' });
            add({ id: 'email', section: 'Stay in touch', emoji: '💌', question: "What's your email address?", type: 'email', field: 'email', required: true, placeholder: 'you@email.com' });
            add({ id: 'review', section: 'Your visit', emoji: '💬', question: 'How was your experience today?', hint: 'Optional — we love hearing from you', type: 'textarea', field: 'review', placeholder: 'What stood out? How can we welcome you better?' });
            add({ id: 'how_heard', section: 'Your visit', emoji: '💡', question: 'How did you hear about us?', hint: 'Optional', type: 'select', field: 'how_heard_about_us', options: this.heardOptions });
            add({ id: 'review_final', section: 'Almost done', emoji: '🙏', question: 'Ready to submit your visitor card?', hint: 'Review your details below — everything look good?', type: 'review' });

            return q;
        },

        get current() { return this.queue[this.qi] || null; },
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
                row('Spouse', this.form.spouse_name),
            ].filter(Boolean);
            const childRows = this.childrenWithNames.map(c => row('Child', c.name));
            if (childRows.length) personal.push(...childRows);
            if (personal.length) sections.push({ title: 'Who visited', items: personal });

            const contact = [
                row('Phone', this.form.phone),
                row('Email', this.form.email),
            ].filter(Boolean);
            if (contact.length) sections.push({ title: 'Contact', items: contact });

            const visit = [
                row('Experience', this.form.review),
                row('Heard about us', this.heardLabel(this.form.how_heard_about_us)),
            ].filter(Boolean);
            if (visit.length) sections.push({ title: 'Your visit', items: visit });

            return sections;
        },

        init() {
            if (this.form.spouse_name) this.hasSpouse = true;
            if (this.children.length) this.hasChildren = true;
            this.$watch('qi', () => {
                this.stepError = '';
                this.yesNoValue = null;
                this.syncYesNo();
                this.$nextTick(() => this.$refs.input?.focus());
            });
            this.$nextTick(() => this.$refs.input?.focus());
        },

        syncYesNo() {
            const c = this.current;
            if (!c || c.type !== 'yesno') return;
            if (c.field === '_has_spouse') this.yesNoValue = this.hasSpouse;
            else if (c.field === '_has_children') this.yesNoValue = this.hasChildren;
            else if (c.field === '_more_children') this.yesNoValue = null;
        },

        pickYesNo(val) {
            this.yesNoValue = val;
            const c = this.current;
            if (c.field === '_has_spouse') {
                this.hasSpouse = val;
                if (!val) this.form.spouse_name = '';
            } else if (c.field === '_has_children') {
                this.hasChildren = val;
                if (val && this.children.length === 0) this.children.push({ name: '' });
                if (!val) this.children = [];
            } else if (c.field === '_more_children') {
                if (val) {
                    this.children.push({ name: '' });
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

        get childrenWithNames() {
            return this.children.filter(c => (c.name || '').trim() !== '');
        },

        heardLabel(v) {
            const h = this.heardOptions.find(o => o.value === v);
            return h ? h.label : v || '';
        },

        validate() {
            const c = this.current;
            if (!c || c.type === 'review') return '';
            if (c.type === 'yesno' && c.required && this.yesNoValue === null) return 'Please choose Yes or No.';
            if (!c.required) return '';
            if (c.type === 'child_name') {
                if (!this.children[c.childIndex]?.name?.trim()) return 'Please enter a name, or go back and choose No.';
                return '';
            }
            if (c.field && c.type !== 'yesno' && c.type !== 'child_name') {
                if (!String(this.form[c.field] || '').trim()) return 'This field is required.';
            }
            if (c.field === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) return 'Please enter a valid email.';
            return '';
        },

        forward() {
            const err = this.validate();
            if (err) { this.stepError = err; return; }
            if (this.qi < this.queue.length - 1) this.qi++;
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
                e.preventDefault();
                this.forward();
            }
        },
    };
}
</script>
