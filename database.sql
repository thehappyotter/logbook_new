-- database.sql

CREATE DATABASE IF NOT EXISTS flightlog;
USE flightlog;

-- Users table: stores pilots/crew and their roles (with email)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') DEFAULT 'user',
  default_role ENUM('pilot','crew') DEFAULT 'pilot',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Aircraft table: list of aircraft details entered by admin
CREATE TABLE IF NOT EXISTS aircraft (
  id INT AUTO_INCREMENT PRIMARY KEY,
  registration VARCHAR(20) UNIQUE NOT NULL,
  type VARCHAR(50) NOT NULL,
  manufacturer_serial VARCHAR(50),
  subtype VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Flights table: each flight log record
CREATE TABLE IF NOT EXISTS flights (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  flight_date DATE NOT NULL,
  aircraft_id INT,  -- allow NULL if the user enters custom details
  custom_aircraft_details TEXT,  -- new column for custom aircraft details when "Other" is selected
  flight_from VARCHAR(50) NOT NULL,
  flight_to VARCHAR(50) NOT NULL,
  capacity ENUM('pilot','crew') NOT NULL,
  pilot_type ENUM('single','multi') NOT NULL,
  crew_names TEXT,
  rotors_start TIME,
  rotors_stop TIME,
  night_vision BOOL DEFAULT 0,
  night_vision_duration INT DEFAULT 0,
  takeoffs INT DEFAULT 0,
  landings INT DEFAULT 0,
  notes TEXT,
  flight_duration TIME,  -- calculated flight duration (H:i:s)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
  -- Note: aircraft_id is left without a foreign key constraint here so that it can be NULL.
);

-- Password reset tokens table
CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used BOOL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Audit trail table for tracking changes to flight records
CREATE TABLE IF NOT EXISTS audit_trail (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  flight_id INT,
  action VARCHAR(20) NOT NULL,  -- e.g., 'create', 'edit', 'delete'
  details TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);
