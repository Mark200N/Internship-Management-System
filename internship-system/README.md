# 🎓 Internship Management System (IMS)

A full-featured web-based Internship Management System built with PHP, MySQL, HTML, CSS, and JavaScript — designed to run on a **WAMP server**.

---

## 🚀 Quick Start

### 1. Prerequisites
- WAMP Server (v3.x+) with Apache, PHP 7.4+, MySQL 5.7+
- Browser: Chrome, Firefox, or Edge

### 2. Installation

**Step 1** — Copy the project folder to your WAMP www directory:
```
C:\wamp64\www\internship-system\
```

**Step 2** — Start WAMP and ensure Apache & MySQL are running (tray icon turns green).

**Step 3** — Open your browser and navigate to:
```
http://localhost/internship-system/setup.php
```

**Step 4** — Fill in your database credentials (default: host=`localhost`, user=`root`, password=``) and click **Install**.

**Step 5** — Delete `setup.php` after setup is complete (security).

**Step 6** — Visit:
```
http://localhost/internship-system/
```

---

## 🔑 Default Admin Login

| Field    | Value                       |
|----------|-----------------------------|
| Email    | admin@internship.ac.ug      |
| Password | Admin@1234                  |

> ⚠️ Change this password immediately after first login via **Settings**.

---

## 📁 Project Structure

```
internship-system/
│
├── index.php               ← Entry point (redirects to login/dashboard)
├── login.php               ← Authentication page
├── register.php            ← Self-registration (student/lecturer)
├── logout.php              ← Session destroy
├── dashboard.php           ← Role router → student/lecturer/admin dashboard
├── setup.php               ← One-time database installer (DELETE after use)
├── .htaccess               ← Apache security rules
│
├── config/
│   ├── db.php              ← PDO database connection
│   ├── helpers.php         ← Auth, sanitization, flash, upload helpers
│   └── install.sql         ← Database schema + seed data
│
├── includes/
│   └── layout.php          ← Shared sidebar + topbar layout
│
├── student/
│   ├── dashboard.php       ← Student home
│   ├── internship.php      ← Register/view internship
│   ├── logbook.php         ← Submit & view weekly logbooks
│   ├── feedback.php        ← View supervisor feedback
│   ├── documents.php       ← View uploaded files
│   └── profile.php         ← Edit profile & password
│
├── lecturer/
│   ├── dashboard.php       ← Lecturer home
│   ├── students.php        ← View assigned students
│   ├── logbooks.php        ← Review logbook list
│   ├── review.php          ← Review single logbook, give feedback/grade
│   ├── grades.php          ← Grade overview
│   └── profile.php         ← Edit profile
│
├── admin/
│   ├── dashboard.php       ← Admin overview
│   ├── users.php           ← Create/edit/delete users
│   ├── assignments.php     ← Assign lecturers to students
│   ├── internships.php     ← View all internships
│   ├── reports.php         ← Analytics & progress reports
│   └── settings.php        ← System settings
│
├── api/
│   └── notifications.php   ← AJAX notification handler
│
├── assets/
│   ├── css/style.css       ← Main stylesheet (CSS variables, components)
│   └── js/main.js          ← Global JavaScript
│
└── uploads/
    └── logbooks/           ← Student file uploads (auto-created)
```

---

## 👥 User Roles & Workflows

### 🧑‍🎓 Student
1. Register at `/register.php` (role: Student)
2. Login → redirected to student dashboard
3. Go to **My Internship** → register company details
4. Submit **weekly logbooks** with optional PDF/DOCX attachments
5. View **supervisor feedback** and grades
6. Track progress on dashboard

### 👨‍🏫 Lecturer
1. Register at `/register.php` (role: Lecturer)
2. Login → redirected to lecturer dashboard
3. Admin assigns students to your supervision
4. **Review Logbooks** → approve/reject entries, add feedback & grade
5. Monitor student progress

### 🛠️ Admin
1. Login with default admin credentials
2. **Users** → create, edit, delete students/lecturers
3. **Assignments** → assign lecturers to students
4. **Internships** → view all registered internships
5. **Reports** → analytics, progress, unassigned students
6. **Settings** → manage admin profile

---

## 🗄️ Database Schema

| Table          | Description                                 |
|----------------|---------------------------------------------|
| `users`        | All user accounts (student/lecturer/admin)  |
| `students`     | Student academic profiles                   |
| `lecturers`    | Lecturer department profiles                |
| `assignments`  | Lecturer ↔ student supervision links        |
| `internships`  | Company placements per student              |
| `logbooks`     | Weekly entries with feedback & grades       |
| `notifications`| In-app notification system                  |

---

## 🔒 Security Features

- Passwords hashed with `PASSWORD_BCRYPT` (cost 12)
- PDO prepared statements on all queries (SQL injection safe)
- Session regeneration on login
- CSRF token on admin user delete
- File upload validation: MIME type + extension + 5MB limit
- `.htaccess` blocks directory listing and PHP execution in uploads
- `htmlspecialchars()` on all output (XSS safe)
- Role-based access control on every page

---

## 🎨 Tech Stack

| Layer     | Technology                              |
|-----------|-----------------------------------------|
| Frontend  | HTML5, CSS3 (custom, no frameworks), JS |
| Fonts     | Syne (headings) + DM Sans (body)        |
| Icons     | Font Awesome 6                          |
| Backend   | PHP 7.4+ (PDO, sessions, file I/O)      |
| Database  | MySQL 5.7+ / MariaDB 10.3+              |
| Server    | Apache via WAMP                         |

---

## 🐛 Troubleshooting

| Issue                        | Fix                                                         |
|------------------------------|-------------------------------------------------------------|
| Blank page / 500 error        | Enable PHP error display in WAMP or check `php.ini`         |
| Database connection error    | Verify `config/db.php` credentials match your MySQL setup   |
| File upload not working      | Check `uploads/` directory exists and is writable (755)     |
| CSS/JS not loading           | Ensure path is `/internship-system/` not `/`                |
| Session issues               | Clear browser cookies and retry                             |
| "Access denied" redirect      | Confirm correct user role is logged in                      |

---

## 📝 Notes

- Default upload limit: 5MB per file (PDF/DOCX only)
- `setup.php` **must be deleted** after installation
- For production: set `display_errors = Off` in `php.ini`
- Tested on WAMP 3.3.x, PHP 8.1, MySQL 8.0
