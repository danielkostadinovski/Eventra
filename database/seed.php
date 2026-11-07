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
    'georgi' => $addUser('Georgi', 'Petrov', 'georgi.petrov@eventra.bg', 'Manager'),
    'maria' => $addUser('Maria', 'Dimitrova', 'maria.dimitrova@eventra.bg', 'Manager'),
    'kalin' => $addUser('Kalin', 'Stefanov', 'kalin.stefanov@eventra.bg', 'Manager'),
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

$maleNames = ['Ivan', 'Dimitar', 'Todor', 'Hristo', 'Vasil', 'Plamen', 'Lyubomir', 'Nikola', 'Boris', 'Atanas', 'Emil', 'Rumen'];
$femaleNames = ['Elena', 'Mariya', 'Viktoria', 'Gabriela', 'Silviya', 'Nadezhda', 'Ralitsa', 'Yoana', 'Kristina', 'Tsvetelina', 'Mila', 'Petya'];
$surnames = ['Ivanov', 'Dimitrov', 'Georgiev', 'Petkov', 'Angelov', 'Marinov', 'Yordanov', 'Kostov', 'Mihaylov', 'Tsvetkov', 'Rusev', 'Zhelev'];
$domains = ['gmail.com', 'abv.bg', 'mail.bg', 'yahoo.com'];

