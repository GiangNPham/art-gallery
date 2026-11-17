<?php

// Only the columns supplied by the list page can enter ORDER BY.
function art_list_sort(array $options, string $tieBreaker = ''): array
{
    $key = $_GET['sort'] ?? '';
    if (!is_string($key) || !isset($options[$key])) $key = array_key_first($options);
    $direction = ($_GET['direction'] ?? '') === 'desc' ? 'desc' : 'asc';
    $columns = array_slice($options[$key], 1);
    $orderBy = implode(', ', array_map(fn ($column) => $column . ' ' . strtoupper($direction), $columns));
    if ($tieBreaker !== '' && !in_array($tieBreaker, $columns, true)) {
        $orderBy .= ', ' . $tieBreaker . ' ASC';
    }

    return [$orderBy, $key, $direction];
}

function art_sort_controls(array $options, string $key, string $direction): void
{
    ?>
    <?php if (count($options) > 1): ?>
        <label>Sort by
            <select name="sort">
                <?php foreach ($options as $value => [$label]): ?>
                    <option value="<?= art_h($value) ?>"<?= $key === $value ? ' selected' : '' ?>><?= art_h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php endif; ?>
    <label>Order
        <select name="direction">
            <option value="asc"<?= $direction === 'asc' ? ' selected' : '' ?>>Ascending</option>
            <option value="desc"<?= $direction === 'desc' ? ' selected' : '' ?>>Descending</option>
        </select>
    </label>
    <?php
}
