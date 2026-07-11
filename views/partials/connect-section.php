<?php

use App\Services\SettingsService;
use App\Services\WebsiteContentService;

$content = WebsiteContentService::bootstrap();
$connectCards = $content['connect_cards'];
$campuses = $content['campuses'];
$ministries = $content['ministries_list'];
$churchName = SettingsService::churchName();

$hubConfig = [
    'churchName' => $churchName,
    'cards' => array_map(static function (array $card): array {
        return [
            'title' => $card['title'] ?? '',
            'desc' => $card['desc'] ?? '',
            'image' => WebsiteContentService::assetUrl($card['image'] ?? ''),
            'form' => $card['modal'] ?? '',
            'btn' => $card['btn_text'] ?? 'Connect',
        ];
    }, $connectCards),
    'campuses' => $campuses,
    'ministries' => $ministries,
];
?>
<link rel="stylesheet" href="/css/connect-section.css">
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script src="/js/connect-icons.js"></script>
<script src="/js/connect-flow.js"></script>

<section class="connect-section py-16 sm:py-24" id="connect-with-us"
         x-data="connectHub(<?= htmlspecialchars(json_encode($hubConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES) ?>)"
         @kc-connect-open.window="openForm($event.detail.form)">

    <header class="connect-section-header kc-rise">
        <h2 class="connect-section-title">Connect With Us</h2>
    </header>

    <div class="connect-grid">
            <?php foreach ($connectCards as $i => $card): ?>
            <?php $formType = $card['modal'] ?? ''; ?>
            <article class="connect-card kc-rise" style="animation-delay: <?= 0.08 + ($i * 0.06) ?>s;">
                <div class="connect-card-img-wrap">
                    <img src="<?= htmlspecialchars(WebsiteContentService::assetUrl($card['image'] ?? '')) ?>"
                         alt="<?= htmlspecialchars($card['alt'] ?? $card['title'] ?? '') ?>"
                         class="connect-card-img"
                         loading="lazy">
                </div>
                <div class="connect-card-body">
                    <h3 class="connect-card-title"><?= htmlspecialchars($card['title'] ?? '') ?></h3>
                    <p class="connect-card-desc"><?= htmlspecialchars($card['desc'] ?? '') ?></p>
                    <?php if ($formType !== ''): ?>
                    <button type="button" class="connect-card-btn" @click="openForm('<?= htmlspecialchars($formType) ?>')">
                        <?= htmlspecialchars($card['btn_text'] ?? 'Connect') ?>
                    </button>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
    </div>

    <!-- Progressive Tally-style form overlay -->
    <div x-show="activeForm" x-transition.opacity x-cloak
         class="fixed inset-0 z-[1100] bg-gradient-to-br from-church-800 via-church-900 to-slate-900 flex flex-col"
         @keydown.window="handleKey($event)">

        <div class="fixed top-0 left-0 right-0 z-30 px-4 pt-4 pb-2 bg-gradient-to-b from-[#0b486d]/95 to-transparent">
            <div class="max-w-lg mx-auto">
                <div class="flex items-center justify-between text-xs text-white/60 mb-2 gap-3">
                    <button type="button" @click="closeForm()" class="inline-flex items-center gap-1.5 hover:text-white transition shrink-0">
                        <i data-lucide="x" class="w-4 h-4"></i> Close
                    </button>
                    <span class="truncate text-center flex-1" x-text="formMeta?.title"></span>
                    <span class="shrink-0"><span x-text="qi + 1"></span> / <span x-text="queue.length"></span></span>
                </div>
                <div class="h-1 rounded-full bg-white/15 overflow-hidden">
                    <div class="h-full rounded-full bg-[#35afe6] transition-all duration-500"
                         :style="`width: ${progress}%`"></div>
                </div>
            </div>
        </div>

        <main class="flex-1 flex flex-col justify-center px-4 pt-20 pb-10 overflow-y-auto">
            <div class="w-full max-w-lg mx-auto">
                <div x-show="submitError" x-transition
                     class="mb-4 p-3 rounded-xl bg-red-500/20 text-red-100 text-sm border border-red-400/30"
                     x-text="submitError"></div>
                <div x-show="stepError" x-transition
                     class="mb-4 p-3 rounded-xl bg-amber-500/20 text-amber-100 text-sm border border-amber-400/30"
                     x-text="stepError"></div>

                <div x-show="submitted" class="text-center kc-q-in py-8">
                    <div class="kc-step-icon kc-step-icon--success mx-auto">
                        <i data-lucide="circle-check" class="kc-step-icon-svg"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-3">Thank you!</h3>
                    <p class="text-white/70 leading-relaxed" x-text="successMessage"></p>
                    <button type="button" @click="closeForm()"
                            class="mt-8 px-8 py-3 rounded-xl bg-[#35afe6] hover:bg-[#2da0d9] text-white font-semibold transition">
                        Done
                    </button>
                </div>

                <div x-show="!submitted && !submitting" class="kc-q-in" :key="current?.id">
                    <div class="kc-step-icon" :key="'icon-' + (current?.id || qi)">
                        <i :data-lucide="stepIconName()" class="kc-step-icon-svg"></i>
                    </div>
                    <p class="text-[#35afe6] text-xs font-bold uppercase tracking-widest mb-2" x-show="current?.section" x-text="current?.section"></p>
                    <h2 class="text-2xl sm:text-3xl font-bold text-white leading-snug mb-2" x-text="current?.question"></h2>
                    <p class="text-white/50 text-sm mb-8" x-show="current?.hint" x-text="current?.hint"></p>
                    <p class="text-white/40 text-xs mb-8" x-show="!current?.hint && !current?.required">Press Enter to skip</p>

                    <template x-if="current?.type === 'text' || current?.type === 'email' || current?.type === 'tel' || current?.type === 'date' || current?.type === 'number'">
                        <input :type="current.type"
                               x-model="form[current.field]"
                               :placeholder="current.placeholder || ''"
                               x-ref="input"
                               class="w-full bg-transparent border-0 border-b-2 border-white/30 text-white text-xl sm:text-2xl py-3 placeholder:text-white/30 focus:border-[#35afe6] outline-none">
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
                                class="py-5 rounded-xl border-2 text-lg font-semibold transition-all flex items-center justify-center gap-2"
                                :class="yesNoValue === true ? 'border-[#35afe6] bg-[#35afe6]/20 text-white' : 'border-white/20 text-white/80 hover:border-[#35afe6]/50'">
                                <i data-lucide="check" class="w-5 h-5"></i> Yes
                            </button>
                            <button type="button" @click="pickYesNo(false)"
                                class="py-5 rounded-xl border-2 text-lg font-semibold transition-all flex items-center justify-center gap-2"
                                :class="yesNoValue === false ? 'border-[#35afe6] bg-[#35afe6]/20 text-white' : 'border-white/20 text-white/80 hover:border-[#35afe6]/50'">
                                <i data-lucide="x" class="w-5 h-5"></i> No
                            </button>
                        </div>
                    </template>

                    <template x-if="current?.type === 'choices'">
                        <div class="space-y-3">
                            <template x-for="opt in current.options" :key="opt.value">
                                <button type="button" @click="pickChoice(opt.value)"
                                    class="w-full text-left py-4 px-5 rounded-xl border-2 text-base font-medium transition-all flex items-center justify-between gap-3"
                                    :class="form[current.field] === opt.value ? 'border-[#35afe6] bg-[#35afe6]/20 text-white' : 'border-white/20 text-white/85 hover:border-[#35afe6]/50'">
                                    <span x-text="opt.label"></span>
                                    <i data-lucide="chevron-right" class="w-5 h-5 shrink-0 opacity-60"></i>
                                </button>
                            </template>
                        </div>
                    </template>

                    <template x-if="current?.type === 'multichoice'">
                        <div class="space-y-2 max-h-[45vh] overflow-y-auto">
                            <template x-for="opt in current.options" :key="opt.value">
                                <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition-all"
                                       :class="isMultiSelected(current.field, opt.value) ? 'border-[#35afe6] bg-[#35afe6]/15' : 'border-white/20 hover:border-white/35'">
                                    <span class="mt-0.5 flex items-center justify-center w-5 h-5 rounded border shrink-0 transition-colors"
                                          :class="isMultiSelected(current.field, opt.value) ? 'bg-[#35afe6] border-[#35afe6] text-white' : 'border-white/40 text-transparent'">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    </span>
                                    <input type="checkbox" class="sr-only"
                                           :checked="isMultiSelected(current.field, opt.value)"
                                           @change="toggleMulti(current.field, opt.value, $event.target.checked)">
                                    <span class="text-white text-sm leading-snug" x-text="opt.label"></span>
                                </label>
                            </template>
                        </div>
                    </template>

                    <template x-if="current?.type === 'review'">
                        <div class="space-y-3 max-h-[50vh] overflow-y-auto pr-1">
                            <template x-for="section in reviewSections" :key="section.title">
                                <div class="p-4 rounded-xl bg-white/10 border border-white/10">
                                    <p class="text-[#35afe6] text-xs font-bold uppercase tracking-widest mb-3" x-text="section.title"></p>
                                    <dl class="space-y-2">
                                        <template x-for="row in section.items" :key="row.label">
                                            <div class="flex justify-between gap-4 items-start">
                                                <dt class="text-white/45 text-sm shrink-0" x-text="row.label"></dt>
                                                <dd class="text-white font-medium text-sm text-right" x-text="row.value"></dd>
                                            </div>
                                        </template>
                                    </dl>
                                </div>
                            </template>
                            <p class="text-white/35 text-xs text-center pt-1">
                                Your information is shared with <?= htmlspecialchars($churchName) ?> for pastoral follow-up only.
                            </p>
                        </div>
                    </template>

                    <div class="mt-8 flex items-center gap-3" x-show="current?.type !== 'choices'">
                        <button type="button" x-show="qi > 0" @click="back()"
                            class="inline-flex items-center gap-1.5 px-4 py-3 rounded-xl text-white/60 hover:text-white text-sm font-medium transition">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
                        </button>
                        <button type="button" x-show="current?.type !== 'review' && current?.type !== 'multichoice'" @click="forward()"
                            class="flex-1 py-4 rounded-xl bg-[#35afe6] hover:bg-[#2da0d9] text-white font-bold text-lg shadow-lg transition active:scale-[0.98] inline-flex items-center justify-center gap-2">
                            OK <i data-lucide="corner-down-left" class="w-5 h-5"></i>
                        </button>
                        <button type="button" x-show="current?.type === 'multichoice'" @click="forward()"
                            class="flex-1 py-4 rounded-xl bg-[#35afe6] hover:bg-[#2da0d9] text-white font-bold text-lg shadow-lg transition inline-flex items-center justify-center gap-2">
                            Continue <i data-lucide="arrow-right" class="w-5 h-5"></i>
                        </button>
                        <button type="button" x-show="current?.type === 'review'" @click="submit()"
                            class="flex-1 py-4 rounded-xl bg-gradient-to-r from-[#35afe6] to-[#0b486d] text-white font-bold text-lg shadow-lg transition active:scale-[0.98] inline-flex items-center justify-center gap-2">
                            Submit <i data-lucide="send" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <div x-show="submitting" class="text-center py-16">
                    <div class="inline-block w-10 h-10 border-2 border-white/30 border-t-[#35afe6] rounded-full animate-spin"></div>
                    <p class="text-white/60 mt-4 text-sm">Sending...</p>
                </div>
            </div>
        </main>
    </div>
</section>

<style>
    @keyframes kc-q-in {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .kc-q-in { animation: kc-q-in 0.35s cubic-bezier(0.22, 1, 0.36, 1) both; }
    .kc-step-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 4.5rem; height: 4.5rem; border-radius: 1.25rem;
        background: linear-gradient(135deg, rgba(53,175,230,0.22) 0%, rgba(11,72,109,0.35) 100%);
        border: 1px solid rgba(255,255,255,0.12); margin-bottom: 1.25rem;
        color: #7dd0ef;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }
    .kc-step-icon--success {
        color: #4ade80;
        background: linear-gradient(135deg, rgba(74,222,128,0.18) 0%, rgba(11,72,109,0.35) 100%);
    }
    .kc-step-icon-svg {
        width: 2rem; height: 2rem;
        stroke-width: 1.75;
    }
    @keyframes kc-rise {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .kc-rise { animation: kc-rise 0.7s cubic-bezier(0.22, 1, 0.36, 1) both; }
</style>
