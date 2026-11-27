<table class="table align-middle mb-5">
    <thead>
        <tr>
            <th class="fw-normal text-muted">Date</th>
            <th class="fw-normal text-muted">Venue</th>
            <th class="fw-normal text-muted">Guests</th>
            <th class="fw-normal text-muted">Details</th>
            <th class="fw-normal text-muted text-end">Pay</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $assignment): ?>
            <?php
            // what to show in the details column
            if ($assignment['BookingStatus'] === 'Cancelled') {
                $details = 'Event cancelled';
            } elseif ($assignment['Status'] === 'Declined') {
                $details = 'Declined';
            } elseif ($assignment['BookingStatus'] === 'Completed') {
                $details = 'Completed';
            } elseif ($assignment['EventDate'] < date('Y-m-d')) {
                $details = 'Waiting to be marked as completed';
            } else {
                $details = 'Accepted';
            }
            ?>
            <tr>
                <td><?= date('D, j M Y', strtotime($assignment['EventDate'])) ?>, <?= e($assignment['StartTime']) ?> to <?= e($assignment['EndTime']) ?></td>
                <td><?= e($assignment['VenueName']) ?></td>
                <td><?= e($assignment['GuestCount']) ?></td>
                <td><?= $details ?></td>
                <td class="text-end">
                    <?= plural($assignment['HoursBooked'], 'hour') ?> at <?= money($assignment['HourlyStaffRate']) ?> = <?= money($assignment['Earnings']) ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
