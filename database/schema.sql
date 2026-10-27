PRAGMA foreign_keys = ON;

CREATE TABLE Users (
    UserID INTEGER PRIMARY KEY,
    FirstName TEXT NOT NULL CHECK (length(trim(FirstName)) BETWEEN 1 AND 50),
    LastName TEXT NOT NULL CHECK (length(trim(LastName)) BETWEEN 1 AND 50),
    Email TEXT NOT NULL COLLATE NOCASE UNIQUE CHECK (length(Email) <= 255 AND Email LIKE '_%@_%._%'),
    PasswordHash TEXT NOT NULL CONSTRAINT CK_Users_BcryptHash CHECK (length(PasswordHash) = 60 AND PasswordHash GLOB '$2y$[0-9][0-9]$*'),
    Role TEXT NOT NULL CHECK (Role IN ('Client', 'Manager', 'Server', 'Admin')),
    IsActive INTEGER NOT NULL DEFAULT 1 CHECK (IsActive IN (0, 1)),
    CreatedAt TEXT NOT NULL DEFAULT (datetime('now', 'localtime')) CHECK (CreatedAt IS datetime(CreatedAt))
) STRICT;

CREATE TABLE Venues (
    VenueID INTEGER PRIMARY KEY,
    ManagerID INTEGER NOT NULL REFERENCES Users (UserID) ON DELETE RESTRICT,
    Name TEXT NOT NULL COLLATE NOCASE UNIQUE CHECK (length(trim(Name)) BETWEEN 1 AND 100),
    Description TEXT NOT NULL CHECK (length(trim(Description)) > 0), 
    HourlyRate INTEGER NOT NULL CHECK (HourlyRate > 0),
    MaxCapacity INTEGER NOT NULL CHECK (MaxCapacity > 0),
    IsActive INTEGER NOT NULL DEFAULT 1 CHECK (IsActive IN (0, 1))
) STRICT;

CREATE INDEX IX_Venues_Manager ON Venues (ManagerID);

CREATE TABLE VenuePhotos (
    PhotoID INTEGER PRIMARY KEY,
    VenueID INTEGER NOT NULL REFERENCES Venues (VenueID) ON DELETE CASCADE,
    FilePath TEXT NOT NULL UNIQUE CHECK (length(FilePath) BETWEEN 1 AND 255)
) STRICT;

CREATE INDEX IX_VenuePhotos_Venue ON VenuePhotos (VenueID);

CREATE TABLE BlackoutDates (
    BlackoutID INTEGER PRIMARY KEY,
    VenueID INTEGER NOT NULL REFERENCES Venues (VenueID) ON DELETE CASCADE,
    BlackoutDate TEXT NOT NULL CHECK (BlackoutDate IS date(BlackoutDate)),
    Reason TEXT NOT NULL CHECK (length(trim(Reason)) BETWEEN 1 AND 200),
    UNIQUE (VenueID, BlackoutDate)
) STRICT;

CREATE TABLE Equipment (
    EquipmentID INTEGER PRIMARY KEY,
    Name TEXT NOT NULL COLLATE NOCASE UNIQUE CHECK (length(trim(Name)) BETWEEN 1 AND 100),
    UnitPrice INTEGER NOT NULL CHECK (UnitPrice >= 0),
    IsActive INTEGER NOT NULL DEFAULT 1 CHECK (IsActive IN (0, 1))
) STRICT;

CREATE TABLE PricingSettings (
    SettingID INTEGER PRIMARY KEY CHECK (SettingID = 1),
    HourlyStaffRate INTEGER NOT NULL CHECK (HourlyStaffRate >= 0),
    FoodRatePerPerson INTEGER NOT NULL CHECK (FoodRatePerPerson >= 0)
) STRICT;

INSERT INTO PricingSettings (SettingID, HourlyStaffRate, FoodRatePerPerson) VALUES (1, 500, 2000);

CREATE TABLE Bookings (
    BookingID INTEGER PRIMARY KEY,
    VenueID INTEGER NOT NULL REFERENCES Venues (VenueID) ON DELETE RESTRICT,
    ClientID INTEGER NOT NULL REFERENCES Users (UserID) ON DELETE RESTRICT,
    EventDate TEXT NOT NULL CHECK (EventDate IS date(EventDate)),
    StartTime TEXT NOT NULL CHECK (StartTime IS strftime('%H:%M', StartTime)),
    HoursBooked INTEGER NOT NULL CHECK (HoursBooked BETWEEN 1 AND 12),
    GuestCount INTEGER NOT NULL CHECK (GuestCount >= 1),
    StaffCount INTEGER NOT NULL DEFAULT 0 CHECK (StaffCount BETWEEN 0 AND 25),
    WantsFood INTEGER NOT NULL DEFAULT 0 CHECK (WantsFood IN (0, 1)),
    VenueHourlyRate INTEGER NOT NULL CHECK (VenueHourlyRate > 0),
    HourlyStaffRate INTEGER NOT NULL CHECK (HourlyStaffRate >= 0),
    FoodRatePerPerson INTEGER NOT NULL CHECK (FoodRatePerPerson >= 0),
    Status TEXT NOT NULL DEFAULT 'Pending' CHECK (Status IN ('Pending', 'Approved', 'Confirmed', 'Completed', 'Cancelled')),
    ConsultationNotes TEXT CHECK (ConsultationNotes IS NULL OR length(trim(ConsultationNotes)) > 0),
    AttachmentPath TEXT CHECK (AttachmentPath IS NULL OR length(AttachmentPath) BETWEEN 1 AND 255),
    CreatedAt TEXT NOT NULL DEFAULT (datetime('now', 'localtime')) CHECK (CreatedAt IS datetime(CreatedAt)),
    CONSTRAINT CK_Bookings_FutureDate CHECK (EventDate > date(CreatedAt)),
    CONSTRAINT CK_Bookings_EndsByMidnight CHECK (CAST(substr(StartTime, 1, 2) AS INTEGER) * 60 + CAST(substr(StartTime, 4, 2) AS INTEGER) + HoursBooked * 60 <= 1440)
) STRICT;

