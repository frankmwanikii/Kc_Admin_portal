<?php
/** Modern month/year picker (replaces native input[type=month]). */
$ariaLabel = $monthPickerLabel ?? 'Month';
$pickerTarget = $monthPickerTarget ?? 'ledger';
$pickerClass = $monthPickerClass ?? '';
?>
<div class="fin-month-picker <?= htmlspecialchars($pickerClass) ?>"
     @keydown.escape.window="monthPickerTarget === '<?= htmlspecialchars($pickerTarget) ?>' && closeMonthPicker()"
     @click.outside="monthPickerTarget === '<?= htmlspecialchars($pickerTarget) ?>' && closeMonthPicker()">
    <button type="button"
            class="fin-month-picker__trigger"
            :aria-expanded="monthPickerOpen && monthPickerTarget === '<?= htmlspecialchars($pickerTarget) ?>'"
            aria-haspopup="dialog"
            aria-label="<?= htmlspecialchars($ariaLabel) ?>"
            @click="toggleMonthPicker('<?= htmlspecialchars($pickerTarget) ?>')">
        <span class="fin-month-picker__value"
              x-text="monthPickerLabel('<?= htmlspecialchars($pickerTarget) ?>')"><?= htmlspecialchars($monthLabel ?? '') ?></span>
        <i data-lucide="calendar" class="fin-month-picker__icon w-4 h-4"></i>
    </button>

    <div class="fin-month-picker__panel"
         x-show="monthPickerOpen && monthPickerTarget === '<?= htmlspecialchars($pickerTarget) ?>'"
         x-cloak
         x-transition.opacity.duration.150ms
         role="dialog"
         aria-label="Choose month and year">
        <div class="fin-month-picker__year">
            <button type="button"
                    class="fin-month-picker__year-btn"
                    aria-label="Previous year"
                    @click="shiftMonthPickerYear(-1)">
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
            </button>
            <span class="fin-month-picker__year-label" x-text="monthPickerYear"></span>
            <button type="button"
                    class="fin-month-picker__year-btn"
                    aria-label="Next year"
                    @click="shiftMonthPickerYear(1)">
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="fin-month-picker__grid" role="listbox" aria-label="Months">
            <template x-for="(label, index) in monthNamesShort" :key="label">
                <button type="button"
                        role="option"
                        class="fin-month-picker__month"
                        :class="isMonthPickerSelected(index + 1) && 'fin-month-picker__month--active'"
                        :aria-selected="isMonthPickerSelected(index + 1)"
                        @click="pickMonthPickerMonth(index + 1)"
                        x-text="label"></button>
            </template>
        </div>

        <div class="fin-month-picker__footer">
            <button type="button" class="fin-month-picker__link" @click="pickMonthPickerToday()">This month</button>
        </div>
    </div>
</div>
<?php
unset($monthPickerLabel, $monthPickerTarget, $monthPickerClass, $monthLabel, $ariaLabel, $pickerTarget, $pickerClass);
?>
