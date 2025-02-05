-- database.sql
DROP DATABASE IF EXISTS flightlog;
CREATE DATABASE IF NOT EXISTS flightlog;
USE flightlog;

-- Table: bases (organisation bases)
CREATE TABLE IF NOT EXISTS bases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  base_name VARCHAR(100) NOT NULL,
  base_code VARCHAR(50) DEFAULT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: users (with default_base referencing bases)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') DEFAULT 'user',
  default_role ENUM('pilot','crew') DEFAULT 'pilot',
  default_base INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (default_base) REFERENCES bases(id)
);

-- Table: aircraft
CREATE TABLE IF NOT EXISTS aircraft (
  id INT AUTO_INCREMENT PRIMARY KEY,
  registration VARCHAR(20) UNIQUE NOT NULL,
  type VARCHAR(50) NOT NULL,
  manufacturer_serial VARCHAR(50),
  subtype VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: flights  
-- (Note: aircraft_id is allowed to be NULL so that manual aircraft entries work;
-- custom_aircraft_details stores the manually entered info.)
CREATE TABLE IF NOT EXISTS flights (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  flight_date DATE NOT NULL,
  aircraft_id INT NULL,
  custom_aircraft_details TEXT,
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
  flight_duration TIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table: flight_breakdown (stores breakdown of flight time by role)
CREATE TABLE IF NOT EXISTS flight_breakdown (
  id INT AUTO_INCREMENT PRIMARY KEY,
  flight_id INT NOT NULL,
  role VARCHAR(50) NOT NULL,
  duration_minutes INT NOT NULL,
  FOREIGN KEY (flight_id) REFERENCES flights(id)
);

-- Table: password_reset_tokens
CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used BOOL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table: audit_trail
CREATE TABLE IF NOT EXISTS audit_trail (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  flight_id INT,
  action VARCHAR(20) NOT NULL,
  details TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);
