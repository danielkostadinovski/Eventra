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

CREATE TRIGGER TRG_Bookings_BeforeInsert
BEFORE INSERT ON Bookings
BEGIN
    SELECT RAISE(ABORT, 'New bookings must start in the Pending status')
    WHERE NEW.Status IS NOT 'Pending';

    SELECT RAISE(ABORT, 'ClientID must reference an active user with the Client role')
    WHERE NOT EXISTS (SELECT 1 FROM Users
                      WHERE UserID = NEW.ClientID AND Role = 'Client' AND IsActive = 1);

    SELECT RAISE(ABORT, 'This venue is not available for booking')
    WHERE NOT EXISTS (SELECT 1 FROM Venues WHERE VenueID = NEW.VenueID AND IsActive = 1);

    SELECT RAISE(ABORT, 'Guest count exceeds the venue''s maximum capacity')
    WHERE NEW.GuestCount > (SELECT MaxCapacity FROM Venues WHERE VenueID = NEW.VenueID);

    SELECT RAISE(ABORT, 'The venue is blacked out on this date')
    WHERE EXISTS (SELECT 1 FROM BlackoutDates
                  WHERE VenueID = NEW.VenueID AND BlackoutDate = NEW.EventDate);

    SELECT RAISE(ABORT, 'Captured rates must equal the rates in force at submission')
    WHERE NEW.VenueHourlyRate IS NOT (SELECT HourlyRate FROM Venues WHERE VenueID = NEW.VenueID)
       OR NEW.HourlyStaffRate IS NOT (SELECT HourlyStaffRate FROM PricingSettings WHERE SettingID = 1)
       OR NEW.FoodRatePerPerson IS NOT (SELECT FoodRatePerPerson FROM PricingSettings WHERE SettingID = 1);
END;

CREATE TRIGGER TRG_Bookings_BeforeUpdate
BEFORE UPDATE ON Bookings
BEGIN
    SELECT RAISE(ABORT, 'Booking details and captured rates cannot be changed after submission')
    WHERE NEW.BookingID IS NOT OLD.BookingID
       OR NEW.VenueID IS NOT OLD.VenueID
       OR NEW.ClientID IS NOT OLD.ClientID
       OR NEW.EventDate IS NOT OLD.EventDate
       OR NEW.HoursBooked IS NOT OLD.HoursBooked
       OR NEW.GuestCount IS NOT OLD.GuestCount
       OR NEW.StaffCount IS NOT OLD.StaffCount
       OR NEW.WantsFood IS NOT OLD.WantsFood
       OR NEW.VenueHourlyRate IS NOT OLD.VenueHourlyRate
       OR NEW.HourlyStaffRate IS NOT OLD.HourlyStaffRate
       OR NEW.FoodRatePerPerson IS NOT OLD.FoodRatePerPerson
       OR NEW.CreatedAt IS NOT OLD.CreatedAt;

    SELECT RAISE(ABORT, 'The start time can only change before the booking is Confirmed')
    WHERE NEW.StartTime IS NOT OLD.StartTime
      AND OLD.Status NOT IN ('Pending', 'Approved');

    SELECT RAISE(ABORT, 'Invalid booking status change')
    WHERE NEW.Status IS NOT OLD.Status
      AND NOT (   (OLD.Status = 'Pending'   AND NEW.Status IN ('Approved', 'Cancelled'))
               OR (OLD.Status = 'Approved'  AND NEW.Status IN ('Confirmed', 'Cancelled'))
               OR (OLD.Status = 'Confirmed' AND NEW.Status IN ('Completed', 'Cancelled')));

    SELECT RAISE(ABORT, 'A booking can only be marked Completed on or after its event date')
    WHERE NEW.Status = 'Completed' AND OLD.Status IS NOT 'Completed'
      AND NEW.EventDate > date('now', 'localtime');
END;

CREATE TRIGGER TRG_Bookings_AfterConfirm
AFTER UPDATE OF Status ON Bookings
WHEN NEW.Status = 'Confirmed' AND OLD.Status IS NOT 'Confirmed'
BEGIN
    INSERT INTO Payments (BookingID) VALUES (NEW.BookingID);
END;

