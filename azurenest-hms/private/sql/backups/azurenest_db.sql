--
-- Reference SQL logic for dynamic room status (for future use)
--
/*
Suggested SQL logic:

SELECT rooms.*,
       CASE 
           WHEN EXISTS (SELECT 1 FROM bookings WHERE room = rooms.room_number AND status = 'Checked-in') THEN 'Occupied'
           WHEN EXISTS (SELECT 1 FROM bookings WHERE room = rooms.room_number AND status = 'Reserved') THEN 'Reserved'
           ELSE rooms.status
       END AS display_status
FROM rooms;

-- Alternative approach:
SELECT rooms.*, 
       CASE 
           WHEN bookings.status = 'Checked-in' THEN 'Occupied'
           ELSE rooms.status
       END AS display_status
FROM rooms
LEFT JOIN bookings ON rooms.room_number = bookings.room
AND bookings.status = 'Checked-in';

-- Use display_status in Room Management instead of static room.status
*/

-- Create the database
CREATE DATABASE IF NOT EXISTS azurenest_db;
USE azurenest_db;

-- Staff Table
CREATE TABLE Staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Manager','Receptionist','Housekeeping','Maintenance') NOT NULL,
    contact VARCHAR(100),
    shift VARCHAR(50),
    force_logout TINYINT(1) DEFAULT 0
) ENGINE=InnoDB;

-- Room Types Table
CREATE TABLE Room_Types (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL,
    capacity INT NOT NULL,
    rate DECIMAL(10,2) NOT NULL,
    description TEXT
) ENGINE=InnoDB;

-- Rooms Table
CREATE TABLE Rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) UNIQUE NOT NULL,
    type_id INT,
    status ENUM('Available','Occupied','Maintenance','Cleaning') DEFAULT 'Available',
    FOREIGN KEY (type_id) REFERENCES Room_Types(type_id)
) ENGINE=InnoDB;

-- Guest Table
CREATE TABLE Guest (
    guest_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact VARCHAR(100),
    email VARCHAR(100),
    preferences TEXT
) ENGINE=InnoDB;

-- Bookings Table
CREATE TABLE Bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    guest_id INT,
    room_id INT,
    check_in DATE,
    check_out DATE,
    status ENUM('Reserved','Checked-in','Checked-out','Cancelled') DEFAULT 'Reserved',
    group_id INT,
    FOREIGN KEY (guest_id) REFERENCES Guest(guest_id),
    FOREIGN KEY (room_id) REFERENCES Rooms(room_id)
) ENGINE=InnoDB;

-- Payments Table
CREATE TABLE Payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    amount DECIMAL(10,2),
    payment_date DATE,
    method VARCHAR(50),
    status ENUM('Pending','Completed','Refunded') DEFAULT 'Pending',
    FOREIGN KEY (booking_id) REFERENCES Bookings(booking_id)
) ENGINE=InnoDB;

-- Suppliers Table
CREATE TABLE Suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    contact VARCHAR(100),
    address TEXT
) ENGINE=InnoDB;

-- Inventory Table
CREATE TABLE Inventory (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    quantity INT,
    reorder_level INT,
    supplier_id INT,
    last_updated DATE,
    FOREIGN KEY (supplier_id) REFERENCES Suppliers(supplier_id)
) ENGINE=InnoDB;

-- Services Table
CREATE TABLE Services (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50),
    description TEXT,
    rate DECIMAL(10,2)
) ENGINE=InnoDB;

-- Service Usage Table
CREATE TABLE Service_Usage (
    usage_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    service_id INT,
    quantity INT,
    usage_date DATE,
    FOREIGN KEY (booking_id) REFERENCES Bookings(booking_id),
    FOREIGN KEY (service_id) REFERENCES Services(service_id)
) ENGINE=InnoDB;

-- Housekeeping Table
CREATE TABLE Housekeeping (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT,
    staff_id INT,
    task_type ENUM('Cleaning','Maintenance','Restocking'),
    status ENUM('Pending','In Progress','Completed') DEFAULT 'Pending',
    notes TEXT,
    scheduled_date DATE,
    FOREIGN KEY (room_id) REFERENCES Rooms(room_id),
    FOREIGN KEY (staff_id) REFERENCES Staff(staff_id)
) ENGINE=InnoDB;