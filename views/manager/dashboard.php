<section class="page-band">
    <div class="container">
        <h1 class="display-5 mb-2">Welcome, <?= e($user['FirstName']) ?></h1>
        <p class="mb-0">
            These are the bookings for your venues: <?= e(implode(', ', array_column($venues, 'Name'))) ?>.
            Open a booking to approve it, confirm it, assign servers, or record a payment.
        </p>
    </div>
</section>

<div class="container py-5">
    <h2 class="h4 mb-1">New requests</h2>
    <?php if ($sections['pending']): ?>
        <p class="text-muted small mb-3">Approve or reject these requests, then contact the client for a consultation.</p>
        <?php $rows = $sections['pending']; require __DIR__ . '/table.php'; ?>
    <?php else: ?>
        <p class="text-muted mb-5">There are no new requests.</p>
    <?php endif; ?>

    <?php if ($sections['approved']): ?>
        <h2 class="h4 mb-1">Approved, waiting for confirmation</h2>
        <p class="text-muted small mb-3">Confirm these bookings after the consultation with the client.</p>
        <?php $rows = $sections['approved']; require __DIR__ . '/table.php'; ?>
    <?php endif; ?>

    <?php if ($sections['toComplete']): ?>
        <h2 class="h4 mb-1">Ready to be marked as completed</h2>
        <p class="text-muted small mb-3">These events have taken place.</p>
        <?php $rows = $sections['toComplete']; require __DIR__ . '/table.php'; ?>
    <?php endif; ?>

    <?php if ($sections['overdue']): ?>
        <h2 class="h4 mb-1">Overdue payments</h2>
        <p class="text-muted small mb-3">These payments are still unpaid more than 14 days after the event.</p>
        <?php $rows = $sections['overdue']; require __DIR__ . '/table.php'; ?>
    <?php endif; ?>

    <?php if ($sections['upcoming']): ?>
        <h2 class="h4 mb-1">Upcoming confirmed events</h2>
        <p class="text-muted small mb-3">Check that every event has its servers.</p>
        <?php $rows = $sections['upcoming']; require __DIR__ . '/table.php'; ?>
    <?php endif; ?>

    <?php if ($past): ?>
        <h2 class="h4 mb-1">Past and cancelled bookings</h2>
        <p class="text-muted small mb-3">The 20 most recent ones.</p>
        <?php $rows = $past; require __DIR__ . '/table.php'; ?>
    <?php endif; ?>
</div>