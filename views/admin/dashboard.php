<section class="page-band">
    <div class="container">
        <h1 class="display-5 mb-2">Welcome, <?= e($user['FirstName']) ?></h1>
        <p class="mb-0">From here you manage the whole company - the venues, the equipment catalog, prices, and performance</p>
    </div>
</section>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="booking-box h-100">
                <h2 class="h5 mb-1">Venues</h2>
                <p class="text-muted mb-3"><?= $activeVenues ?> active, <?= $inactiveVenues ?> inactive</p>
                <a class="btn btn-eventra" href="index.php?page=admin-venues">Manage venues</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="booking-box h-100">
                <h2 class="h5 mb-1">Equipment</h2>
                <p class="text-muted mb-3"><?= $offeredItems ?> offered, <?= $retiredItems ?> retired</p>
                <a class="btn btn-eventra" href="index.php?page=admin-equipment">Manage equipment</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="booking-box h-100">
                <h2 class="h5 mb-1">Prices</h2>
                <p class="text-muted mb-3">
                    Staff <?= money($rates['HourlyStaffRate']) ?> per hour, food <?= money($rates['FoodRatePerPerson']) ?> per guest
                </p>
                <a class="btn btn-eventra" href="index.php?page=admin-prices">Change prices</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="booking-box h-100">
                <h2 class="h5 mb-1">Users</h2>
                <p class="text-muted mb-3"><?= plural($staff, 'staff account') ?>, <?= plural($clients, 'client') ?></p>
                <a class="btn btn-eventra" href="index.php?page=admin-users">Manage users</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="booking-box h-100">
                <h2 class="h5 mb-1">Confirmed bookings</h2>
                <p class="text-muted mb-3"><?= $confirmedBookings ?> upcoming, only you can cancel them now</p>
                <a class="btn btn-eventra" href="index.php?page=admin-bookings">See bookings</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="booking-box h-100">
                <h2 class="h5 mb-1">Reports</h2>
                <p class="text-muted mb-3"><?= money($yearRevenue) ?> revenue in the last 12 months</p>
                <a class="btn btn-eventra" href="index.php?page=admin-reports">Open reports</a>
            </div>
        </div>
    </div>
</div>