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


