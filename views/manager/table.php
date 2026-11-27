<table class="table align-middle mb-5">
    <thead>
        <tr>
            <th class="fw-normal text-muted">Date</th>
            <th class="fw-normal text-muted">Venue</th>
            <th class="fw-normal text-muted">Client</th>
            <th class="fw-normal text-muted">Guests</th>
            <th class="fw-normal text-muted">Details</th>
            <th class="fw-normal text-muted text-end">Total</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $booking): ?>
            <tr>
                <td>
                    <a href="index.php?page=manage-booking&id=<?= $booking['BookingID'] ?>">
                        <?= date('D, j M Y', strtotime($booking['EventDate'])) ?>, <?= e($booking['StartTime']) ?>
                    </a>
                </td>
                <td><?= e($booking['VenueName']) ?></td>
                <td><?= e($booking['ClientName']) ?></td>
                <td><?= e($booking['GuestCount']) ?></td>
                <td>
                    <?= e($booking['Status']) ?><?php if ($booking['Status'] === 'Confirmed' && $booking['StaffCount'] > 0): ?>, 
                        <?= e($booking['AcceptedServers']) ?> of <?= e($booking['StaffCount']) ?> servers<?php endif; ?><?php if ($booking['PaymentStatus'] && $booking['Status'] !== 'Cancelled'): ?>,
                        <?= e($booking['PaymentStatus']) ?><?php endif; ?>
                </td>
                <td class="text-end"><?= money($booking['TotalPrice']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
