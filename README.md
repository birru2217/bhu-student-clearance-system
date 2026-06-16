# 🎓 Bule Hora University Student Clearance System

![Status](https://img.shields.io/badge/Status-Completed-success)
![Version](https://img.shields.io/badge/Version-1.0-blue)
![Tech Stack](https://img.shields.io/badge/Tech_Stack-PHP_|_MySQL_|_Bootstrap-orange)

An automated, secure, and user-friendly student clearance web application designed for Bule Hora University (BHU). This system replaces slow, paper-dependent clearance workflows with real-time digital approvals across multiple university offices, culminating in a verifiable digital clearance certificate.

## 📸 System Previews
![Clearance System Preview](screenshots/home.png) <!-- Gara fuulduraatti suuraa itti dabaluuf -->

---

## 📥 Database Download & Quick Setup
To set up this clearance system locally, download the required MySQL database schema below:

[![Download SQL Database](https://img.shields.io/badge/Download-MySQL_Database_Schema-3f51b5?style=for-the-badge&logo=mysql&logoColor=white)](https://github.com/birru2217/bhu-student-clearance-system/raw/main/sql/bhu_clearance_db.sql)

---

## ✨ System Features & Workflows

The platform is designed to handle different approval clearances through dedicated interfaces for all key actors:

### 👤 1. Student Module:
- Submit clearance requests with specific graduation or withdrawal reasons.
- Track real-time status across 5 essential campus offices:
  - 📚 **Library Office:** Book returns and outstanding fines.
  - 🍽️ **Cafeteria Office:** Cafeteria tray and utensil clearance.
  - 🛏️ **Dormitory / Proctor Office:** Keys, blankets, and hostel property.
  - 💰 **Finance Office:** Tuition fee settlement.
  - ⚽ **Sports / Store Office:** Sport kits and equipment clearance.
- Receive instant in-app notifications upon approval or hold status updates.
- Generate and download the official **Digital Clearance Certificate** with a unique secure code (`certificate_code`) once fully cleared.

### 🔑 2. Department & Administrative Head Module:
- View pending department-level clearances for graduating students.
- Review student academic records and approve/reject clearance with feedback remarks.

### 🛡️ 3. Registrar Module:
- Issue final institutional clearance approvals.
- Automatically generate the secure verification code for final graduation files.

---

## 🛠️ Technologies Used
- **Backend:** PHP (Procedural logic with SQL injection safeguards)
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap
- **Database:** MySQL (Relational tables with foreign key constraints)
- **Local Server:** WAMP / XAMPP Server

---

## ⚙️ Local Installation Guide

1. Clone or download this repository into your local server directory (`wamp64/www/` or `xampp/htdocs/`).
2. Rename the project folder to `bhu`.
3. Open **phpMyAdmin** and create a new database named **`bhu_clearance_db`**.
4. Import the **`bhu_clearance_db.sql`** file (which you can download from the link above) into the database.
5. Open your browser and navigate to: `http://localhost/bhu`.

---

## 🔑 Default Login Credentials
To test different user roles within the clearance system (use the password `password` for all default accounts):

- **System Administrator:** Username: `admin` | Password: `password`
- **Library Officer:** Username: `library` | Password: `password`
- **Dormitory Officer:** Username: `dormitory` | Password: `password`
- **Student User (Sara Demisse):** Student ID: `BHU/0001/16` | Password: `password`

---

## 👥 Contributors
- Developed as an Advanced Web Programming Project by CSE students.
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
