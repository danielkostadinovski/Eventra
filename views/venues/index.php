<section class="venues-hero">
    <video autoplay muted loop playsinline>
        <source src="videos/venues.mp4" type="video/mp4">
    </video>
    <div class="container venues-hero-text">
        <h1 class="display-5 mb-2">Venues</h1>
        <p class="mb-0"><?= count($venues) ?> venues across Sofia for weddings, birthdays and different events.</p>
    </div>
</section>

<div class="container py-5">
    <div class="row g-4">
        <?php foreach ($venues as $venue): ?>
            <div class="col-sm-6 col-lg-4">
                <a class="venue-card" href="index.php?page=venue&id=<?= $venue['VenueID'] ?>">
                    <?php if ($venue['CoverPhoto']): ?>
                        <img src="<?= e($venue['CoverPhoto']) ?>" alt="<?= e($venue['Name']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="venue-card-placeholder"></div>
                    <?php endif; ?>
                    <h2 class="h5 mt-3 mb-1"><?= e($venue['Name']) ?></h2>
                    <p class="text-muted mb-1">Up to <?= e($venue['MaxCapacity']) ?> guests</p>
                    <p class="mb-0"><strong><?= money($venue['HourlyRate']) ?></strong> per hour</p>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
