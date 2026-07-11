<?php
/** @var string $pageKey e.g. arrearsPage */
/** @var string $listKey e.g. filteredArrears */
/** @var string $itemLabel e.g. arrears */
/** @var string $navLabel e.g. Expense arrears pages */
?>
<div class="finance-pagination">
    <p class="finance-pagination-info">
        Showing
        <span x-text="paginationFrom(<?= $listKey ?>, <?= $pageKey ?>)"></span>–<span x-text="paginationTo(<?= $listKey ?>, <?= $pageKey ?>)"></span>
        of
        <span x-text="<?= $listKey ?>.length"></span>
        <?= htmlspecialchars($itemLabel) ?>
    </p>
    <nav class="finance-pagination-nav" aria-label="<?= htmlspecialchars($navLabel) ?>">
        <button type="button"
                class="finance-pagination-btn"
                @click="prevPage('<?= $pageKey ?>')"
                :disabled="<?= $pageKey ?> <= 1"
                aria-label="Previous page">
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>Previous</span>
        </button>
        <div class="finance-pagination-pages">
            <template x-for="p in paginationPages(<?= $listKey ?>)" :key="'<?= $pageKey ?>-' + p">
                <button type="button"
                        class="finance-pagination-page"
                        :class="p === <?= $pageKey ?> && 'finance-pagination-page--active'"
                        @click="goPage('<?= $pageKey ?>', p, <?= $listKey ?>)"
                        :aria-label="'Page ' + p"
                        :aria-current="p === <?= $pageKey ?> ? 'page' : false"
                        x-text="p"></button>
            </template>
        </div>
        <button type="button"
                class="finance-pagination-btn"
                @click="nextPage('<?= $pageKey ?>', <?= $listKey ?>)"
                :disabled="<?= $pageKey ?> >= paginationTotalPages(<?= $listKey ?>)"
                aria-label="Next page">
            <span>Next</span>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
        </button>
    </nav>
</div>
