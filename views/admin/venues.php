<section class="page-band">
    <div class="container">
        <h1 class="display-5 mb-2">Venues</h1>
        <p class="mb-0">All of the company's venues. Open a venue to change its details, venue manager, or if clients can book it.</p>
    </div>
</section>

<div class="container py-5">
    <a class="btn btn-eventra mb-4" href="index.php?page=admin-venue">Add a venue</a>

    <table class="table align-middle">
        <thead>
            <tr>
                <th class="fw-normal text-muted">Venue</th>
                <th class="fw-normal text-muted">Venue manager</th>
                <th class="fw-normal text-muted">Hourly rate</th>
                <th class="fw-normal text-muted">Capacity</th>
                <th class="fw-normal text-muted">Photos</th>
                <th class="fw-normal text-muted">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($venues as $venue): ?>
                <tr>
                    <td><a href="index.php?page=admin-venue&id=<?= $venue['VenueID'] ?>"><?= e($venue['Name']) ?></a></td>
                    <td><?= e($venue['FirstName'] . ' ' . $venue['LastName']) ?></td>
                    <td><?= money($venue['HourlyRate']) ?></td>
                    <td><?= plural($venue['MaxCapacity'], 'guest') ?></td>
                    <td><?= $venue['PhotoCount'] ?></td>
                    <td><?= $venue['IsActive'] ? 'Active' : 'Inactive' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>