CREATE UNIQUE INDEX UX_Bookings_Venue_Date_Active ON Bookings (VenueID, EventDate) WHERE Status <> 'Cancelled';
CREATE INDEX IX_Bookings_Venue ON Bookings (VenueID);
CREATE INDEX IX_Bookings_Client ON Bookings (ClientID);

CREATE TABLE BookingEquipment (
    BookingID INTEGER NOT NULL REFERENCES Bookings (BookingID) ON DELETE CASCADE,
    EquipmentID INTEGER NOT NULL REFERENCES Equipment (EquipmentID) ON DELETE RESTRICT,
    Quantity INTEGER NOT NULL CHECK (Quantity >= 1),
    UnitPrice INTEGER NOT NULL CHECK (UnitPrice >= 0),
    PRIMARY KEY (BookingID, EquipmentID)
) STRICT;

CREATE INDEX IX_BookingEquipment_Equipment ON BookingEquipment (EquipmentID);

CREATE TABLE StaffAssignments (
    AssignmentID INTEGER PRIMARY KEY,
    BookingID INTEGER NOT NULL REFERENCES Bookings (BookingID) ON DELETE RESTRICT,
    ServerID INTEGER NOT NULL REFERENCES Users (UserID) ON DELETE RESTRICT,
    Status TEXT NOT NULL DEFAULT 'Pending' CHECK (Status IN ('Pending', 'Accepted', 'Declined')),
    UNIQUE (BookingID, ServerID)
) STRICT;

CREATE INDEX IX_StaffAssignments_Server ON StaffAssignments (ServerID);

CREATE TABLE Payments (
    BookingID INTEGER NOT NULL PRIMARY KEY REFERENCES Bookings (BookingID) ON DELETE RESTRICT,
    Status TEXT NOT NULL DEFAULT 'Unpaid' CHECK (Status IN ('Unpaid', 'Paid', 'Refunded')),
    PaidAt TEXT CHECK (PaidAt IS NULL OR PaidAt IS datetime(PaidAt)),
    CONSTRAINT CK_Payments_PaidAt CHECK ((Status = 'Unpaid') = (PaidAt IS NULL))
) STRICT, WITHOUT ROWID;

CREATE TABLE Reviews (
    BookingID INTEGER NOT NULL PRIMARY KEY REFERENCES Bookings (BookingID) ON DELETE RESTRICT,
    Rating INTEGER NOT NULL CHECK (Rating BETWEEN 1 AND 5),
    Comment TEXT NOT NULL CHECK (length(trim(Comment)) > 0),
    CreatedAt TEXT NOT NULL DEFAULT (datetime('now', 'localtime')) CHECK (CreatedAt IS datetime(CreatedAt))
) STRICT, WITHOUT ROWID;

CREATE VIEW BookingSchedule AS
SELECT
    t.BookingID,
    t.VenueID,
    t.EventDate,
    t.Status,
    t.StartTime,
    printf('%02d:%02d', t.EndMinute / 60, t.EndMinute % 60) AS EndTime,
    t.StartMinute,
    t.EndMinute
FROM (
    SELECT BookingID, VenueID, EventDate, Status, StartTime,
        CAST(substr(StartTime, 1, 2) AS INTEGER) * 60 + CAST(substr(StartTime, 4, 2) AS INTEGER) AS StartMinute,
        CAST(substr(StartTime, 1, 2) AS INTEGER) * 60 + CAST(substr(StartTime, 4, 2) AS INTEGER) + HoursBooked * 60 AS EndMinute
    FROM Bookings
) AS t;

