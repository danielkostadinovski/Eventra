<?php
// the largest value of each box (so that the longest bar fills the whole width)
$maxStatus = max(1, ...array_values($statuses ?: [0]));
$maxRevenue = max(1, ...array_column($venues, 'Revenue'));
$maxBookings = max(1, ...array_column($venues, 'Bookings'));
$popular = $venues;
usort($popular, fn ($a, $b) => $b['Bookings'] <=> $a['Bookings']);
$overdueTotal = array_sum(array_column($overdue, 'TotalPrice'));
?>

<section class="page-band">
    <div class="container d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <h1 class="display-5 mb-2">Reports</h1>
            <p class="mb-0">Live numbers from every booking. Revenue counts only the bookings that are paid.</p>
        </div>
        <form method="get" action="index.php" class="d-flex gap-2">
            <input type="hidden" name="page" value="admin-reports">
            <select class="form-select" name="period" aria-label="Period">
                <?php foreach (AdminController::PERIODS as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $value === $period ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-light">Show</button>
        </form>
    </div>
</section>

<div class="container py-5">
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="booking-box h-100">
                <p class="text-muted mb-1">Revenue</p>
                <p class="fs-3 mb-1"><?= money($summary['Revenue']) ?></p>
                <p class="small text-muted mb-0">From paid bookings</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="booking-box h-100">
                <p class="text-muted mb-1">Bookings</p>
                <p class="fs-3 mb-1"><?= $summary['Bookings'] ?></p>
                <p class="small text-muted mb-0">Not counting cancelled ones</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="booking-box h-100">
                <p class="text-muted mb-1">Average booking</p>
                <p class="fs-3 mb-1"><?= money($summary['AverageBooking']) ?></p>
                <p class="small text-muted mb-0">Per completed booking</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="booking-box h-100">
                <p class="text-muted mb-1">Overdue payments</p>
                <p class="fs-3 mb-1"><?= count($overdue) ?></p>
                <p class="small text-muted mb-0"><?= money($overdueTotal) ?> unpaid 14+ days after the event</p>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="booking-box h-100">
                <h2 class="h5 mb-1">Bookings by status</h2>
                <p class="text-muted mb-4">Bookings made in this period, and where they are now.</p>
                <?php foreach (['Pending', 'Approved', 'Confirmed', 'Completed', 'Cancelled'] as $status): ?>
                    <?php
                    $label = $status;
                    $amount = (string) ($statuses[$status] ?? 0);
                    $share = ($statuses[$status] ?? 0) / $maxStatus;
                    require __DIR__ . '/bar.php';
                    ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="booking-box h-100">
                <h2 class="h5 mb-1">Revenue per venue</h2>
                <p class="text-muted mb-4">Paid bookings only, from the highest revenue down.</p>
                <?php foreach ($venues as $venue): ?>
                    <?php
                    $label = $venue['Name'];
                    $amount = money($venue['Revenue']);
                    $share = $venue['Revenue'] / $maxRevenue;
                    require __DIR__ . '/bar.php';
                    ?>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="booking-box h-100">
                <h2 class="h5 mb-1">Most popular venues</h2>
                <p class="text-muted mb-4">Bookings that were not cancelled, and the clients' reviews.</p>
                <?php foreach ($popular as $venue): ?>
                    <?php
                    $label = $venue['Name'];
                    $amount = plural($venue['Bookings'], 'booking')
                        . ($venue['Reviews'] ? ', ' . number_format($venue['AverageRating'], 1) . ' out of 5' : '');
                    $share = $venue['Bookings'] / $maxBookings;
                    require __DIR__ . '/bar.php';
                    ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="booking-box">
        <h2 class="h5 mb-1">Overdue payments</h2>
        <p class="text-muted mb-4">Clients who haven't paid 14 days after their event. They can't make new bookings until they pay.</p>
        <?php if (!$overdue): ?>
            <p class="text-muted mb-0">There are no overdue payments.</p>
        <?php else: ?>
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="fw-normal text-muted">Client</th>
                        <th class="fw-normal text-muted">Venue</th>
                        <th class="fw-normal text-muted">Event date</th>
                        <th class="fw-normal text-muted">Overdue</th>
                        <th class="fw-normal text-muted text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($overdue as $row): ?>
                        <tr>
                            <td><?= e($row['FirstName'] . ' ' . $row['LastName']) ?><br><span class="small text-muted"><?= e($row['Email']) ?></span></td>
                            <td><?= e($row['VenueName']) ?></td>
                            <td><?= date('j M Y', strtotime($row['EventDate'])) ?></td>
                            <td><?= plural($row['DaysOverdue'], 'day') ?></td>
                            <td class="text-end"><?= money($row['TotalPrice']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>