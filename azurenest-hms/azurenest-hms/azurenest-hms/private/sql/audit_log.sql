-- Audit Log Table
CREATE TABLE IF NOT EXISTS Audit_Log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT,
    action VARCHAR(255),
    details TEXT,
    log_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES Staff(staff_id) ON DELETE SET NULL
);
