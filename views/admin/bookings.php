<section class="page-band">
    <div class="container">
        <h1 class="display-5 mb-2">Confirmed bookings</h1>
        <p class="mb-0">Once a booking is confirmed only you can cancel it (and only in an emergency). A paid booking is then marked as Refunded.</p>
    </div>
</section>

<div class="container py-5">
    <?php if (!$bookings): ?>
        <p class="text-muted">There are no upcoming confirmed bookings.</p>
    <?php else: ?>
        <table class="table align-middle">
            <thead>
                <tr>
                    <th class="fw-normal text-muted">Date</th>
                    <th class="fw-normal text-muted">Venue</th>
                    <th class="fw-normal text-muted">Client</th>
                    <th class="fw-normal text-muted">Guests</th>
                    <th class="fw-normal text-muted">Payment</th>
                    <th class="fw-normal text-muted text-end">Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?= date('D, j M Y', strtotime($booking['EventDate'])) ?>, <?= e($booking['StartTime']) ?></td>
                        <td><?= e($booking['VenueName']) ?></td>
                        <td><?= e($booking['FirstName'] . ' ' . $booking['LastName']) ?></td>
                        <td><?= $booking['GuestCount'] ?></td>
                        <td><?= e($booking['PaymentStatus'] ?? 'Unpaid') ?></td>
                        <td class="text-end"><?= money($booking['TotalPrice']) ?></td>
                        <td class="text-end">
                            <form method="post" action="index.php?page=admin-booking-cancel">
                                <?= csrfField() ?>
                                <input type="hidden" name="booking_id" value="<?= $booking['BookingID'] ?>">
                                <button type="submit" class="btn btn-eventra btn-sm"
                                        onclick="return confirm('Do you want to cancel this confirmed booking? This cannot be undone.')">Cancel</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>