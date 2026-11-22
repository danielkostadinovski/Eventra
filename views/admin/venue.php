<?php
// after an error the form shows what the administrator typed, otherwise the saved values (or empty for a new venue)
$name = $form['name'] ?? $venue['Name'] ?? '';
$description = $form['description'] ?? $venue['Description'] ?? '';
$hourlyRate = $form['hourly_rate'] ?? ($venue ? number_format($venue['HourlyRate'] / 100, 2, '.', '') : '');
$maxCapacity = $form['max_capacity'] ?? $venue['MaxCapacity'] ?? '';
$managerId = (int) ($form['manager_id'] ?? $venue['ManagerID'] ?? 0);
$isActive = (int) ($form['is_active'] ?? $venue['IsActive'] ?? 0);
?>

<a href="index.php?page=admin-venues" class="text-muted text-decoration-none small">&larr; Venues</a>

<h1 class="mt-2 mb-1"><?= $venue ? e($venue['Name']) : 'New venue' ?></h1>
<p class="text-muted mb-4">
    <?php if ($venue === null): ?>
        A new venue starts inactive. Its venue manager adds the photos and then you can make it active.
    <?php else: ?>
        The photos and blackout dates are managed by the venue manager, and a new hourly rate is used only for new bookings.
    <?php endif; ?>
</p>

<div class="row">
    <div class="col-lg-7">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="index.php?page=admin-venue-save">
            <?= csrfField() ?>
            <input type="hidden" name="venue_id" value="<?= $venue['VenueID'] ?? 0 ?>">

            <label class="form-label" for="name">Name</label>
            <input class="form-control mb-3" type="text" id="name" name="name" maxlength="100" value="<?= e($name) ?>">

            <label class="form-label" for="description">Description</label>
            <textarea class="form-control mb-3" id="description" name="description" rows="6"><?= e($description) ?></textarea>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label" for="hourly-rate">Hourly rate (&euro;)</label>
                    <input class="form-control" type="number" id="hourly-rate" name="hourly_rate" min="0.01" step="0.01" value="<?= e($hourlyRate) ?>">
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="max-capacity">Maximum capacity (guests)</label>
                    <input class="form-control" type="number" id="max-capacity" name="max_capacity" min="1" max="500" step="1" value="<?= e($maxCapacity) ?>">
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <label class="form-label" for="manager">Venue manager</label>
                    <select class="form-select" id="manager" name="manager_id">
                        <option value="0">Choose a venue manager</option>
                        <?php foreach ($managers as $manager): ?>
                            <?php if ($manager['IsActive'] || $manager['UserID'] === $managerId): ?>
                                <option value="<?= $manager['UserID'] ?>" <?= $manager['UserID'] === $managerId ? 'selected' : '' ?>>
                                    <?= e($manager['FirstName'] . ' ' . $manager['LastName']) ?><?= $manager['IsActive'] ? '' : ' (deactivated)' ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($venue !== null): ?>
                    <div class="col-sm-6">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="is_active">
                            <option value="1" <?= $isActive === 1 ? 'selected' : '' ?>>Active: clients can book it</option>
                            <option value="0" <?= $isActive === 0 ? 'selected' : '' ?>>Inactive: hidden from clients</option>
                        </select>
                    </div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-eventra"><?= $venue ? 'Save venue' : 'Add venue' ?></button>
        </form>
    </div>
</div>
