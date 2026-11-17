
<h1 class="mb-1">Welcome, <?= e($user['FirstName']) ?></h1>
<p class="text-muted mb-5">Here are all your bookings. Open one to see its details, to pay, or to leave a review.</p>

<?php if (!$upcoming && !$past): ?>
    <p class="text-muted">You have no bookings yet.</p>
    <a class="btn btn-eventra" href="index.php?page=venues">Browse venues</a>
<?php endif; ?>

<?php if ($upcoming): ?>
    <h2 class="h4 mb-4">Upcoming bookings</h2>
    <div class="row g-4 mb-5">
        <?php foreach ($upcoming as $booking): ?>
            <?php require __DIR__ . '/card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($past): ?>
    <h2 class="h4 mb-4">Past and cancelled bookings</h2>
    <div class="row g-4">
        <?php foreach ($past as $booking): ?>
            <?php require __DIR__ . '/card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
