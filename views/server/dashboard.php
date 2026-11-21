<section class="page-band">
    <div class="container">
        <h1 class="display-5 mb-2">Welcome, <?= e($user['FirstName']) ?></h1>
        <p class="mb-0">These are the events you've been assigned to. Please answer new assignments as soon as you can.</p>
    </div>
</section>

<div class="container py-5">

    <div class="row g-4 mb-5">
        <div class="col-sm-6 col-lg-4">
            <div class="booking-box">
                <p class="text-muted small mb-1">Total earnings</p>
                <p class="h3 mb-1"><?= money($earnings['Total']) ?></p>
                <p class="text-muted small mb-0">From <?= plural($earnings['Events'], 'completed event') ?></p>
            </div>
        </div>
    </div>

    <h2 class="h4 mb-1">Waiting for your answer</h2>
    <?php if ($sections['pending']): ?>
        <p class="text-muted small mb-3">Accept the events you can work. If you decline the venue manager will assign someone else.</p>
        <table class="table align-middle mb-5">
            <thead>
                <tr>
                    <th class="fw-normal text-muted">Date</th>
                    <th class="fw-normal text-muted">Venue</th>
                    <th class="fw-normal text-muted">Guests</th>
                    <th class="fw-normal text-muted">Pay</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sections['pending'] as $assignment): ?>
                    <tr>
                        <td><?= date('D, j M Y', strtotime($assignment['EventDate'])) ?>, <?= e($assignment['StartTime']) ?> to <?= e($assignment['EndTime']) ?></td>
                        <td><?= e($assignment['VenueName']) ?></td>
                        <td><?= e($assignment['GuestCount']) ?></td>
                        <td><?= money($assignment['Earnings']) ?></td>
                        <td class="text-end">
                            <div class="d-flex gap-2 justify-content-end">
                                <form method="post" action="index.php?page=server-respond">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="assignment_id" value="<?= $assignment['AssignmentID'] ?>">
                                    <button type="submit" name="action" value="accept" class="btn btn-eventra btn-sm">Accept</button>
                                </form>
                                <form method="post" action="index.php?page=server-respond"
                                      onsubmit="return confirm('Do you want to decline this event?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="assignment_id" value="<?= $assignment['AssignmentID'] ?>">
                                    <button type="submit" name="action" value="decline" class="btn btn-eventra btn-sm">Decline</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-muted mb-5">There are no new assignments.</p>
    <?php endif; ?>

    <h2 class="h4 mb-1">Upcoming events</h2>
    <?php if ($sections['upcoming']): ?>
        <p class="text-muted small mb-3">The events you have accepted.</p>
        <?php $rows = $sections['upcoming']; require __DIR__ . '/table.php'; ?>
    <?php else: ?>
        <p class="text-muted mb-5">You have no upcoming events.</p>
    <?php endif; ?>

    <?php if ($worked): ?>
        <h2 class="h4 mb-1">Past events</h2>
        <p class="text-muted small mb-3">The 20 most recent - each event pays its hours times the hourly staff rate at the time it was booked.</p>
        <?php $rows = $worked; require __DIR__ . '/table.php'; ?>
    <?php endif; ?>

    <?php if ($other): ?>
        <h2 class="h4 mb-1">Declined and cancelled</h2>
        <p class="text-muted small mb-3">The 10 most recent ones.</p>
        <?php $rows = $other; require __DIR__ . '/table.php'; ?>
    <?php endif; ?>
</div>
