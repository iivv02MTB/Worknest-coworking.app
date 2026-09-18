CREATE DATABASE IF NOT EXISTS worknest
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE worknest;

CREATE TABLE spaces(
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    capacity INT NOT NULL,
    price_per_hour DECIMAL(10, 2) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    active TINYINT(1) DEFAULT 1
);

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admim') NOT NULL DEFAULT 'customer'
);

CREATE TABLE bookings(
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id INT UNSIGNED NOT NULL,
    space_id INT UNSIGNED NOT NULL,

    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,

    status ENUM(
        'pending',
        'confirmed',
        'cancelled'
    )NOT NULL DEFAULT 'pending',

    total_price DECIMAL (10, 2) NOT NULL,

    CONSTRAINT fk_bookings_user
    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,

    CONSTRAINT fk_bookings_space
        FOREIGN KEY (space_id)
        REFERENCES spaces(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

INSERT INTO spaces
    (name, type, capacity, price_per_hour, description, image, active)
VALUES

(
    'Common Room',
    'flex desk',
    1,
    6.00,
    'A comfortable shared workspace for focused individual work.',
    'img/common-room.jpg',
    TRUE
),

(
    'Window Desk',
    'flex desk',
    1,
    7.00,
    'A bright desk next to the window with natural light.',
    'img/window-desk.jpg',
    TRUE
),

(
    'Oak Room',
    'meeting room',
    5,
    18.00,
    'A private meeting room suitable for small teams.',
    'img/oak-room.jpg',
    TRUE
),

(
    'Cedar Room',
    'meeting room',
    4,
    14.00,
    'A quiet private room for meetings and collaborative work.',
    'img/cedar-room.jpg',
    TRUE
),

(
    'Studio 3',
    'private office',
    5,
    35.00,
    'A spacious private office for teams that need more room.',
    'img/studio-3.jpg',
    TRUE
),

(
    'Studio 5',
    'private office',
    3,
    28.00,
    'A private office designed for focused team work.',
    'img/studio-5.jpg',
    TRUE
);

CREATE INDEX idx_spaces_type
    ON spaces(type);

CREATE INDEX idx_spaces_active_price
    ON spaces(active, price_per_hour);

CREATE INDEX idx_bookings_space_date
    ON bookings(space_id, booking_date);
