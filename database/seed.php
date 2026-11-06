<?php

require __DIR__ . '/../config/db.php';

$db = db();

if ((int) $db->query('SELECT COUNT(*) FROM Users')->fetchColumn() > 0) {
    exit("The database already contains data. Rebuild it first.\n");
}

mt_srand(2026);

$db->beginTransaction();

// ---------------------------------------------------------------
// Users
// ---------------------------------------------------------------

$hash = password_hash('Eventra2026!', PASSWORD_BCRYPT);

$insertUser = $db->prepare(
    'INSERT INTO Users (FirstName, LastName, Email, PasswordHash, Role, CreatedAt)
     VALUES (?, ?, ?, ?, ?, ?)'
);

$addUser = function (string $firstName, string $lastName, string $email, string $role) use ($db, $insertUser, $hash): int {
    $insertUser->execute([$firstName, $lastName, $email, $hash, $role, '2025-09-01 09:00:00']);
    return (int) $db->lastInsertId();
};

$adminId = $addUser('Daniela', 'Koleva', 'daniela.koleva@eventra.bg', 'Admin');

$managers = [
    'georgi'    => $addUser('Georgi', 'Petrov', 'georgi.petrov@eventra.bg', 'Manager'),
    'maria'     => $addUser('Maria', 'Dimitrova', 'maria.dimitrova@eventra.bg', 'Manager'),
    'kalin'     => $addUser('Kalin', 'Stefanov', 'kalin.stefanov@eventra.bg', 'Manager'),
    'radostina' => $addUser('Radostina', 'Pavlova', 'radostina.pavlova@eventra.bg', 'Manager'),
];

$servers = [
    $addUser('Nikolay', 'Ivanov', 'nikolay.ivanov@eventra.bg', 'Server'),
    $addUser('Teodora', 'Stoyanova', 'teodora.stoyanova@eventra.bg', 'Server'),
    $addUser('Stefan', 'Todorov', 'stefan.todorov@eventra.bg', 'Server'),
    $addUser('Martin', 'Kolev', 'martin.kolev@eventra.bg', 'Server'),
    $addUser('Yana', 'Hristova', 'yana.hristova@eventra.bg', 'Server'),
    $addUser('Bozhidar', 'Popov', 'bozhidar.popov@eventra.bg', 'Server'),
    $addUser('Simona', 'Atanasova', 'simona.atanasova@eventra.bg', 'Server'),
    $addUser('Kristian', 'Georgiev', 'kristian.georgiev@eventra.bg', 'Server'),
    $addUser('Gergana', 'Vasileva', 'gergana.vasileva@eventra.bg', 'Server'),
    $addUser('Aleksandar', 'Iliev', 'aleksandar.iliev@eventra.bg', 'Server'),
];

$clients = [
    $addUser('Ivana', 'Georgieva', 'ivana.georgieva@abv.bg', 'Client'),
    $addUser('Petar', 'Nikolov', 'petar.nikolov@gmail.com', 'Client'),
    $addUser('Desislava', 'Hristova', 'desislava.hristova@abv.bg', 'Client'),
];

$maleNames   = ['Ivan', 'Dimitar', 'Todor', 'Hristo', 'Vasil', 'Plamen', 'Lyubomir', 'Nikola', 'Boris', 'Atanas', 'Emil', 'Rumen'];
$femaleNames = ['Elena', 'Mariya', 'Viktoria', 'Gabriela', 'Silviya', 'Nadezhda', 'Ralitsa', 'Yoana', 'Kristina', 'Tsvetelina', 'Mila', 'Petya'];
$surnames    = ['Ivanov', 'Dimitrov', 'Georgiev', 'Petkov', 'Angelov', 'Marinov', 'Yordanov', 'Kostov', 'Mihaylov', 'Tsvetkov', 'Rusev', 'Zhelev'];
$domains     = ['gmail.com', 'abv.bg', 'mail.bg', 'yahoo.com'];

$usedEmails = [];
while (count($clients) < 40) {
    $isFemale  = mt_rand(0, 1) === 1;
    $firstName = $isFemale ? $femaleNames[array_rand($femaleNames)] : $maleNames[array_rand($maleNames)];
    $lastName  = $surnames[array_rand($surnames)] . ($isFemale ? 'a' : '');
    $email     = strtolower($firstName . '.' . $lastName) . '@' . $domains[array_rand($domains)];

    if (isset($usedEmails[$email])) {
        continue;
    }
    $usedEmails[$email] = true;
    $clients[] = $addUser($firstName, $lastName, $email, 'Client');
}