$usedEmails = [];
while (count($clients) < 40) {
    $isFemale = mt_rand(0, 1) === 1;
    $firstName = $isFemale ? $femaleNames[array_rand($femaleNames)] : $maleNames[array_rand($maleNames)];
    $lastName = $surnames[array_rand($surnames)] . ($isFemale ? 'a' : '');
    $email = strtolower($firstName . '.' . $lastName) . '@' . $domains[array_rand($domains)];

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
    ['maria', 'Skyline Lounge', 'A modern rooftop lounge in the Sofia center with a panoramic view of the city. It is amazing for birthdays, cocktail parties, and smaller wedding receptions.', 6000, 60],
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

// ---------------------------------------------------------------
// Bookings
// ------------------------------------------------------------

function chance(int $percent): bool
{
    return mt_rand(1, 100) <= $percent;
}

function pick(array $list)
{
    return $list[array_rand($list)];
}

$venueRates = $db->query('SELECT VenueID, HourlyRate FROM Venues')->fetchAll(PDO::FETCH_KEY_PAIR);
$rates = $db->query('SELECT HourlyStaffRate, FoodRatePerPerson FROM PricingSettings')->fetch();

$insertBooking = $db->prepare(
    'INSERT INTO Bookings (VenueID, ClientID, EventDate, StartTime, HoursBooked, GuestCount, StaffCount,
                           WantsFood, VenueHourlyRate, HourlyStaffRate, FoodRatePerPerson, CreatedAt)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$insertEquipment  = $db->prepare('INSERT INTO BookingEquipment (BookingID, EquipmentID, Quantity, UnitPrice) VALUES (?, ?, 1, ?)');
$updateStatus = $db->prepare('UPDATE Bookings SET Status = ? WHERE BookingID = ?');
$updateNotes = $db->prepare('UPDATE Bookings SET ConsultationNotes = ? WHERE BookingID = ?');
$insertAssignment = $db->prepare('INSERT INTO StaffAssignments (BookingID, ServerID) VALUES (?, ?)');
$acceptAssignment = $db->prepare("UPDATE StaffAssignments SET Status = 'Accepted' WHERE AssignmentID = ?");
$markPaid = $db->prepare("UPDATE Payments SET Status = 'Paid', PaidAt = ? WHERE BookingID = ?");
$insertReview = $db->prepare('INSERT INTO Reviews (BookingID, Rating, Comment, CreatedAt) VALUES (?, ?, ?, ?)');

$notes = [
    'Consultation happened through a call and the client confirmed their seating plan and the menu.',
    'We met with the client at the venue and agreed on all the decoration and the arrival time of the guests.',
    'The client asked for a vegetarian option for some of the guests (which I discussed with the kitchen later too)',
    'We had a short call with the client - they said that the guest list is final and the music will be arranged by their DJ.',
    'I gave a tour of the venue to the client - they said that they would prefer if the tables were arranged in a u-shape though.',
    'The client said they want a small stage to give speeches. We also agreed to keep the dance floor free until 21:00.',
    'We had a consultation through email: the client sent us the final timeline for the evening.',
    'We discussed parking for the guests and a quiet room for older family members (they also said that some of them will need special assistance).',
];

$goodComments = [
    'Everything was perfect!!! from the food to the service! Our friends are still talking about it when they see us :)',
    'Such a beautiful venue and such a helpful and amazing team. Our venue manager took care of every single detail!!',
    'We celebrated our wedding here and we simply could not have asked for more! Thank you!',
    'Such a great atmosphere and the team and staff were so professional! We will definitely be back for another occasion!',
    'The booking was super easy, the price was very clear from the start, and the evening went super smoothly!',
    'A wonderful place for a birthday party! We all had a great time!!',
    'The venue looked even better than in the photos! Highly recommended.',
    'Very well organised. The consultation helped us plan every part of the evening.'
];

$badComments = [
    'The venue was fine at most, but the food arrived late and some dishes were cold!!',
    'Not what we expected... the sound system had problems for most of the night and the food was room temperature too.',
    'The staff were polite but there were not enough waiters for so many guests honestly....',
    'Nice place, but the music was too loud for the older guests, my grandparents had to leave early because of it..',
    'The evening was okay but the parking was very difficult.',
];

$today = date('Y-m-d');
$booked = [];  
$serverBusy = [];   
$bookings = 0;

while ($bookings < 500) {

    // it picks a random venue and a date ( 80% of the time are in the last 300 days and 20% in the next 120 days)
    $venue = pick($venues);
    $days = chance(80) ? -mt_rand(1, 300) : mt_rand(1, 120);
    $date = date('Y-m-d', strtotime("$days days"));
    $key = $venue['id'] . '|' . $date;

    if (isset($booked[$key]) || isset($holidays[$date])) {
        continue;
    }

    // client's choices
    $guests = mt_rand(intdiv($venue['capacity'], 2), $venue['capacity']);
    $start = pick(['13:00', '18:00', '19:00', '20:00']);
    $hours = pick([3, 4]);
    $staff = chance(70) ? intdiv($guests, 40) + 1 : 0;
    $food = chance(70) ? 1 : 0;
    $created = date('Y-m-d H:i:s', min(strtotime("$date -30 days"), time()));

    $insertBooking->execute([
        $venue['id'], pick($clients), $date, $start, $hours, $guests, $staff, $food,
        $venueRates[$venue['id']], $rates['HourlyStaffRate'], $rates['FoodRatePerPerson'], $created,
    ]);
    $bookingId = (int) $db->lastInsertId();
    $booked[$key] = true;
    $bookings++;

    // adds equipment (only can be added if the booking is pending tho)
    if (chance(40)) {
        $item = pick($equipment);
        $insertEquipment->execute([$bookingId, $item['id'], $item['price']]);
    }

    // decides the final status (past events are completed or cancelled and future ones are open)
    if ($date < $today) {
        $final = chance(90) ? 'Completed' : 'Cancelled';
    } else {
        $final = pick(['Pending', 'Approved', 'Confirmed', 'Cancelled']);
    }

    // moves the booking through its statuses in order (the triggers dont allow a status to be skipped)
    if ($final === 'Approved' || $final === 'Confirmed' || $final === 'Completed') {
        $updateStatus->execute(['Approved', $bookingId]);
        $updateNotes->execute([pick($notes), $bookingId]);
    }

    if ($final === 'Confirmed' || $final === 'Completed') {
        $updateStatus->execute(['Confirmed', $bookingId]);   //this also creates the unpaid payment

        // assigns free servers (each server can work only once a day)
        $candidates = $servers;
        shuffle($candidates);
        $assigned = 0;
        foreach ($candidates as $serverId) {
            if ($assigned === $staff) {
                break;
            }
            if (isset($serverBusy[$date][$serverId])) {
                continue;
            }
            $insertAssignment->execute([$bookingId, $serverId]);
            $acceptAssignment->execute([(int) $db->lastInsertId()]);
            $serverBusy[$date][$serverId] = true;
            $assigned++;
        }
    }

    if ($final === 'Completed') {
        $updateStatus->execute(['Completed', $bookingId]);

        if (chance(95)) {
            $markPaid->execute([$date . ' 12:00:00', $bookingId]);
        }

        if (chance(60)) {
            $rating = pick([5, 5, 5, 4, 4, 3, 2]);
            $comment = $rating >= 4 ? pick($goodComments) : pick($badComments);
            $insertReview->execute([$bookingId, $rating, $comment, $date . ' 23:30:00']);
        }
    }

    if ($final === 'Cancelled') {
        $updateStatus->execute(['Cancelled', $bookingId]);
    }
}

$db->commit();

echo 'Users: ' . (1 + count($managers) + count($servers) + count($clients)) . "\n";
echo 'Venues: ' . count($venues) . "\n";
echo 'Equipment: ' . count($equipment) . "\n";
echo 'Blackout dates: ' . count($venues) * count($holidays) . "\n";
echo 'Bookings: ' . $bookings . "\n";

foreach ($db->query('SELECT Status, COUNT(*) AS Total FROM Bookings GROUP BY Status') as $row) {
    echo '  ' . $row['Status'] . ': ' . $row['Total'] . "\n";
}
echo 'Payments: ' . $db->query('SELECT COUNT(*) FROM Payments')->fetchColumn() . "\n";
echo 'Server assignments: ' . $db->query('SELECT COUNT(*) FROM StaffAssignments')->fetchColumn() . "\n";
echo 'Reviews: ' . $db->query('SELECT COUNT(*) FROM Reviews')->fetchColumn() . "\n";