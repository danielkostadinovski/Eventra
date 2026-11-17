
<?php $galleryPhotos = array_slice($photos, 0, 3); ?>

<a href="index.php?page=venues" class="text-muted text-decoration-none small">&larr; All venues</a>

<h1 class="mt-2 mb-1"><?= e($venue['Name']) ?></h1>
<p class="text-muted">
    Sofia, Bulgaria  |  Up to <?= e($venue['MaxCapacity']) ?> guests |
    <?php if ($reviews): ?>
        <?= number_format($averageRating, 1) ?> (<?= count($reviews) ?> <?= count($reviews) === 1 ? 'review' : 'reviews' ?>)
    <?php endif; ?>
</p>

<?php if ($photos): ?>
    <div class="venue-gallery-wrap">
        <div class="venue-gallery gallery-count-<?= count($galleryPhotos) ?>">
            <?php foreach ($galleryPhotos as $index => $photo): ?>
                <img src="<?= e($photo) ?>" alt="<?= e($venue['Name']) ?>, photo <?= $index + 1 ?>"
                    data-photo-index="<?= $index ?>" data-bs-toggle="modal" data-bs-target="#photoModal">
            <?php endforeach; ?>
        </div>
        <?php if (count($photos) > 1): ?>
            <button type="button" class="btn btn-eventra bg-white show-photos-btn"
                    data-photo-index="0" data-bs-toggle="modal" data-bs-target="#photoModal">
                    Show all <?= count($photos) ?> photos
            </button>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row g-5 mt-1">
    <div class="col-lg-7">
        <h2 class="h4 mb-3">About this venue</h2>
        <p><?= e($venue['Description']) ?></p>
        <ul class="venue-facts">
            <li>Up to <?= e($venue['MaxCapacity']) ?> guests</li>
            <li><?= money($venue['HourlyRate']) ?> per hour, for up to 12 hours</li>
            <li>The venue is reserved for your event for the whole day</li>
            <li>Waiting staff, catering and equipment can be added when you book</li>
        </ul>

        <hr class="my-5">

        <h2 class="h4 mb-1" id="calendar">Available dates</h2>
        <p class="text-muted small mb-4">Crossed-out dates are not available, choose a free date to book it. </p>

        <div class="row g-4">
            <?php foreach ($months as $position => $month): ?>
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <?php if ($position === 0 && $previousMonth): ?>
                            <a class="calendar-arrow" href="index.php?page=venue&id=<?= $venue['VenueID'] ?>&month=<?= $previousMonth ?>#calendar" aria-label="Previous month">&lsaquo;</a>
                        <?php else: ?>
                            <span class="calendar-arrow"></span>
                        <?php endif; ?>

                        <h3 class="h6 mb-0"><?= $month['title'] ?></h3>

                        <?php if ($position === 1 && $followingMonth): ?>
                            <a class="calendar-arrow" href="index.php?page=venue&id=<?= $venue['VenueID'] ?>&month=<?= $followingMonth ?>#calendar" aria-label="Next month">&rsaquo;</a>
                        <?php else: ?>
                            <span class="calendar-arrow"></span>
                        <?php endif; ?>
                    </div>

                    <div class="calendar-grid">
                        <?php foreach (['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'] as $weekday): ?>
                            <div class="calendar-weekday"><?= $weekday ?></div>
                        <?php endforeach; ?>

                        <?php for ($cell = 0; $cell < $month['emptyCells']; $cell++): ?>
                            <div></div>
                        <?php endfor; ?>

                        <?php foreach ($month['days'] as $day): ?>
                            <?php if ($day['available']): ?>
                               <button type="button" class="calendar-day <?= $day['date'] === ($form['event_date'] ?? '') ? 'selected' : '' ?>" data-date="<?= $day['date'] ?>"><?= $day['number'] ?></button>
                            <?php else: ?>
                                <span class="calendar-day calendar-day-taken"><?= $day['number'] ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <hr class="my-5">

        <h2 class="h4 mb-4">
            <?php if ($reviews): ?>
                 <?= number_format($averageRating, 1) ?> | <?= count($reviews) ?> <?= count($reviews) === 1 ? 'review' : 'reviews' ?>
            <?php else: ?>
                Reviews
            <?php endif; ?>
        </h2>

        <?php if (!$reviews): ?>
            <p class="text-muted">This venue has no reviews yet</p>
        <?php endif; ?>

        <div class="row g-4">
            <?php foreach (array_slice($reviews, 0, 6) as $review): ?>
                <div class="col-md-6">
                    <p class="fw-semibold mb-0"><?= e($review['FirstName']) ?></p>
                    <p class="text-muted small mb-2">
                        <?= str_repeat('★', $review['Rating']) . str_repeat('☆', 5 - $review['Rating']) ?>
                        Event in <?= date('F Y', strtotime($review['EventDate'])) ?>
                    </p>
                    <p class="mb-0"><?= e($review['Comment']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="booking-box" id="booking">
            <p class="h4 mb-1"><?= money($venue['HourlyRate']) ?> <span class="fs-6 fw-normal text-muted">per hour</span></p>
            <p class="text-muted small mb-0">Up to <?= e($venue['MaxCapacity']) ?> guests</p>
            <hr>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="index.php?page=booking-review">
                <?= csrfField() ?>
                <input type="hidden" name="venue_id" value="<?= $venue['VenueID'] ?>">
                <input type="hidden" id="event-date" name="event_date" value="<?= e($form['event_date'] ?? '') ?>">

                <p class="text-muted small mb-1">Date</p>
                <p class="fw-semibold mb-3" id="selected-date">
                    <?= !empty($form['event_date']) ? date('l, j F Y', strtotime($form['event_date'])) : 'Choose a date in the calendar' ?>
                </p>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label" for="start-time">Start time</label>
                        <select class="form-select" id="start-time" name="start_time">
                            <?php foreach ($startTimes as $time): ?>
                                <option <?= ($form['start_time'] ?? '18:00') === $time ? 'selected' : '' ?>><?= $time ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="hours">Hours</label>
                        <select class="form-select" id="hours" name="hours">
                            <?php for ($hours = 1; $hours <= 12; $hours++): ?>
                                <option <?= ($form['hours'] ?? 4) === $hours ? 'selected' : '' ?>><?= $hours ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="guests">Guests</label>
                        <input type="number" class="form-control" id="guests" name="guests"
                               min="1" max="<?= e($venue['MaxCapacity']) ?>" value="<?= !empty($form['guests']) ? e($form['guests']) : '' ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="staff-count">Waiting staff</label>
                        <input type="number" class="form-control" id="staff-count" name="staff_count"
                               min="0" max="25" value="<?= e($form['staff_count'] ?? 0) ?>">
                    </div>
                </div>
                <p class="text-muted small">Waiting staff cost <?= money($rates['HourlyStaffRate']) ?> per server per hour, leave it at 0 if you do not need any.</p>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="wants-food" name="wants_food" value="1" <?= !empty($form['wants_food']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="wants-food">Catering for all guests (<?= money($rates['FoodRatePerPerson']) ?> per guest)</label>
                </div>

                <p class="mb-2">Equipment</p>
                <?php foreach ($equipment as $item): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="equipment-<?= $item['EquipmentID'] ?>">
                            <?= e($item['Name']) ?> <span class="text-muted small">(<?= money($item['UnitPrice']) ?> each)</span>
                        </label>
                        <input type="number" class="form-control equipment-quantity" id="equipment-<?= $item['EquipmentID'] ?>"
                               name="equipment[<?= $item['EquipmentID'] ?>]" min="0" max="10"
                               value="<?= e($form['equipment'][$item['EquipmentID']] ?? 0) ?>">
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-eventra w-100 mt-3">Review booking</button>
            </form>
        </div>
    </div>
</div>

<?php if ($photos): ?>
    <div class="modal fade" id="photoModal" tabindex="-1" aria-label="Photos of <?= e($venue['Name']) ?>">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-black text-white">
                <div class="modal-header border-0">
                    <span id="photo-counter">1 / <?= count($photos) ?></span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex align-items-center">
                    <div id="photoCarousel" class="carousel slide w-100" data-bs-interval="false" tabindex="0">
                        <div class="carousel-inner">
                            <?php foreach ($photos as $index => $photo): ?>
                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                    <img src="<?= e($photo) ?>" class="d-block mx-auto carousel-photo" alt="<?= e($venue['Name']) ?>, photo <?= $index + 1 ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#photoCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous photo</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#photoCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next photo</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // photos- clicking a photo opens the full screen viewer for the photo
    const carouselElement = document.getElementById('photoCarousel');

    if (carouselElement) {
        const carousel = bootstrap.Carousel.getOrCreateInstance(carouselElement);
        const counter = document.getElementById('photo-counter');
        const total = carouselElement.querySelectorAll('.carousel-item').length;

        document.querySelectorAll('[data-photo-index]').forEach((photo) => {
            photo.addEventListener('click', () => carousel.to(Number(photo.dataset.photoIndex)));
        });

        carouselElement.addEventListener('slid.bs.carousel', (event) => {
            counter.textContent = (event.to + 1) + ' / ' + total;
        });

        // so that the left and right arrow keys work as soon as the viewer opens
        document.getElementById('photoModal').addEventListener('shown.bs.modal', () => carouselElement.focus());
    }

    // calendar - clicking a free date selects it
    const selectedDate = document.getElementById('selected-date');
    const dateInput = document.getElementById('event-date');

    document.querySelectorAll('.calendar-day[data-date]').forEach((day) => {
        day.addEventListener('click', () => {
            document.querySelectorAll('.calendar-day.selected').forEach((other) => other.classList.remove('selected'));
            day.classList.add('selected');
            dateInput.value = day.dataset.date;
            selectedDate.textContent = new Date(day.dataset.date + 'T12:00').toLocaleDateString('en-GB', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
            });
        });
    });
});
</script>
