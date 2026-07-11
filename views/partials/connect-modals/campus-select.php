<?php
/** Campus dropdown for connect modals — uses $campuses and $campus_select_id from parent. */
if (empty($campus_select_id)) {
    return;
}
$campuses = $campuses ?? [];
$defaultCampus = $campuses[0]['id'] ?? 'nanyuki';
?>
<select class="nb-input nb-select" id="<?= htmlspecialchars($campus_select_id) ?>" name="campus" required>
    <?php foreach ($campuses as $campus): ?>
    <option value="<?= htmlspecialchars($campus['id']) ?>"<?= $campus['id'] === $defaultCampus ? ' selected' : '' ?>>
        <?= htmlspecialchars($campus['name']) ?>
    </option>
    <?php endforeach; ?>
</select>
