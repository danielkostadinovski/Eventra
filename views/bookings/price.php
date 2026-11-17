
<?php foreach ($price['lines'] as $line): ?>
    <div class="d-flex justify-content-between gap-3 mb-2">
        <span><?= e($line['label']) ?></span>
        <span class="text-nowrap"><?= money($line['amount']) ?></span>
    </div>
<?php endforeach; ?>
<hr>
<div class="d-flex justify-content-between fw-semibold fs-5">
    <span>Total</span>
    <span><?= money($price['total']) ?></span>
</div>
