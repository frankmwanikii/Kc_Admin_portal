<?php
/** @var string $pageKey e.g. arrearsPage | page */
/** @var string $listKey e.g. filteredArrears */
/** @var string $itemLabel e.g. arrears */
/** @var string $navLabel e.g. Expense arrears pages */
$perPageKey = $perPageKey ?? 'tablePerPage';
$perPageOptions = $perPageOptions ?? [10, 15, 25, 50];
?>
<div class="finance-pagination"
     x-data="{ gotoDraft: '' }"
     x-init="gotoDraft = String(<?= $pageKey ?>).padStart(2, '0'); $watch('<?= $pageKey ?>', (v) => { gotoDraft = String(v).padStart(2, '0'); })">
    <nav class="finance-pagination-nav" aria-label="<?= htmlspecialchars($navLabel) ?>">
        <button type="button"
                class="finance-pagination-btn finance-pagination-btn--icon"
                @click="firstPage('<?= $pageKey ?>')"
                :disabled="<?= $pageKey ?> <= 1"
                aria-label="First page">
            <i data-lucide="chevrons-left" class="w-4 h-4"></i>
        </button>
        <button type="button"
                class="finance-pagination-btn finance-pagination-btn--icon"
                @click="prevPage('<?= $pageKey ?>')"
                :disabled="<?= $pageKey ?> <= 1"
                aria-label="Previous page">
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
        </button>

        <div class="finance-pagination-pages">
            <template x-for="p in paginationPages(<?= $listKey ?>, '<?= $pageKey ?>')" :key="'<?= $pageKey ?>-' + p">
                <button type="button"
                        class="finance-pagination-page"
                        :class="{
                            'finance-pagination-page--active': p === <?= $pageKey ?>,
                            'finance-pagination-page--ellipsis': p === '…'
                        }"
                        @click="typeof p === 'number' && goPage('<?= $pageKey ?>', p, <?= $listKey ?>)"
                        :disabled="p === '…'"
                        :aria-label="p === '…' ? 'More pages' : ('Page ' + p)"
                        :aria-current="p === <?= $pageKey ?> ? 'page' : false"
                        x-text="p"></button>
            </template>
        </div>

        <button type="button"
                class="finance-pagination-btn finance-pagination-btn--icon"
                @click="nextPage('<?= $pageKey ?>', <?= $listKey ?>)"
                :disabled="<?= $pageKey ?> >= paginationTotalPages(<?= $listKey ?>)"
                aria-label="Next page">
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
        </button>
        <button type="button"
                class="finance-pagination-btn finance-pagination-btn--icon"
                @click="lastPage('<?= $pageKey ?>', <?= $listKey ?>)"
                :disabled="<?= $pageKey ?> >= paginationTotalPages(<?= $listKey ?>)"
                aria-label="Last page">
            <i data-lucide="chevrons-right" class="w-4 h-4"></i>
        </button>
    </nav>

    <div class="finance-pagination-meta">
        <span class="finance-pagination-range"
              x-show="<?= $listKey ?>.length > 0"
              x-cloak>
            <span x-text="paginationFrom(<?= $listKey ?>, <?= $pageKey ?>)"></span>–<span x-text="paginationTo(<?= $listKey ?>, <?= $pageKey ?>)"></span>
            of
            <span x-text="<?= $listKey ?>.length"></span>
        </span>

        <label class="finance-pagination-goto">
            <span class="finance-pagination-label">Go to</span>
            <input type="text"
                   inputmode="numeric"
                   class="finance-pagination-goto-input"
                   x-model="gotoDraft"
                   @keydown.enter.prevent="goPage('<?= $pageKey ?>', gotoDraft, <?= $listKey ?>); gotoDraft = String(<?= $pageKey ?>).padStart(2, '0')"
                   @blur="goPage('<?= $pageKey ?>', gotoDraft, <?= $listKey ?>); gotoDraft = String(<?= $pageKey ?>).padStart(2, '0')"
                   aria-label="Go to page">
            <span class="finance-pagination-of">
                of <span x-text="String(paginationTotalPages(<?= $listKey ?>)).padStart(2, '0')"></span>
            </span>
        </label>

        <span class="finance-pagination-divider" aria-hidden="true"></span>

        <label class="finance-pagination-perpage">
            <span class="finance-pagination-label">Show entries</span>
            <select class="finance-pagination-select"
                    :value="<?= $perPageKey ?>"
                    @change="setTablePerPage($event.target.value)"
                    aria-label="Rows per page">
                <?php foreach ($perPageOptions as $n): ?>
                <option value="<?= (int) $n ?>"><?= (int) $n ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
</div>
