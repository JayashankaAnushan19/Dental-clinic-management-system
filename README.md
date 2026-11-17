# SmileCare Dental — Clinic Management System (v2)

A modern dental clinic management system built with **PHP 8** and **MySQL/MariaDB**, running on **XAMPP**.

## Features

**Patients**
- Self-registration and secure sign-in
- Book appointments with a live calendar and real-time time-slot availability
- Upcoming/past appointments with online cancellation
- Treatment history with dentist notes
- Invoices (printable) and outstanding balance
- Feedback and star ratings, plus a medical profile (allergies, conditions, emergency contact)

**Dentists**
- Daily schedule with "next patient" view
- Visit screen: record treatments per tooth with clinical notes, see the patient's history and allergy alerts
- Complete a visit, which automatically issues an invoice (with optional offer discount)
- Records for their own patients

**Admin**
- Dashboard: today's schedule, pending bookings, revenue and appointment charts, popular treatments, dentist workload
- Appointments with filters, plus booking on behalf of patients (walk-in or phone)
- Patients, dentists (with logins), staff, clinics (hours and working days), services and prices
- Offers, closed days (holidays), invoices (mark paid or void), and feedback moderation (publish to the website)

**General**
- Responsive design (mobile, tablet, desktop) with light and dark mode
- Public website with services, dentists, offers, reviews and locations

## Security
- Passwords hashed with `password_hash` (bcrypt)
- Prepared statements everywhere (no SQL injection)
- CSRF tokens on every form, and output escaping against XSS
- Role-based access control (admin / dentist / patient)
- Login throttling, session regeneration and idle timeout
- Application code lives outside the web root (only `public/` is served)

## Quick start (Windows + XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/) to `C:\xampp` (PHP 8.1+).
2. Double-click **`start.bat`**. It will:
   - start MySQL and Apache
   - create the `dentalclinic_v2` database with demo data (first run only)
   - link `public/` to `C:\xampp\htdocs\dentalclinic`
   - open http://localhost/dentalclinic/
3. Sign in with a demo account:

| Role    | Email                    | Password      |
|---------|--------------------------|---------------|
| Admin   | admin@smilecare.test     | `Admin@123`   |
| Dentist | dentist@smilecare.test   | `Dentist@123` |
| Patient | patient@smilecare.test   | `Patient@123` |

- **`stop.bat`** stops Apache and MySQL.
- **`reset-demo.bat`** wipes the database and reloads fresh demo data.

## Configuration
All settings are in [`config/config.php`](config/config.php): clinic name, currency, timezone, booking slot length, database credentials and debug mode.

## Project structure
```
config/      app configuration
database/    schema.sql, install.php (installer + demo data), legacy v1 dump
public/      web root (pages, assets, api)
  admin/     admin pages
  dentist/   dentist pages
  patient/   patient pages
  api/       JSON endpoints (time slots)
src/         application code (db, auth, booking engine, layout, shared views)
```

## Versions
- **v1.0**: the original 2017 HTML/PHP project (tag `v1.0`, plus `Dental-clinic-management-system_V1.0.rar`)
- **v2**: complete rewrite (this branch)
