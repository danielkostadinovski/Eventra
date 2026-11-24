<?php //one bar that gets reused by the reports?>
<div class="mb-3">
    <div class="d-flex justify-content-between mb-1">
        <span><?= e($label) ?></span>
        <span class="text-muted"><?= e($amount) ?></span>
    </div>
    <div class="report-bar">
        <div style="width: <?= round($share * 100) ?>%"></div>
    </div>
</div>