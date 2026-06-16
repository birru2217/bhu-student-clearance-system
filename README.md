# BHU Digital Clearance Management System

A complete PHP + MySQL clearance system for **Bule Hora University**.

## Stack
- PHP 7.4+ / 8.x
- MySQL / MariaDB
- Bootstrap 5 (CDN)
- Dompdf (vendored stub — see `vendor/`) for PDF certificates
- Pure PHP QR code generator (embedded)

## Setup

1. Copy the project into your web root (e.g. `htdocs/bhu` for XAMPP).
2. Create the database & seed data:
   ```sql
   SOURCE sql/bhu_clearance_db.sql;
   ```
   Or import `sql/bhu_clearance_db.sql` via phpMyAdmin.
3. Edit `config/db.php` if your MySQL credentials differ (defaults: `root` / empty password).
4. Visit `http://localhost/bhu/index.php`.

## Default Logins (password for all sample users: `password`)

| Role               | Username           |
|--------------------|--------------------|
| Admin              | admin              |
| Library Officer    | library            |
| Cafeteria Officer  | cafeteria          |
| Dormitory Officer  | dormitory          |
| Finance Officer    | finance            |
| Sports Officer     | sports             |
| Department Head    | depthead_cse       |
| Registrar          | registrar          |
| Student (sample)   | BHU/0001/16        |

## Roles & Routing (`login.php`)
- `admin` → `dashboards/admin_dashboard.php`
- `library|cafeteria|dormitory|finance|sports` → `dashboards/office_dashboard.php`
- `depthead` → `dashboards/depthead_dashboard.php`
- `registrar` → `dashboards/registrar_dashboard.php`
- `student` → `dashboards/student_dashboard.php`

## Features
- Unified login with 5 office portals + admin + dept head + registrar + student
- Full CRUD on students & staff (Admin)
- Search → View → Update (Hold/Cleared + remarks) per office
- Department-level approval (only own department students)
- Final registrar approval with digital seal + signature
- Student live tracking of all 5 offices
- PDF certificate download with embedded QR code (only when fully cleared)
