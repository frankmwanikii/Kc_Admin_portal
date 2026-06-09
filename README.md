# Grace Church MIS

A mobile-first PHP church management system for households, attendance, giving, ministries, communications, and a member self-service portal.

## Features

- **Household Management** — Group members by family units
- **Attendance Tracking** — Services, cell groups, events; missed-member alerts
- **Finance & Giving** — Tithes, offerings, project funds; SMS acknowledgments; M-Pesa integration ready
- **Ministries** — Departments with leaders and member counts
- **Communication Hub** — Bulk SMS and email; birthday tracking
- **Member Portal** — Giving timeline, PDF statements, pledge tracker, profile
- **QR Onboarding** — Self-registration with emailed magic login link

## Requirements

- PHP 8.1+ with PDO MySQL extension
- Composer
- MySQL 5.7+ or MariaDB (running locally)

## Quick Start

```bash
# Install dependencies
php composer.phar install

# Copy environment template (optional — setup wizard writes .env for you)
copy .env.example .env

# Start development server
php -S localhost:8000 -t public
```

Open [http://localhost:8000](http://localhost:8000) — you'll be guided through the **setup wizard** to enter your MySQL credentials, church name, and admin account. Credentials are saved automatically to `.env` and tables are created in your database.

## After Setup

Sign in with the admin email and password you chose during setup. Demo member accounts are seeded automatically (password: `password123`).

## QR Onboarding

1. Admin → **QR Onboarding** to print/display the QR code
2. New members scan and complete the registration form
3. They receive an email with a secure 48-hour portal access link

## Configuration

Edit `.env` for:

- `CHURCH_NAME`, `CHURCH_ADDRESS` — branding
- `MAIL_*` — SMTP for emails (Mailtrap for dev)
- `SMS_API_KEY`, `SMS_SENDER_ID` — Africa's Talking SMS
- `DB_CONNECTION=mysql` — switch from SQLite to MySQL

## Project Structure

```
app/
  Controllers/     Admin & member controllers
  Core/            Router, Auth, Database, View
  Models/          Data models
  Services/        Mail, SMS, PDF, QR, Onboarding
database/          Schema, seeds, migration script
public/            Web root (index.php)
routes/            URL routing
views/             Mobile-first Tailwind UI
```

## Production

Point your web server document root to `public/`. Ensure `database/` is writable for SQLite.
