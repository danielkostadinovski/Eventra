<?php
// after an error the form shows what the manager typed (otherwise the saved values)
$description = $form['description'] ?? $venue['Description'];
$hourlyRate = $form['hourly_rate'] ?? number_format($venue['HourlyRate'] / 100, 2, '.', '');
$maxCapacity = $form['max_capacity'] ?? $venue['MaxCapacity'];
?>

<a href="index.php?page=manage-venues" class="text-muted text-decoration-none small">&larr; Your venues</a>

<h1 class="mt-2 mb-1"><?= e($venue['Name']) ?></h1>
<p class="text-muted mb-4">
    Clients see the changes straight away (a new hourly rate is used only for new bookings)
    <?php if (!$venue['IsActive']): ?>This venue is inactive, so clients cannot see it.<?php endif; ?>
</p>

<div class="row g-5">
    <div class="col-lg-7">
        <h2 class="h5 mb-3">Details</h2>

        <?php if (!empty($errors['details'])): ?>
            <div class="alert alert-danger"><?= e($errors['details']) ?></div>
        <?php endif; ?>

        <form method="post" action="index.php?page=manage-venue-save">
            <?= csrfField() ?>
            <input type="hidden" name="venue_id" value="<?= $venue['VenueID'] ?>">

            <label class="form-label" for="description">Description</label>
            <textarea class="form-control mb-3" id="description" name="description" rows="6"><?= e($description) ?></textarea>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label" for="hourly-rate">Hourly rate (&euro;)</label>
                    <input class="form-control" type="number" id="hourly-rate" name="hourly_rate" min="0.01" step="0.01" value="<?= e($hourlyRate) ?>">
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="max-capacity">Maximum capacity (guests)</label>
                    <input class="form-control" type="number" id="max-capacity" name="max_capacity" min="1" step="1" value="<?= e($maxCapacity) ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-eventra">Save details</button>
        </form>

        <h2 class="h5 mt-5 mb-1">Photos</h2>
        <p class="text-muted small mb-3">The first photo is the cover photo on the venues page. JPG or PNG up to 2 MB, and at most 10 photos.</p>

        <div class="row g-3 mb-3">
            <?php foreach ($photos as $index => $photo): ?>
                <div class="col-6 col-md-4">
                    <img src="<?= e($photo['FilePath']) ?>" class="manage-photo mb-2" alt="Photo <?= $index + 1 ?> of <?= e($venue['Name']) ?>">
                    <form method="post" action="index.php?page=manage-photo-delete"
                          onsubmit="return confirm('Do you want to remove this photo?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="venue_id" value="<?= $venue['VenueID'] ?>">
                        <input type="hidden" name="photo_id" value="<?= $photo['PhotoID'] ?>">
                        <button type="submit" class="btn btn-eventra btn-sm">Remove</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="post" action="index.php?page=manage-photo-add" enctype="multipart/form-data" class="d-flex gap-2">
            <?= csrfField() ?>
            <input type="hidden" name="venue_id" value="<?= $venue['VenueID'] ?>">
            <input class="form-control" type="file" name="photo" accept=".jpg,.jpeg,.png" aria-label="Photo">
            <button type="submit" class="btn btn-eventra text-nowrap">Add photo</button>
        </form>
    </div>

    <div class="col-lg-5">
        <div class="booking-box">
            <h2 class="h5 mb-1">Blackout dates</h2>
            <p class="text-muted small mb-3">
                Clients cannot book these dates (for example on a national holiday).
                A date that already has a booking cannot be blocked.
            </p>

            <?php if ($blackouts): ?>
                <table class="table align-middle">
                    <?php foreach ($blackouts as $blackout): ?>
                        <tr>
                            <td class="text-nowrap"><?= date('j M Y', strtotime($blackout['BlackoutDate'])) ?></td>
                            <td class="text-muted"><?= e($blackout['Reason']) ?></td>
                            <td class="text-end">
                                <?php if ($blackout['BlackoutDate'] > date('Y-m-d')): ?>
                                    <form method="post" action="index.php?page=manage-blackout-delete"
                                          onsubmit="return confirm('Do you want to remove this blackout date?');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="venue_id" value="<?= $venue['VenueID'] ?>">
                                        <input type="hidden" name="blackout_id" value="<?= $blackout['BlackoutID'] ?>">
                                        <button type="submit" class="btn btn-eventra btn-sm">Remove</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p class="text-muted">There are no upcoming blackout dates.</p>
            <?php endif; ?>

            <hr>

            <?php if (!empty($errors['blackout'])): ?>
                <div class="alert alert-danger"><?= e($errors['blackout']) ?></div>
            <?php endif; ?>

            <form method="post" action="index.php?page=manage-blackout-add">
                <?= csrfField() ?>
                <input type="hidden" name="venue_id" value="<?= $venue['VenueID'] ?>">

                <label class="form-label" for="blackout-date">Date</label>
                <input class="form-control mb-3" type="date" id="blackout-date" name="blackout_date"
                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= e($form['blackout_date'] ?? '') ?>">

                <label class="form-label" for="reason">Reason</label>
                <input class="form-control mb-3" type="text" id="reason" name="reason" maxlength="200"
                       placeholder="For example a national holiday" value="<?= e($form['reason'] ?? '') ?>">

                <button type="submit" class="btn btn-eventra w-100">Add blackout date</button>
            </form>
        </div>
    </div>
</div>
