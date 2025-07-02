-- Tasks Table for Notification & Task System
CREATE TABLE IF NOT EXISTS Tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    assigned_to INT,
    assigned_by INT,
    status ENUM('Pending','In Progress','Completed') DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES Staff(staff_id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_by) REFERENCES Staff(staff_id) ON DELETE SET NULL
);
