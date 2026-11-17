
<?= csrfField() ?>
<input type="hidden" name="venue_id" value="<?= e($form['venue_id']) ?>">
<input type="hidden" name="event_date" value="<?= e($form['event_date']) ?>">
<input type="hidden" name="start_time" value="<?= e($form['start_time']) ?>">
<input type="hidden" name="hours" value="<?= e($form['hours']) ?>">
<input type="hidden" name="guests" value="<?= e($form['guests']) ?>">
<input type="hidden" name="staff_count" value="<?= e($form['staff_count']) ?>">
<?php if ($form['wants_food']): ?>
    <input type="hidden" name="wants_food" value="1">
<?php endif; ?>
<?php foreach ($form['equipment'] as $equipmentId => $quantity): ?>
    <?php if ($quantity > 0): ?>
        <input type="hidden" name="equipment[<?= $equipmentId ?>]" value="<?= $quantity ?>">
    <?php endif; ?>
<?php endforeach; ?>