CREATE TRIGGER TRG_BookingEquipment_BeforeInsert
BEFORE INSERT ON BookingEquipment
BEGIN
    SELECT RAISE(ABORT, 'Equipment can only be added while the booking is Pending')
    WHERE (SELECT Status FROM Bookings WHERE BookingID = NEW.BookingID) IS NOT 'Pending';

    SELECT RAISE(ABORT, 'This equipment item is no longer offered')
    WHERE NOT EXISTS (SELECT 1 FROM Equipment WHERE EquipmentID = NEW.EquipmentID AND IsActive = 1);

    SELECT RAISE(ABORT, 'Captured unit price should equal the current catalog price')
    WHERE NEW.UnitPrice IS NOT (SELECT UnitPrice FROM Equipment WHERE EquipmentID = NEW.EquipmentID);
END;

CREATE TRIGGER TRG_BookingEquipment_BeforeUpdate
BEFORE UPDATE ON BookingEquipment
BEGIN
    SELECT RAISE(ABORT, 'Booked equipment cannot be changed after submission');
END;

CREATE TRIGGER TRG_BookingEquipment_BeforeDelete
BEFORE DELETE ON BookingEquipment
BEGIN
    SELECT RAISE(ABORT, 'Booked equipment cannot be removed after submission')
    WHERE (SELECT Status FROM Bookings WHERE BookingID = OLD.BookingID)
          IN ('Approved', 'Confirmed', 'Completed', 'Cancelled');
END;

CREATE TRIGGER TRG_StaffAssignments_BeforeInsert
BEFORE INSERT ON StaffAssignments
BEGIN
    SELECT RAISE(ABORT, 'ServerID must reference an active user with the Server role')
    WHERE NOT EXISTS (SELECT 1 FROM Users
                      WHERE UserID = NEW.ServerID AND Role = 'Server' AND IsActive = 1);

    SELECT RAISE(ABORT, 'Servers can only be assigned to Confirmed bookings')
    WHERE (SELECT Status FROM Bookings WHERE BookingID = NEW.BookingID) IS NOT 'Confirmed';

    SELECT RAISE(ABORT, 'This booking did not request staffing')
    WHERE (SELECT StaffCount FROM Bookings WHERE BookingID = NEW.BookingID) = 0;

    SELECT RAISE(ABORT, 'New assignments must start as Pending')
    WHERE NEW.Status IS NOT 'Pending';

    SELECT RAISE(ABORT, 'This booking already has as many servers as it requested')
    WHERE (SELECT COUNT(*) FROM StaffAssignments
           WHERE BookingID = NEW.BookingID AND Status <> 'Declined')
          >= (SELECT StaffCount FROM Bookings WHERE BookingID = NEW.BookingID);

    SELECT RAISE(ABORT, 'This server already has an assignment that overlaps this event')
    WHERE EXISTS (SELECT 1
                  FROM StaffAssignments AS sa
                  JOIN BookingSchedule AS o ON o.BookingID = sa.BookingID
                  JOIN BookingSchedule AS n ON n.BookingID = NEW.BookingID
                  WHERE sa.ServerID = NEW.ServerID
                    AND sa.BookingID <> NEW.BookingID
                    AND sa.Status <> 'Declined'
                    AND o.Status <> 'Cancelled'
                    AND o.EventDate = n.EventDate
                    AND o.StartMinute < n.EndMinute
                    AND n.StartMinute < o.EndMinute);
END;

CREATE TRIGGER TRG_StaffAssignments_BeforeUpdate
BEFORE UPDATE ON StaffAssignments
BEGIN
    SELECT RAISE(ABORT, 'An assignment''s booking and server cannot be changed')
    WHERE NEW.AssignmentID IS NOT OLD.AssignmentID
       OR NEW.BookingID IS NOT OLD.BookingID
       OR NEW.ServerID IS NOT OLD.ServerID;

    SELECT RAISE(ABORT, 'Assignments can only change while the booking is Confirmed')
    WHERE NEW.Status IS NOT OLD.Status
      AND (SELECT Status FROM Bookings WHERE BookingID = NEW.BookingID) IS NOT 'Confirmed';

    SELECT RAISE(ABORT, 'This booking already has as many servers as it requested')
    WHERE OLD.Status = 'Declined' AND NEW.Status <> 'Declined'
      AND (SELECT COUNT(*) FROM StaffAssignments
           WHERE BookingID = NEW.BookingID AND Status <> 'Declined')
          >= (SELECT StaffCount FROM Bookings WHERE BookingID = NEW.BookingID);

    SELECT RAISE(ABORT, 'This server already has an assignment that overlaps this event')
    WHERE OLD.Status = 'Declined' AND NEW.Status <> 'Declined'
      AND EXISTS (SELECT 1
                  FROM StaffAssignments AS sa
                  JOIN BookingSchedule AS o ON o.BookingID = sa.BookingID
                  JOIN BookingSchedule AS n ON n.BookingID = NEW.BookingID
                  WHERE sa.ServerID = NEW.ServerID
                    AND sa.BookingID <> NEW.BookingID
                    AND sa.Status <> 'Declined'
                    AND o.Status <> 'Cancelled'
                    AND o.EventDate = n.EventDate
                    AND o.StartMinute < n.EndMinute
                    AND n.StartMinute < o.EndMinute);
