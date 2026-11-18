
<?php $dueDate = date('Y-m-d', strtotime($booking['EventDate'] . ' +14 days')); ?>

<a href="index.php?page=manager" class="text-muted text-decoration-none small">&larr; Bookings</a>

<h1 class="mt-2 mb-1"><?= e($booking['VenueName']) ?></h1>
<p class="text-muted mb-4">
    Booking number <?= e($booking['BookingID']) ?>, requested on <?= date('j F Y', strtotime($booking['CreatedAt'])) ?>
</p>

<div class="row g-5">
    <div class="col-lg-7">
        <h2 class="h5 mb-3">Status: <?= e($booking['Status']) ?></h2>

        <?php if ($booking['Status'] === 'Pending'): ?>
            <p class="text-muted">Approve the request if the venue can host this event, then contact the client for a consultation.</p>
            <div class="d-flex gap-2">
                <form method="post" action="index.php?page=manage-status">
                    <?= csrfField() ?>
                    <input type="hidden" name="booking_id" value="<?= $booking['BookingID'] ?>">
                    <button type="submit" name="action" value="approve" class="btn btn-eventra">Approve</button>
                </form>
                <form method="post" action="index.php?page=manage-status"
                      onsubmit="return confirm('Do you want to reject this request? The booking will be cancelled.');">
                    <?= csrfField() ?>
                    <input type="hidden" name="booking_id" value="<?= $booking['BookingID'] ?>">
                    <button type="submit" name="action" value="reject" class="btn btn-eventra">Reject</button>
                </form>
            </div>
        <?php elseif ($booking['Status'] === 'Approved'): ?>
            <p class="text-muted">Confirm the booking after the consultation. The client can then pay and you can assign servers.</p>
            <form method="post" action="index.php?page=manage-status">
                <?= csrfField() ?>
                <input type="hidden" name="booking_id" value="<?= $booking['BookingID'] ?>">
                <button type="submit" name="action" value="confirm" class="btn btn-eventra">Confirm booking</button>
            </form>
        <?php elseif ($booking['Status'] === 'Confirmed' && $booking['EventDate'] <= date('Y-m-d')): ?>
            <p class="text-muted">The event has taken place. Mark it as completed so that the client can leave a review.</p>
            <form method="post" action="index.php?page=manage-status">
                <?= csrfField() ?>
                <input type="hidden" name="booking_id" value="<?= $booking['BookingID'] ?>">
                <button type="submit" name="action" value="complete" class="btn btn-eventra">Mark as completed</button>
            </form>
        <?php elseif ($booking['Status'] === 'Confirmed'): ?>
            <p class="text-muted">The booking can be marked as completed on or after the event date.</p>
        <?php endif; ?>

        <h2 class="h5 mt-5 mb-3">Client</h2>
        <p class="mb-0"><?= e($booking['FirstName'] . ' ' . $booking['LastName']) ?></p>
        <p><a href="mailto:<?= e($booking['Email']) ?>"><?= e($booking['Email']) ?></a></p>

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

        <?php if ($booking['StaffCount'] > 0): ?>
            <h2 class="h5 mt-5 mb-1">Servers</h2>
            <p class="text-muted small mb-3">
                The client requested <?= plural($booking['StaffCount'], 'server') ?>. A server has to accept an assignment,
                and if they decline, another server can be assigned in their place.
            </p>

            <?php if ($assignments): ?>
                <table class="table align-middle">
                    <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td><?= e($assignment['FirstName'] . ' ' . $assignment['LastName']) ?></td>
                            <td class="text-muted"><?= e($assignment['Status']) ?></td>
                            <td class="text-end">
                                <?php if ($assignment['Status'] === 'Pending' && $booking['Status'] === 'Confirmed'): ?>
                                    <form method="post" action="index.php?page=manage-withdraw">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="booking_id" value="<?= $booking['BookingID'] ?>">
                                        <input type="hidden" name="assignment_id" value="<?= $assignment['AssignmentID'] ?>">
                                        <button type="submit" class="btn btn-eventra btn-sm">Withdraw</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>

            <?php if ($canAssign && $availableServers): ?>
                <form method="post" action="index.php?page=manage-assign" class="d-flex gap-2">
                    <?= csrfField() ?>
                    <input type="hidden" name="booking_id" value="<?= $booking['BookingID'] ?>">
                    <select class="form-select" name="server_id" aria-label="Server">
                        <?php foreach ($availableServers as $server): ?>
                            <option value="<?= $server['UserID'] ?>"><?= e($server['FirstName'] . ' ' . $server['LastName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-eventra text-nowrap">Assign Server</button>
                </form>
            <?php elseif ($canAssign): ?>
                <p class="text-muted">No servers are free at the time of this event.</p>
            <?php elseif (in_array($booking['Status'], ['Pending', 'Approved'], true)): ?>
                <p class="text-muted">Servers can be assigned once the booking is confirmed.</p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($booking['Status'] !== 'Cancelled'): ?>
            <h2 class="h5 mt-5 mb-1">Consultation notes</h2>
            <p class="text-muted small mb-3">Only you can see these notes. You can also attach one PDF, JPG, or PNG file of up to 2 MB.</p>
            <form method="post" action="index.php?page=manage-notes" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="booking_id" value="<?= $booking['BookingID'] ?>">
                <textarea class="form-control mb-3" name="notes" rows="4" aria-label="Consultation notes"><?= e($booking['ConsultationNotes'] ?? '') ?></textarea>
                <?php if ($booking['AttachmentPath']): ?>
                    <p class="small mb-2">
                        Current attachment:
                        <a href="index.php?page=manage-attachment&id=<?= $booking['BookingID'] ?>" target="_blank">open the file</a>
                    </p>
                <?php endif; ?>
                <input class="form-control mb-3" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" aria-label="Attachment">
                <button type="submit" class="btn btn-eventra">Save notes</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="col-lg-5">
        <div class="booking-box">
            <h2 class="h5 mb-3">Price</h2>
            <?php require __DIR__ . '/../bookings/price.php'; ?>

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
                        <form method="post" action="index.php?page=manage-paid"
                              onsubmit="return confirm('Record this payment as paid?');">
                            <?= csrfField() ?>
                            <input type="hidden" name="booking_id" value="<?= $booking['BookingID'] ?>">
                            <button type="submit" class="btn btn-eventra w-100">Mark as paid</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            <?php elseif (in_array($booking['Status'], ['Pending', 'Approved'], true)): ?>
                <hr>
                <p class="text-muted small mb-0">The payment opens once the booking is confirmed.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
