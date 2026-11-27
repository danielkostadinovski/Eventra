<h1 class="mb-1">Review your booking</h1>
<p class="text-muted mb-4"><?= e($venue['Name']) ?>, Sofia</p>

<div class="row g-5">
    <div class="col-lg-7">
        <h2 class="h5 mb-3">Event details</h2>
        <table class="table">
            <tr>
                <th class="fw-normal text-muted">Date</th>
                <td><?= date('l, j F Y', strtotime($form['event_date'])) ?></td>
            </tr>
            <tr>
                <th class="fw-normal text-muted">Time</th>
                <td><?= e($form['start_time']) ?> to <?= sprintf('%02d:00', (int) substr($form['start_time'], 0, 2) + $form['hours']) ?></td>
            </tr>
            <tr>
                <th class="fw-normal text-muted">Guests</th>
                <td><?= e($form['guests']) ?></td>
            </tr>
            <tr>
                <th class="fw-normal text-muted">Waiting staff</th>
                <td><?= $form['staff_count'] > 0 ? plural($form['staff_count'], 'server') : 'None' ?></td>
            </tr>
            <tr>
                <th class="fw-normal text-muted">Catering</th>
                <td><?= $form['wants_food'] ? 'Yes' : 'No' ?></td>
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
        <p class="text-muted">
            This is a booking request, meaning that the venue manager will review it and contact you for a consultation.
            You only pay once your booking gets confirmed.
        </p>
    </div>

    <div class="col-lg-5">
        <div class="booking-box">
            <h2 class="h5 mb-3">Price</h2>
            <?php require __DIR__ . '/price.php'; ?>

            <form method="post" action="index.php?page=booking-submit" class="mt-4">
                <?php require __DIR__ . '/hidden-fields.php'; ?>
                <button type="submit" class="btn btn-eventra w-100">Submit booking request</button>
            </form>
            <form method="post" action="index.php?page=booking-change#booking" class="mt-2">
                <?php require __DIR__ . '/hidden-fields.php'; ?>
                <button type="submit" class="btn btn-eventra w-100">Go back and change</button>
            </form>
        </div>
    </div>
</div>
