-- Create the database
CREATE DATABASE lab_consumables_management;
USE lab_consumables_management;

-- Organization table
CREATE TABLE organization (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL
);

-- User table
CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    role VARCHAR(50) DEFAULT 'user',
    organization_id INT NOT NULL,
    FOREIGN KEY (organization_id) REFERENCES organization(id)
);

-- Drug table
CREATE TABLE drug (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    reception_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    expiry_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    total_stock INT NOT NULL,
    used INT DEFAULT 0,
    remaining INT NOT NULL,
    losses_adjustment INT DEFAULT 0,
    received_from VARCHAR(100),
    box_count INT DEFAULT 1,
    pack_per_box INT DEFAULT 1,
    lot_batch_number VARCHAR(100),
    company_name VARCHAR(100),
    reference_number VARCHAR(100),
    reorder_limit INT NOT NULL DEFAULT 10,
    is_maintained BOOLEAN DEFAULT TRUE,
    first_used_date DATE,
    date_finished DATE,
    organization_id INT NOT NULL,
    FOREIGN KEY (organization_id) REFERENCES organization(id)
);

-- Expired drug table
CREATE TABLE expired_drug (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    reception_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    total_stock INT NOT NULL,
    used INT DEFAULT 0,
    remaining INT NOT NULL,
    received_from VARCHAR(100),
    box_count INT DEFAULT 1,
    pack_per_box INT DEFAULT 1,
    lot_batch_number VARCHAR(100),
    company_name VARCHAR(100),
    reference_number VARCHAR(100),
    reorder_limit INT NOT NULL DEFAULT 10,
    date_finished DATE,
    first_used_date DATE,
    organization_id INT NOT NULL,
    FOREIGN KEY (organization_id) REFERENCES organization(id)
);

-- Drug update log table
CREATE TABLE drug_update (
    id INT AUTO_INCREMENT PRIMARY KEY,
    drug_id INT NOT NULL,
    user_id INT NOT NULL,
    updated_amount INT NOT NULL,
    update_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (drug_id) REFERENCES drug(id),
    FOREIGN KEY (user_id) REFERENCES user(id)
);