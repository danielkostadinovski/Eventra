# Eventra

Eventra is a web-based event booking management system for a company that rents out event venues in Sofia.
Clients can browse venues and book them online with the full price shown before submitting, Venue Managers handle
the booking requests for their venues, Servers (the waiting staff) accept event assignments and see their earnings,
and the Administrator manages all venues, staff, prices and reports.

## Technologies

- PHP 8 (no framework), organised in an MVC structure
- SQLite, accessed through PDO with prepared statements
- Bootstrap 5 (loaded from a CDN)
- PHP's built-in web server

## Requirements

- PHP 8.1 or newer with the `pdo_sqlite` extension
- SQLite 3.37 or newer (bundled with PHP)

## Setup

1. Clone the repository:
   ```
   git clone https://github.com/danielkostadinovski/Eventra.git
   ```
2. In the project folder, create the database and fill it with demo data:
   ```
   php database/create_db.php
   php database/seed.php
   ```
3. Start the server:
   ```
   php -S localhost:8000 -t public
   ```
4. Open http://localhost:8000 in a browser.

## Demo accounts

All demo accounts use the password `Eventra2026!`

| Role | Email |
|---|---|
| Administrator | daniela.koleva@eventra.bg |
| Venue Manager | georgi.petrov@eventra.bg |
| Server | nikolay.ivanov@eventra.bg |
| Client | ivana.georgieva@abv.bg |