// ---------------------------------------------------------------
// Venues (the rates are in euro cents)
// ---------------------------------------------------------------

$insertVenue = $db->prepare(
    'INSERT INTO Venues (ManagerID, Name, Description, HourlyRate, MaxCapacity)
     VALUES (?, ?, ?, ?, ?)'
);

$venueList = [
    ['georgi', 'Vitosha Garden', 'A green garden at the beginning of Vitosha mountain in Sofia, with a covered terrace and string lights - a favourite venue for summer weddings and family celebrations.', 9000, 150],
    ['georgi', 'Serdika Grand Hall', 'An elegant hall in Sofia with crystal chandeliers, a stage, and a dance floor. It is great for weddings, galas and large corporate dinners.', 10000, 250],
    ['maria', 'Skyline Lounge', 'Skyline Lounge is a modern rooftop lounge in Sofia, that has a panoramic view of the city. It is a perfect choice for birthdays, cocktail parties, and smaller wedding receptions.', 6000, 60],
    ['maria', 'Boyana Villa', 'A restored villa with a private garden in the quiet Boyana district of Sofia. Perfect for intimate weddings, christenings and family lunches.', 9500, 100],
    ['maria', 'Lozenets Loft', 'An industrial-style loft in the Lozenets district of Sofia with an open floor plan and great views. Popular for graduation parties and company events.', 5500, 50],
    ['kalin', 'Pancharevo Lake House', 'A lakeside house by Pancharevo lake on the edge of Sofia, with a wooden deck right by the water. A lovely setting for weddings and summer receptions.', 8000, 120],
    ['kalin', 'Oborishte Salon', 'A classic salon with high ceilings in the Oborishte district of Sofia. Great for anniversaries, receptions and conferences.', 6500, 110],
    ['kalin', 'Dragalevtsi Lodge', 'A wooden lodge in Dragalevtsi, at the foot of Vitosha, with a fireplace and a terrace facing the mountain. Ideal for winter celebrations and team buildings.', 7000, 80],
    ['radostina', 'Borisova Garden Pavilion', 'A glass pavilion in Borisova Gradina, the oldest park in Sofia, surrounded by trees. A popular choice for summer weddings and graduation parties.', 11000, 180],
    ['radostina', 'Mladost Event Center', 'A modern event hall in the Mladost business district of Sofia, with a stage and built-in sound and lighting. Suited for corporate events, birthdays and graduation parties.', 8500, 140],
];

$venues = [];
foreach ($venueList as [$manager, $name, $description, $rate, $capacity]) {
    $insertVenue->execute([$managers[$manager], $name, $description, $rate, $capacity]);
    $venues[] = ['id' => (int) $db->lastInsertId(), 'capacity' => $capacity];
}

// ---------------------------------------------------------------
//  Equipment (price per unit per booking in euro cents)
// ---------------------------------------------------------------

$insertEquipment = $db->prepare('INSERT INTO Equipment (Name, UnitPrice) VALUES (?, ?)');

$equipmentList = [
    ['Projector and screen', 4000],
    ['Sound system', 6000],
    ['Wireless microphone', 1500],
    ['Stage lighting', 8000],
    ['LED dance floor', 15000],
    ['Photo booth', 12000],
];

$equipment = [];
foreach ($equipmentList as [$name, $price]) {
    $insertEquipment->execute([$name, $price]);
    $equipment[] = ['id' => (int) $db->lastInsertId(), 'price' => $price];
}

// ---------------------------------------------------------------
//   Blackout dates 
// ---------------------------------------------------------------

$insertBlackout = $db->prepare('INSERT INTO BlackoutDates (VenueID, BlackoutDate, Reason) VALUES (?, ?, ?)');

$holidays = [
    '2025-12-24' => 'Christmas Eve',
    '2025-12-25' => 'Christmas Day',
    '2026-01-01' => "New Year's Day",
    '2026-04-12' => 'Easter Sunday',
    '2026-12-24' => 'Christmas Eve',
    '2026-12-25' => 'Christmas Day',
    '2027-01-01' => "New Year's Day",
];

foreach ($venues as $venue) {
    foreach ($holidays as $date => $reason) {
        $insertBlackout->execute([$venue['id'], $date, $reason]);
    }
}

$db->commit();

echo 'Users: ' . (1 + count($managers) + count($servers) + count($clients)) . "\n";
echo 'Venues: ' . count($venues) . "\n";
echo 'Equipment: ' . count($equipment) . "\n";
echo 'Blackout dates: ' . count($venues) * count($holidays) . "\n";