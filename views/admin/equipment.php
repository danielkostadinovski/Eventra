<section class="page-band">
    <div class="container">
        <h1 class="display-5 mb-2">Equipment</h1>
        <p class="mb-0">The add-ons clients can choose when they book (a new price is used only for new bookings and retired items stay on past bookings).</p>
    </div>
</section>

<div class="container py-5">
    <table class="table align-middle">
        <thead>
            <tr>
                <th class="fw-normal text-muted">Item and price (&euro;)</th>
                <th class="fw-normal text-muted">Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <form method="post" action="index.php?page=admin-equipment-save" class="row g-2 align-items-center">
                            <?= csrfField() ?>
                            <input type="hidden" name="equipment_id" value="<?= $item['EquipmentID'] ?>">
                            <div class="col-7">
                                <input class="form-control" type="text" name="name" maxlength="100" value="<?= e($item['Name']) ?>" aria-label="Name">
                            </div>
                            <div class="col-3">
                                <input class="form-control" type="number" name="price" min="0.01" step="0.01"
                                       value="<?= number_format($item['UnitPrice'] / 100, 2, '.', '') ?>" aria-label="Price in euro">
                            </div>
                            <div class="col-2">
                                <button type="submit" class="btn btn-eventra btn-sm">Save</button>
                            </div>
                        </form>
                    </td>
                    <td><?= $item['IsActive'] ? 'Offered' : 'Retired' ?></td>
                    <td class="text-end">
                        <form method="post" action="index.php?page=admin-equipment-status">
                            <?= csrfField() ?>
                            <input type="hidden" name="equipment_id" value="<?= $item['EquipmentID'] ?>">
                            <?php if ($item['IsActive']): ?>
                                <button type="submit" name="action" value="retire" class="btn btn-eventra btn-sm">Retire</button>
                            <?php else: ?>
                                <button type="submit" name="action" value="offer" class="btn btn-eventra btn-sm">Offer again</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="row mt-5">
        <div class="col-lg-7">
            <div class="booking-box">
                <h2 class="h5 mb-3">Add an item</h2>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" action="index.php?page=admin-equipment-add" class="row g-2 align-items-end">
                    <?= csrfField() ?>
                    <div class="col-sm-7">
                        <label class="form-label" for="new-name">Name</label>
                        <input class="form-control" type="text" id="new-name" name="name" maxlength="100" value="<?= e($form['name'] ?? '') ?>">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label" for="new-price">Price (&euro;)</label>
                        <input class="form-control" type="number" id="new-price" name="price" min="0.01" step="0.01" value="<?= e($form['price'] ?? '') ?>">
                    </div>
                    <div class="col-sm-2">
                        <button type="submit" class="btn btn-eventra">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
