<?php
$statusText = [
    'Pending' => 'Your request has been sent and the venue manager will review it soon.',
    'Approved' => 'The venue manager has approved your request and will contact you for a consultation soon.',
    'Confirmed' => 'Your booking is confirmed.',
    'Completed' => 'This event has taken place.',
    'Cancelled' => 'This booking has been cancelled.',
];
$dueDate = date('Y-m-d', strtotime($booking['EventDate'] . ' +14 days'));
?>

<a href="index.php?page=client" class="text-muted text-decoration-none small">&larr; My bookings</a>

<h1 class="mt-2 mb-1"><?= e($booking['VenueName']) ?></h1>
<p class="text-muted mb-4">
    Booking number <?= e($booking['BookingID']) ?>, requested on <?= date('j F Y', strtotime($booking['CreatedAt'])) ?>
</p>

<div class="row g-5">
    <div class="col-lg-7">
        <h2 class="h5 mb-1">Status: <?= e($booking['Status']) ?></h2>
        <p class="text-muted mb-3"><?= e($statusText[$booking['Status']]) ?></p>

        <?php if (in_array($booking['Status'], ['Pending', 'Approved'], true)): ?>
            <form method="post" action="index.php?page=booking-cancel"
                  onsubmit="return confirm('Do you want to cancel this booking?');">
                <?= csrfField() ?>
                <input type="hidden" name="booking_id" value="<?= $booking['BookingID'] ?>">
                <button type="submit" class="btn btn-eventra">Cancel booking</button>
            </form>
        <?php endif; ?>

        <h2 class="h5 mt-5 mb-3">Event details</h2>
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
        </table>

        <?php if ($booking['ReviewRating'] !== null): ?>
            <h2 class="h5 mt-5 mb-1">Your review</h2>
            <p class="text-muted small mb-2">Rated <?= e($booking['ReviewRating']) ?> out of 5</p>
            <p><?= e($booking['ReviewComment']) ?></p>
        <?php elseif ($booking['Status'] === 'Completed'): ?>
            <h2 class="h5 mt-5 mb-1">Leave a review</h2>
            <p class="text-muted small mb-3">Tell other clients how your event went.</p>

            <?php if ($reviewError): ?>
                <div class="alert alert-danger"><?= e($reviewError) ?></div>
            <?php endif; ?>

            <form method="post" action="index.php?page=review-add">
                <?= csrfField() ?>
                <input type="hidden" name="booking_id" value="<?= $booking['BookingID'] ?>">
                <label class="form-label" for="rating">Rating</label>
                <select class="form-select mb-3" id="rating" name="rating">
                    <?php foreach ([5 => 'Excellent', 4 => 'Very good', 3 => 'Good', 2 => 'Fair', 1 => 'Poor'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($reviewForm['rating'] ?? 5) === $value ? 'selected' : '' ?>>
                            <?= $value ?> out of 5 (<?= $label ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label" for="comment">Comment</label>
                <textarea class="form-control mb-3" id="comment" name="comment" rows="4" maxlength="1000"><?= e($reviewForm['comment'] ?? '') ?></textarea>
                <button type="submit" class="btn btn-eventra">Submit review</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="col-lg-5">
        <div class="booking-box">
            <h2 class="h5 mb-3">Price</h2>
            <?php require __DIR__ . '/price.php'; ?>

            <?php if ($booking['PaymentStatus']): ?>
                <hr>
                <p class="text-muted small mb-1">Payment</p>

                <?php if ($booking['PaymentStatus'] === 'Paid'): ?>
                    <p class="fw-semibold mb-0">Paid on <?= date('j F Y', strtotime($booking['PaidAt'])) ?></p>
                <?php elseif ($booking['PaymentStatus'] === 'Refunded'): ?>
                    <p class="fw-semibold mb-0">Refunded</p>
                <?php else: ?>
                    <p class="fw-semibold mb-1">Unpaid</p>
                    <p class="small <?= date('Y-m-d') > $dueDate ? 'text-danger' : 'text-muted' ?>">
                        <?= date('Y-m-d') > $dueDate ? 'This payment is overdue. ' : '' ?>The payment is due by <?= date('j F Y', strtotime($dueDate)) ?>.
                    </p>
                    <?php if (in_array($booking['Status'], ['Confirmed', 'Completed'], true)): ?>
                        <form method="post" action="index.php?page=booking-pay">
                            <?= csrfField() ?>
                            <input type="hidden" name="booking_id" value="<?= $booking['BookingID'] ?>">
                            <button type="submit" class="btn btn-eventra w-100">Pay now</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
