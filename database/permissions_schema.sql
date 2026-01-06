-- Permissions table for granular access control
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    `key` VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User permissions junction table
CREATE TABLE IF NOT EXISTS user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY user_permission (user_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default permissions
INSERT INTO permissions (name, `key`, description) VALUES 
('View Users', 'view_users', 'Can view the list of users'),
('Manage Users', 'manage_users', 'Can add, edit, and delete users'),
('Manage Permissions', 'manage_permissions', 'Can assign and revoke permissions'),
('View Cases', 'view_cases', 'Can view cases'),
('Manage Cases', 'manage_cases', 'Can create, edit, and delete cases'),
('View Clients', 'view_clients', 'Can view clients'),
('Manage Clients', 'manage_clients', 'Can add, edit, and delete clients'),
('View Calendar', 'view_calendar', 'Can view the calendar'),
('Manage Calendar', 'manage_calendar', 'Can create and manage calendar events'),
('View Documents', 'view_documents', 'Can view documents'),
('Manage Documents', 'manage_documents', 'Can upload and manage documents'),
('View Reports', 'view_reports', 'Can view reports');
