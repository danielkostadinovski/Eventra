
<?php
$statusText = [
    'Pending' => 'Your request has been sent and the venue manager will review it soon.',
    'Approved' => 'The venue manager has approved your request and will contact you for a consultation soon.',
    'Confirmed' => 'Your booking is confirmed.',
    'Completed' => 'This event has taken place.',
    'Cancelled' => 'This booking has been cancelled.',
];
?>

<a href="index.php?page=venues" class="text-muted text-decoration-none small">&larr; All venues</a>

<h1 class="mt-2 mb-1"><?= e($booking['VenueName']) ?></h1>
<p class="text-muted mb-4">
    Booking number <?= e($booking['BookingID']) ?>, requested on <?= date('j F Y', strtotime($booking['CreatedAt'])) ?>
</p>

<div class="row g-5">
    <div class="col-lg-7">
        <h2 class="h5 mb-1">Status: <?= e($booking['Status']) ?></h2>
        <p class="text-muted mb-4"><?= e($statusText[$booking['Status']]) ?></p>

        <h2 class="h5 mb-3">Event details</h2>
        <table class="table">
            <tr>
                <th class="fw-normal text-muted">Date</th>
                <td><?= date('l, j F Y', strtotime($booking['EventDate'])) ?></td>
            </tr>
            <tr>
                <th class="fw-normal text-muted">Time</th>
                <td><?= e($booking['StartTime']) ?> to <?= e($booking['EndTime']) ?></td>
            </tr>
            <tr>
                <th class="fw-normal text-muted">Guests</th>
                <td><?= e($booking['GuestCount']) ?></td>
            </tr>
            <tr>
                <th class="fw-normal text-muted">Waiting staff</th>
                <td><?= $booking['StaffCount'] > 0 ? plural($booking['StaffCount'], 'server') : 'None' ?></td>
            </tr>
            <tr>
                <th class="fw-normal text-muted">Catering</th>
                <td><?= $booking['WantsFood'] ? 'Yes' : 'No' ?></td>
            </tr>
            <tr>
                <th class="fw-normal text-muted">Equipment</th>
                <td>
                    <?php if (!$equipment): ?>
                        None
                    <?php endif; ?>
                    <?php foreach ($equipment as $item): ?>
                        <div><?= e($item['Name']) ?>, <?= e($item['Quantity']) ?></div>
                    <?php endforeach; ?>
                </td>
            </tr>
            <?php if ($booking['PaymentStatus']): ?>
                <tr>
                    <th class="fw-normal text-muted">Payment</th>
                    <td><?= e($booking['PaymentStatus']) ?></td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="col-lg-5">
        <div class="booking-box">
            <h2 class="h5 mb-3">Price</h2>
            <?php require __DIR__ . '/price.php'; ?>
        </div>
    </div>
</div>
