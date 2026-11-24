<?php
$role = $form['role'] ?? 'Manager';
$venueId = (int) ($form['venue_id'] ?? 0);
?>

<a href="index.php?page=admin-users" class="text-muted text-decoration-none small">&larr; Users</a>

<h1 class="mt-2 mb-1">New staff account</h1>
<p class="text-muted mb-4">
    Create an account for a venue manager or a server and then give them the email address and the password so they can log in.
</p>

<div class="row">
    <div class="col-lg-7">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="index.php?page=admin-user-save">
            <?= csrfField() ?>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label" for="first-name">First name</label>
                    <input class="form-control" type="text" id="first-name" name="first_name" maxlength="50" value="<?= e($form['first_name'] ?? '') ?>">
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="last-name">Last name</label>
                    <input class="form-control" type="text" id="last-name" name="last_name" maxlength="50" value="<?= e($form['last_name'] ?? '') ?>">
                </div>
            </div>

            <label class="form-label" for="email">Email address</label>
            <input class="form-control mb-3" type="email" id="email" name="email" maxlength="255" value="<?= e($form['email'] ?? '') ?>">

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password" minlength="8">
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="password-confirm">Repeat the password</label>
                    <input class="form-control" type="password" id="password-confirm" name="password_confirm" minlength="8">
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <label class="form-label" for="role">Role</label>
                    <select class="form-select" id="role" name="role">
                        <option value="Manager" <?= $role === 'Manager' ? 'selected' : '' ?>>Venue manager</option>
                        <option value="Server" <?= $role === 'Server' ? 'selected' : '' ?>>Server</option>
                    </select>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="venue">Venue (only for a venue manager)</label>
                    <select class="form-select" id="venue" name="venue_id">
                        <option value="0">No venue for now</option>
                        <?php foreach ($venues as $venue): ?>
                            <option value="<?= $venue['VenueID'] ?>" <?= $venue['VenueID'] === $venueId ? 'selected' : '' ?>>
                                <?= e($venue['Name']) ?> (now <?= e($venue['FirstName'] . ' ' . $venue['LastName']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-eventra">Create account</button>
        </form>
    </div>
</div>