<section class="page-band">
    <div class="container">
        <h1 class="display-5 mb-2">Users</h1>
        <p class="mb-0">Every account in Eventra - venue manager and server accounts are created here, and a deactivated account cannot log in.</p>
    </div>
</section>

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="d-flex flex-wrap gap-3">
            <?php foreach (AdminController::ROLE_FILTERS as $value => $label): ?>
                <?php if ($value === $role): ?>
                    <span class="fw-semibold"><?= $label ?></span>
                <?php else: ?>
                    <a href="index.php?page=admin-users&role=<?= $value ?>"><?= $label ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <a class="btn btn-eventra" href="index.php?page=admin-user">Create a staff account</a>
    </div>

    <table class="table align-middle">
        <thead>
            <tr>
                <th class="fw-normal text-muted">Name</th>
                <th class="fw-normal text-muted">Email</th>
                <th class="fw-normal text-muted">Role</th>
                <th class="fw-normal text-muted">Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $person): ?>
                <tr>
                    <td><?= e($person['FirstName'] . ' ' . $person['LastName']) ?></td>
                    <td><?= e($person['Email']) ?></td>
                    <td><?= roleName($person['Role']) ?></td>
                    <td><?= $person['IsActive'] ? 'Active' : 'Deactivated' ?></td>
                    <td class="text-end">
                        <?php if ($person['Role'] !== 'Admin'): ?>
                            <form method="post" action="index.php?page=admin-user-status">
                                <?= csrfField() ?>
                                <input type="hidden" name="user_id" value="<?= $person['UserID'] ?>">
                                <input type="hidden" name="role_filter" value="<?= $role ?>">
                                <?php if ($person['IsActive']): ?>
                                    <button type="submit" name="action" value="deactivate" class="btn btn-eventra btn-sm"
                                            onclick="return confirm('Do you want to deactivate this account?')">Deactivate</button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="activate" class="btn btn-eventra btn-sm">Activate</button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>