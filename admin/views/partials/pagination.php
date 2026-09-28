<?php
/**
 * Admin Pagination Partial
 * Expects: $pagination (array), $baseUrl (string)
 */
if (empty($pagination) || ($pagination['total_pages'] ?? 1) <= 1) return;

$p = $pagination['page'];
$tp = $pagination['total_pages'];
$sep = str_contains($baseUrl, '?') ? '&' : '?';
?>
<div class="admin-pagination">
    <?php if ($p > 1): ?>
        <a href="<?= $baseUrl . $sep ?>page=<?= $p - 1 ?>">&laquo;</a>
    <?php endif; ?>

    <?php
    $start = max(1, $p - 2);
    $end = min($tp, $p + 2);
    for ($i = $start; $i <= $end; $i++):
        $active = $i === $p ? ' active' : '';
    ?>
        <a href="<?= $baseUrl . $sep ?>page=<?= $i ?>" class="<?= $active ?>"><?= $i ?></a>
    <?php endfor; ?>

    <?php if ($p < $tp): ?>
        <a href="<?= $baseUrl . $sep ?>page=<?= $p + 1 ?>">&raquo;</a>
    <?php endif; ?>
</div>
