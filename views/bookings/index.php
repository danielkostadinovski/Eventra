<section class="page-band">
    <div class="container">
        <h1 class="display-5 mb-2">Welcome, <?= e($user['FirstName']) ?></h1>
        <p class="mb-0">Here are all your bookings. Open one to see its details, to pay, or to leave a review.</p>
    </div>
</section>

<div class="container py-5">

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
