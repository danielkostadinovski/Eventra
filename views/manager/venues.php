<section class="page-band">
    <div class="container">
        <h1 class="display-5 mb-2">Your venues</h1>
        <p class="mb-0">Open a venue to edit its details and photos, or to block dates when it cannot be booked.</p>
    </div>
</section>

<div class="container py-5">
    <?php if (!$venues): ?>
        <p class="text-muted">You do not run any venues yet.</p>
    <?php else: ?>
        <table class="table align-middle">
            <thead>
                <tr>
                    <th class="fw-normal text-muted">Venue</th>
                    <th class="fw-normal text-muted">Hourly rate</th>
                    <th class="fw-normal text-muted">Capacity</th>
                    <th class="fw-normal text-muted">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($venues as $venue): ?>
                    <tr>
                        <td><a href="index.php?page=manage-venue&id=<?= $venue['VenueID'] ?>"><?= e($venue['Name']) ?></a></td>
                        <td><?= money($venue['HourlyRate']) ?></td>
                        <td><?= plural($venue['MaxCapacity'], 'guest') ?></td>
                        <td><?= $venue['IsActive'] ? 'Active' : 'Inactive' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>