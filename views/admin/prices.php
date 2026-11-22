<?php
// after an error the form shows what the admin typed, otherwise the saved prices
$staffRate = $form['staff_rate'] ?? number_format($rates['HourlyStaffRate'] / 100, 2, '.', '');
$foodRate = $form['food_rate'] ?? number_format($rates['FoodRatePerPerson'] / 100, 2, '.', '');
?>

<a href="index.php?page=admin" class="text-muted text-decoration-none small">&larr; Overview</a>

<h1 class="mt-2 mb-1">Prices</h1>
<p class="text-muted mb-4">
    New bookings use these prices from now on. Existing bookings keep the prices they were made with,
    and so do the servers' earnings for them.
</p>

<div class="row">
    <div class="col-lg-6">
        <div class="booking-box">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="index.php?page=admin-prices-save">
                <?= csrfField() ?>

                <label class="form-label" for="staff-rate">Hourly rate per server (&euro;)</label>
                <input class="form-control mb-3" type="number" id="staff-rate" name="staff_rate" min="0.01" step="0.01" value="<?= e($staffRate) ?>">

                <label class="form-label" for="food-rate">Food rate per guest (&euro;)</label>
                <input class="form-control mb-4" type="number" id="food-rate" name="food_rate" min="0.01" step="0.01" value="<?= e($foodRate) ?>">

                <button type="submit" class="btn btn-eventra">Save prices</button>
            </form>
        </div>
    </div>
</div>
