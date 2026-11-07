<h1 class="mb-4">Venues</h1>

<div class="row g-4">
    <?php foreach ($venues as $venue): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <h2 class="h5"><?= e($venue['Name']) ?></h2>
                    <p class="text-muted"><?= e($venue['Description']) ?></p>
                    <p class="mb-3">
                        Up to <?= e($venue['MaxCapacity']) ?> guests<br>
                        <?= money($venue['HourlyRate']) ?> per hour
                    </p>
                    <a class="btn btn-eventra mt-auto" href="index.php?page=venue&id=<?= $venue['VenueID'] ?>">View venue</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>