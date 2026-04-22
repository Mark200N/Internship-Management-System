-- Internship Management System Database
-- Run this file to set up the database

CREATE DATABASE IF NOT EXISTS internship_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE internship_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(200) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','lecturer','admin') NOT NULL DEFAULT 'student',
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Students table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    registration_number VARCHAR(50) NOT NULL UNIQUE,
    course VARCHAR(150) NOT NULL,
    year INT DEFAULT 1,
    phone VARCHAR(20) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Lecturers table
CREATE TABLE IF NOT EXISTS lecturers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    department VARCHAR(150) NOT NULL,
    staff_id VARCHAR(50) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Lecturer-Student assignments
CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecturer_id INT NOT NULL,
    student_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_assignment (lecturer_id, student_id),
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Internships table
CREATE TABLE IF NOT EXISTS internships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    company_name VARCHAR(200) NOT NULL,
    location VARCHAR(200) NOT NULL,
    supervisor_name VARCHAR(150) DEFAULT NULL,
    supervisor_email VARCHAR(200) DEFAULT NULL,
    supervisor_phone VARCHAR(20) DEFAULT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    description TEXT DEFAULT NULL,
    status ENUM('active','completed','withdrawn') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Logbooks table
CREATE TABLE IF NOT EXISTS logbooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    internship_id INT DEFAULT NULL,
    week_number INT NOT NULL,
    title VARCHAR(200) DEFAULT NULL,
    content TEXT NOT NULL,
    activities TEXT DEFAULT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    file_name VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    feedback TEXT DEFAULT NULL,
    grade VARCHAR(10) DEFAULT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE SET NULL
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin account (password: Admin@1234)
INSERT INTO users (name, email, password, role) VALUES 
('System Administrator', 'admin@internship.ac.ug', 'Admin@1234', 'admin')
ON DUPLICATE KEY UPDATE id=id;
