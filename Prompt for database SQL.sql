-- DeviceRent database setup
-- In live hosting, select your existing database before running this script
-- if your hosting provider does not allow CREATE DATABASE or USE.
CREATE DATABASE IF NOT EXISTS `device-rent` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `device-rent`;

-- ==========================
-- Admins Table (login)
-- ==========================
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin') DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin account
-- Username: admin | Password: admin123
INSERT INTO admins (username, password, role)
VALUES ('admin', '$2y$12$4VyRYvH3I.ZUC9pbzsXteu1utEzHqg57qVPXrk2fiCWgsvGoTLvHu', 'admin')
ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================
-- Sample Devices
-- ==========================
INSERT INTO devices (device_name, serial_number, category, quantity, status)
SELECT 'Dell Latitude 5420', 'LPT-001', 'Laptop', 1, 'Available'
WHERE NOT EXISTS (SELECT 1 FROM devices WHERE serial_number = 'LPT-001');
INSERT INTO devices (device_name, serial_number, category, quantity, status)
SELECT 'Dell Pro 14', '4YYF2C4', 'Laptop', 1, 'Available'
WHERE NOT EXISTS (SELECT 1 FROM devices WHERE serial_number = '4YYF2C4');
INSERT INTO devices (device_name, serial_number, category, quantity, status)
SELECT 'Canon Pixma', 'PRT-001', 'Printer', 1, 'Available'
WHERE NOT EXISTS (SELECT 1 FROM devices WHERE serial_number = 'PRT-001');
INSERT INTO devices (device_name, serial_number, category, quantity, status)
SELECT 'Epson EB-972', 'X8BD2Y00448', 'Projector', 1, 'Available'
WHERE NOT EXISTS (SELECT 1 FROM devices WHERE serial_number = 'X8BD2Y00448');
INSERT INTO devices (device_name, serial_number, category, quantity, status)
SELECT 'Epson EB-972', 'X8BD2Y00457', 'Projector', 1, 'Available'
WHERE NOT EXISTS (SELECT 1 FROM devices WHERE serial_number = 'X8BD2Y00457');

-- ==========================
-- Sample Customers (optional)
-- ==========================
-- Uncomment these only if the live database should contain demo customers.
-- INSERT INTO customers (fullname, ic_number, phone, email, device, rent_date, return_date, returned) VALUES
-- ('Ali Ahmad', '010101-10-1234', '0123456789', 'ali@gmail.com', 'Dell Latitude 5420 [LPT-001]', '2026-08-01', '2026-08-05', 0),
-- ('Siti Nur', '020202-08-5678', '0198765432', 'siti@gmail.com', 'Epson EB-972 [X8BD2Y00448]', '2026-08-03', '2026-08-07', 0);