END;

CREATE TRIGGER TRG_StaffAssignments_BeforeDelete
BEFORE DELETE ON StaffAssignments
BEGIN
    SELECT RAISE(ABORT, 'Only a Pending assignment on a Confirmed booking can be withdrawn')
    WHERE OLD.Status IS NOT 'Pending'
       OR (SELECT Status FROM Bookings WHERE BookingID = OLD.BookingID) IS NOT 'Confirmed';
END;

CREATE TRIGGER TRG_Payments_BeforeInsert
BEFORE INSERT ON Payments
BEGIN
    SELECT RAISE(ABORT, 'A payment record can only be opened for a Confirmed booking')
    WHERE (SELECT Status FROM Bookings WHERE BookingID = NEW.BookingID) IS NOT 'Confirmed';
END;

CREATE TRIGGER TRG_Payments_BeforeUpdate
BEFORE UPDATE ON Payments
BEGIN
    SELECT RAISE(ABORT, 'A payment''s booking cannot be changed')
    WHERE NEW.BookingID IS NOT OLD.BookingID;

    SELECT RAISE(ABORT, 'A refunded payment cannot be changed')
    WHERE OLD.Status = 'Refunded'
      AND (NEW.Status IS NOT OLD.Status OR NEW.PaidAt IS NOT OLD.PaidAt);

    SELECT RAISE(ABORT, 'A cancelled booking cannot be marked as Paid')
    WHERE NEW.Status = 'Paid' AND OLD.Status IS NOT 'Paid'
      AND (SELECT Status FROM Bookings WHERE BookingID = NEW.BookingID) = 'Cancelled';

    SELECT RAISE(ABORT, 'Record a refund with the Refunded status, not Unpaid')
    WHERE NEW.Status = 'Unpaid' AND OLD.Status = 'Paid'
      AND (SELECT Status FROM Bookings WHERE BookingID = NEW.BookingID) = 'Cancelled';

    SELECT RAISE(ABORT, 'Only a paid payment of a cancelled booking can be refunded')
    WHERE NEW.Status = 'Refunded' AND OLD.Status IS NOT 'Refunded'
      AND (OLD.Status IS NOT 'Paid'
           OR (SELECT Status FROM Bookings WHERE BookingID = NEW.BookingID) IS NOT 'Cancelled');

    SELECT RAISE(ABORT, 'A refund keeps the original payment time')
    WHERE NEW.Status = 'Refunded' AND OLD.Status = 'Paid'
      AND NEW.PaidAt IS NOT OLD.PaidAt;
END;

CREATE TRIGGER TRG_Payments_BeforeDelete
BEFORE DELETE ON Payments
BEGIN
    SELECT RAISE(ABORT, 'Payment records cannot be deleted, they are kept as financial history');
END;

CREATE TRIGGER TRG_Reviews_BeforeInsert
BEFORE INSERT ON Reviews
BEGIN
    SELECT RAISE(ABORT, 'Only Completed bookings can be reviewed')
    WHERE (SELECT Status FROM Bookings WHERE BookingID = NEW.BookingID) IS NOT 'Completed';
END;

CREATE TRIGGER TRG_Reviews_BeforeUpdate
BEFORE UPDATE ON Reviews
BEGIN
    SELECT RAISE(ABORT, 'A review''s booking cannot be changed')
    WHERE NEW.BookingID IS NOT OLD.BookingID;
END;