CREATE VIEW BookingPrices AS
SELECT
    b.BookingID,
    b.VenueHourlyRate * b.HoursBooked AS VenueCost,
    b.StaffCount * b.HourlyStaffRate * b.HoursBooked AS StaffCost,
    b.WantsFood * b.GuestCount * b.FoodRatePerPerson AS FoodCost,
    COALESCE(e.EquipmentCost, 0) AS EquipmentCost,
    b.VenueHourlyRate * b.HoursBooked
        + b.StaffCount * b.HourlyStaffRate * b.HoursBooked
        + b.WantsFood * b.GuestCount * b.FoodRatePerPerson
        + COALESCE(e.EquipmentCost, 0) AS TotalPrice
FROM Bookings AS b
LEFT JOIN (
    SELECT BookingID, SUM(Quantity * UnitPrice) AS EquipmentCost
    FROM BookingEquipment
    GROUP BY BookingID
) AS e ON e.BookingID = b.BookingID;

CREATE VIEW ServerEarnings AS
SELECT
    sa.AssignmentID,
    sa.ServerID,
    b.BookingID,
    b.VenueID,
    b.EventDate,
    b.Status AS BookingStatus,
    b.HoursBooked AS HoursWorked,
    b.HourlyStaffRate,
    b.HoursBooked * b.HourlyStaffRate AS Earnings
FROM StaffAssignments AS sa
JOIN Bookings AS b ON b.BookingID = sa.BookingID
WHERE sa.Status = 'Accepted';

CREATE TRIGGER TRG_Users_BeforeUpdate
BEFORE UPDATE ON Users
BEGIN
    SELECT RAISE(ABORT, 'A user''s role cannot be changed after the account is created')
    WHERE NEW.Role IS NOT OLD.Role;

    SELECT RAISE(ABORT, 'Administrator accounts cannot be deactivated')
    WHERE NEW.IsActive = 0 AND OLD.IsActive = 1 AND OLD.Role = 'Admin';

    SELECT RAISE(ABORT, 'Reassign this manager''s venues before deactivating the account')
    WHERE NEW.IsActive = 0 AND OLD.IsActive = 1
      AND EXISTS (SELECT 1 FROM Venues WHERE ManagerID = OLD.UserID AND IsActive = 1);
END;

CREATE TRIGGER TRG_Venues_BeforeInsert
BEFORE INSERT ON Venues
BEGIN
    SELECT RAISE(ABORT, 'ManagerID must reference a user with the Manager role')
    WHERE NOT EXISTS (SELECT 1 FROM Users WHERE UserID = NEW.ManagerID AND Role = 'Manager');

    SELECT RAISE(ABORT, 'An active venue must be assigned to an active manager')
    WHERE NEW.IsActive = 1
      AND NOT EXISTS (SELECT 1 FROM Users WHERE UserID = NEW.ManagerID AND IsActive = 1);
END;

CREATE TRIGGER TRG_Venues_BeforeUpdate
BEFORE UPDATE ON Venues
BEGIN
    SELECT RAISE(ABORT, 'ManagerID must reference a user with the Manager role')
    WHERE NOT EXISTS (SELECT 1 FROM Users WHERE UserID = NEW.ManagerID AND Role = 'Manager');

    SELECT RAISE(ABORT, 'An active venue must be assigned to an active manager')
    WHERE NEW.IsActive = 1
      AND NOT EXISTS (SELECT 1 FROM Users WHERE UserID = NEW.ManagerID AND IsActive = 1);

    SELECT RAISE(ABORT, 'Resolve this venue''s open bookings before deactivating it')
    WHERE NEW.IsActive = 0 AND OLD.IsActive = 1
      AND EXISTS (SELECT 1 FROM Bookings
                  WHERE VenueID = OLD.VenueID AND Status IN ('Pending', 'Approved', 'Confirmed'));

    SELECT RAISE(ABORT, 'Capacity cannot be lowered below the guest count of an open booking')
    WHERE NEW.MaxCapacity < OLD.MaxCapacity
      AND EXISTS (SELECT 1 FROM Bookings
                  WHERE VenueID = OLD.VenueID AND Status IN ('Pending', 'Approved', 'Confirmed')
                    AND GuestCount > NEW.MaxCapacity);
END;

CREATE TRIGGER TRG_PricingSettings_BeforeDelete
BEFORE DELETE ON PricingSettings
BEGIN
    SELECT RAISE(ABORT, 'The pricing settings row cannot be deleted; update it instead');
END;

CREATE TRIGGER TRG_BlackoutDates_BeforeInsert
BEFORE INSERT ON BlackoutDates
BEGIN
    SELECT RAISE(ABORT, 'This venue already has a booking on that date')
    WHERE EXISTS (SELECT 1 FROM Bookings
                  WHERE VenueID = NEW.VenueID AND EventDate = NEW.BlackoutDate AND Status <> 'Cancelled');
END;

CREATE TRIGGER TRG_BlackoutDates_BeforeUpdate
BEFORE UPDATE OF VenueID, BlackoutDate ON BlackoutDates
BEGIN
    SELECT RAISE(ABORT, 'This venue already has a booking on that date')
    WHERE EXISTS (SELECT 1 FROM Bookings
                  WHERE VenueID = NEW.VenueID AND EventDate = NEW.BlackoutDate AND Status <> 'Cancelled');
END;