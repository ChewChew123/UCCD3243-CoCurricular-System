
CREATE DATABASE IF NOT EXISTS co_curricular_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE co_curricular_db;


CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clubs (
    club_id INT AUTO_INCREMENT PRIMARY KEY,
    club_name VARCHAR(100) NOT NULL,
    club_category VARCHAR(50),
    club_description TEXT
);


CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NULL, 
    event_name VARCHAR(150) NOT NULL,
    event_type VARCHAR(50),
    event_location VARCHAR(150),
    event_date DATE,
    FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE SET NULL
);


CREATE TABLE club_members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    club_id INT NOT NULL,
    member_role VARCHAR(50) DEFAULT 'Member',
    member_status ENUM('Active', 'Inactive') DEFAULT 'Active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE
);


CREATE TABLE event_participants (
    participant_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    participant_status ENUM('Registered', 'Attended', 'Absent') DEFAULT 'Registered',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
);


CREATE TABLE merits (
    merit_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NULL, 
    hours DECIMAL(5,2) NOT NULL,
    organizer VARCHAR(100),
    date_completed DATE NOT NULL,
    merit_description TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE SET NULL
);


CREATE TABLE achievements (
    achievement_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NULL, 
    achievement_title VARCHAR(150) NOT NULL,
    achievement_category ENUM('Academic', 'Sports', 'Arts', 'Leadership', 'Others') DEFAULT 'Others',
    level VARCHAR(50),
    issuer VARCHAR(100) NOT NULL,
    date_received DATE NOT NULL,
    achievement_description TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE SET NULL
);