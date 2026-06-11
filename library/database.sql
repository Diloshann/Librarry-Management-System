-- ============================================================
-- ATI Library Management System - Database Schema
-- School: Advanced Technological Institute (ATI)
-- ============================================================

CREATE DATABASE IF NOT EXISTS ati_library CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ati_library;

-- -----------------------------------------------
-- ROLES
-- -----------------------------------------------
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);
INSERT INTO roles (name) VALUES ('admin'), ('librarian'), ('member');

-- -----------------------------------------------
-- USERS (Admin & Librarian)
-- -----------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE,
    phone VARCHAR(20),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Default admin: admin / admin123
INSERT INTO users (role_id, username, password, full_name, email) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@ati.lk');

-- -----------------------------------------------
-- MEMBERS (Students)
-- -----------------------------------------------
CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    nic VARCHAR(20) UNIQUE,
    department VARCHAR(100),
    course VARCHAR(100),
    batch VARCHAR(20),
    email VARCHAR(150) UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    profile_picture VARCHAR(255) DEFAULT 'default.png',
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('active','inactive','suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------
-- AUTHORS
-- -----------------------------------------------
CREATE TABLE authors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------
-- CATEGORIES
-- -----------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO categories (name) VALUES
('Programming'),('Networking'),('Database'),('Mathematics'),
('English'),('Business'),('Science'),('General');

-- -----------------------------------------------
-- BOOKS
-- -----------------------------------------------
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(30) UNIQUE,
    title VARCHAR(255) NOT NULL,
    author_id INT,
    publisher VARCHAR(150),
    edition VARCHAR(50),
    category_id INT,
    language VARCHAR(50) DEFAULT 'English',
    shelf_location VARCHAR(50),
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    borrowed_copies INT DEFAULT 0,
    cover_image VARCHAR(255) DEFAULT 'no-cover.png',
    description TEXT,
    date_added DATE,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- -----------------------------------------------
-- SETTINGS
-- -----------------------------------------------
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value VARCHAR(255) NOT NULL
);
INSERT INTO settings (setting_key, setting_value) VALUES
('fine_per_day', '50'),
('borrow_days', '14'),
('max_books_per_member', '3'),
('library_name', 'ATI Library'),
('library_email', 'library@ati.lk'),
('library_phone', '+94 000 000 000');

-- -----------------------------------------------
-- BORROW TRANSACTIONS
-- -----------------------------------------------
CREATE TABLE borrow_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    book_id INT NOT NULL,
    issued_by INT NOT NULL,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE,
    status ENUM('borrowed','returned','overdue','renewed') DEFAULT 'borrowed',
    book_condition_on_issue VARCHAR(50) DEFAULT 'Good',
    book_condition_on_return VARCHAR(50),
    renewal_count INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (book_id) REFERENCES books(id),
    FOREIGN KEY (issued_by) REFERENCES users(id)
);

-- -----------------------------------------------
-- FINES
-- -----------------------------------------------
CREATE TABLE fines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL UNIQUE,
    member_id INT NOT NULL,
    overdue_days INT DEFAULT 0,
    fine_per_day DECIMAL(10,2) DEFAULT 50.00,
    total_fine DECIMAL(10,2) DEFAULT 0.00,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    balance DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('unpaid','paid','waived') DEFAULT 'unpaid',
    collected_by INT,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES borrow_transactions(id),
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (collected_by) REFERENCES users(id) ON DELETE SET NULL
);

-- -----------------------------------------------
-- RESERVATIONS
-- -----------------------------------------------
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    book_id INT NOT NULL,
    reserved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATE,
    status ENUM('pending','fulfilled','cancelled','expired') DEFAULT 'pending',
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);

-- -----------------------------------------------
-- NOTIFICATIONS
-- -----------------------------------------------
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('issued','due_reminder','overdue','fine','reservation','general') DEFAULT 'general',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id)
);

-- -----------------------------------------------
-- ANNOUNCEMENTS
-- -----------------------------------------------
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    created_by INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- -----------------------------------------------
-- AUDIT LOG
-- -----------------------------------------------
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('admin','librarian','member') NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(200) NOT NULL,
    details TEXT,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
