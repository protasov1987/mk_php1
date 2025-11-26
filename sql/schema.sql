CREATE TABLE IF NOT EXISTS access_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS access_level_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    access_level_id INT NOT NULL,
    resource_code VARCHAR(100) NOT NULL,
    allowed TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (access_level_id) REFERENCES access_levels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    access_level_id INT NOT NULL,
    password1_hash VARCHAR(255) NOT NULL,
    password2_ean13 VARCHAR(13) NULL,
    password2_hash VARCHAR(255) NULL,
    is_observer TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (access_level_id) REFERENCES access_levels(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS route_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    number VARCHAR(50) NOT NULL,
    order_number VARCHAR(100) NULL,
    title VARCHAR(255) NOT NULL,
    status ENUM('draft','in_progress','done','paused','cancelled') NOT NULL DEFAULT 'draft',
    created_by INT,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS route_operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_card_id INT NOT NULL,
    operation_number INT,
    name VARCHAR(255) NOT NULL,
    subdivision VARCHAR(255) NULL,
    planned_time_min INT,
    status ENUM('waiting','in_progress','done','paused','cancelled') DEFAULT 'waiting',
    position INT,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (route_card_id) REFERENCES route_cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS operation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_operation_id INT NOT NULL,
    user_id INT NOT NULL,
    action ENUM('start','pause','resume','finish','cancel') NOT NULL,
    timestamp DATETIME,
    comment TEXT NULL,
    FOREIGN KEY (route_operation_id) REFERENCES route_operations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
