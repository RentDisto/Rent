-- Create Database
CREATE DATABASE IF NOT EXISTS `device-rent`;
USE `device-rent`;

-- ==========================
-- Admins Table (login)
-- ==========================
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin') DEFAULT 'admin'
);

-- Admin account
-- Password: admin123  
INSERT INTO admins (username, password, role)
VALUES ('admin', MD5('admin123'), 'admin');

-- ==========================
-- Customers Table
-- ==========================
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    ic_number VARCHAR(20) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    device TEXT,
    rent_date DATE,
    return_date DATE,
    returned TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================
-- Devices Table
-- ==========================
CREATE TABLE IF NOT EXISTS devices (
    device_id INT AUTO_INCREMENT PRIMARY KEY,
    device_name VARCHAR(100) NOT NULL,
    serial_number VARCHAR(50),
    category VARCHAR(50),
    quantity INT DEFAULT 1,
    status ENUM('Available', 'Unavailable', 'Maintenance') DEFAULT 'Available'
);

-- ==========================
-- Sample Devices
-- ==========================
INSERT INTO devices (device_name, serial_number, category, quantity, status) VALUES
('Dell Latitude 5420', 'LPT-001', 'Laptop', 1, 'Available'),
('Dell Pro 14', '4YYF2C4', 'Laptop', 1, 'Available'),
('Canon Pixma', 'PRT-001', 'Printer', 1, 'Available'),
('Epson EB-972', 'X8BD2Y00448', 'Projector', 1, 'Available'),
('Epson EB-972', 'X8BD2Y00457', 'Projector', 1, 'Available');

-- ==========================
-- Sample Customers (optional)
-- ==========================
INSERT INTO customers (fullname, ic_number, phone, email, device, rent_date, return_date, returned) VALUES
('Ali Ahmad', '010101-10-1234', '0123456789', 'ali@gmail.com', 'Dell Latitude 5420 [LPT-001]', '2026-08-01', '2026-08-05', 0),
('Siti Nur', '020202-08-5678', '0198765432', 'siti@gmail.com', 'Epson EB-972 [X8BD2Y00448]', '2026-08-03', '2026-08-07', 0);