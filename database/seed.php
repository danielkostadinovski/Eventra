<?php

require __DIR__ . '/../config/db.php';

$password = 'Eventra2026!';

$users = [
    ['Daniela', 'Koleva', 'daniela.koleva@eventra.bg', 'Admin'],
    ['Georgi', 'Petrov', 'georgi.petrov@eventra.bg', 'Manager'],
    ['Maria', 'Dimitrova', 'maria.dimitrova@eventra.bg', 'Manager'],
    ['Nikolay', 'Ivanov', 'nikolay.ivanov@eventra.bg', 'Server'],
    ['Teodora', 'Stoyanova', 'teodora.stoyanova@eventra.bg', 'Server'],
    ['Stefan', 'Todorov', 'stefan.todorov@eventra.bg', 'Server'],
    ['Ivana', 'Georgieva', 'ivana.georgieva@abv.bg', 'Client'],
    ['Petar', 'Nikolov', 'petar.nikolov@gmail.com', 'Client'],
    ['Desislava', 'Hristova', 'desislava.hristova@abv.bg', 'Client'],
];

$venues = [
    ['georgi.petrov@eventra.bg', 'Vitosha Garden',
        'A landscaped garden in the heart of Vitosha with a covered terrace, lights, and room for a dance floor. It is very popular for summer weddings, family celebrations, and birthdays.',
        9000, 150],
    ['georgi.petrov@eventra.bg', 'Serdika Grand Hall',
        'An elegant banquet hall in the heart of Sofia with crystal chandeliers, a stage, a bar, and a dining area. It is mostly popular for weddings, graduation balls and corporate dinners.',
        10000, 250],
    ['maria.dimitrova@eventra.bg', 'Skyline Lounge',
        'A rooftop lounge in Lozenets with a panoramic city view, a cocktail bar, and lounge seating. Ideal for birthdays, engagement parties and after-work events.',
        6000, 60],
];

$equipment = [
    ['Projector and screen', 2000],
    ['Sound system with DJ console', 7000],
    ['Wireless microphone', 1000],
    ['LED dance floor', 23000],
    ['Photo booth', 15000],
];

if (db()->query('SELECT COUNT(*) FROM Users')->fetchColumn() > 0) {
    echo 'The database already has data - to start over run: rm -f database/eventra.db* && php database/create_db.php && php database/seed.php' . PHP_EOL;
    exit(1);
}

try {
    db()->beginTransaction();

    $insertUser = db()->prepare(
        'INSERT INTO Users (FirstName, LastName, Email, PasswordHash, Role) VALUES (?, ?, ?, ?, ?)'
    );
    $userIds = [];
    foreach ($users as [$firstName, $lastName, $email, $role]) {
        $insertUser->execute([$firstName, $lastName, $email, password_hash($password, PASSWORD_BCRYPT), $role]);
        $userIds[$email] = (int) db()->lastInsertId();
    }

    $insertVenue = db()->prepare(
        'INSERT INTO Venues (ManagerID, Name, Description, HourlyRate, MaxCapacity) VALUES (?, ?, ?, ?, ?)'
    );
    foreach ($venues as [$managerEmail, $name, $description, $hourlyRate, $maxCapacity]) {
        $insertVenue->execute([$userIds[$managerEmail], $name, $description, $hourlyRate, $maxCapacity]);
    }

    $insertEquipment = db()->prepare('INSERT INTO Equipment (Name, UnitPrice) VALUES (?, ?)');
    foreach ($equipment as [$name, $unitPrice]) {
        $insertEquipment->execute([$name, $unitPrice]);
    }

    db()->commit();
} catch (PDOException $e) {
    db()->rollBack();
    echo 'Seeding failed: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo 'Seeded ' . count($users) . ' users, ' . count($venues) . ' venues and '
    . count($equipment) . ' equipment items.' . PHP_EOL;
echo "Every demo account uses the password: $password" . PHP_EOL;