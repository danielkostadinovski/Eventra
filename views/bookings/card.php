<?php $dueDate = date('Y-m-d', strtotime($booking['EventDate'] . ' +14 days')); ?>
<div class="col-sm-6 col-lg-4">
    <a class="venue-card" href="index.php?page=booking&id=<?= $booking['BookingID'] ?>">
        <?php if ($booking['CoverPhoto']): ?>
            <img src="<?= e($booking['CoverPhoto']) ?>" alt="<?= e($booking['VenueName']) ?>" loading="lazy">
        <?php else: ?>
            <div class="venue-card-placeholder"></div>
        <?php endif; ?>
        <h3 class="h5 mt-3 mb-1"><?= e($booking['VenueName']) ?></h3>
        <p class="text-muted mb-1"><?= date('l, j F Y', strtotime($booking['EventDate'])) ?>, from <?= e($booking['StartTime']) ?></p>
        <p class="mb-1"><?= e($booking['Status']) ?>, <strong><?= money($booking['TotalPrice']) ?></strong></p>

        <?php if ($booking['PaymentStatus'] === 'Unpaid' && $booking['Status'] !== 'Cancelled'): ?>
            <p class="text-danger small mb-0">
                <?= date('Y-m-d') > $dueDate ? 'Payment overdue' : 'Payment due by ' . date('j F Y', strtotime($dueDate)) ?>
            </p>
        <?php elseif ($booking['Status'] === 'Completed' && !$booking['HasReview']): ?>
            <p class="text-muted small mb-0">You can leave a review</p>
        <?php endif; ?>
    </a>
</div>
