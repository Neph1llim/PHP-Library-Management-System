-- Development feature: remove when finished
-- DROP DATABASE IF EXISTS library_management_system;
-- CREATE DATABASE library_management_system;
-- USE library_management_system;

-- =========================
-- TABLE: categories
-- =========================
CREATE TABLE categories (
    category_id   INT NOT NULL AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    PRIMARY KEY (category_id)
);

-- =========================
-- TABLE: users
-- =========================
CREATE TABLE users (
    user_id           INT NOT NULL AUTO_INCREMENT,
    first_name        VARCHAR(50) NOT NULL,
    middle_name       VARCHAR(50) DEFAULT NULL,
    last_name         VARCHAR(50) NOT NULL,
    email             VARCHAR(150) NOT NULL UNIQUE,
    password          VARCHAR(255) NOT NULL,
    contact_number    VARCHAR(20) DEFAULT NULL,
    address           TEXT DEFAULT NULL,
    user_type         ENUM('User','Admin') NOT NULL DEFAULT 'User',
    registration_date DATE NOT NULL DEFAULT (CURRENT_DATE),

    PRIMARY KEY (user_id),
    INDEX idx_user_email (email),
    INDEX idx_user_type (user_type)
);

-- =========================
-- TABLE: items
-- =========================
CREATE TABLE items (
    item_id            INT NOT NULL AUTO_INCREMENT,
    title              VARCHAR(255) NOT NULL,
    author             VARCHAR(255) NOT NULL,
    isbn               VARCHAR(20) DEFAULT NULL UNIQUE,
    publisher          VARCHAR(150) DEFAULT NULL,
    publication_year   YEAR DEFAULT NULL,
    category_id        INT NOT NULL,
    quantity_available INT NOT NULL DEFAULT 1,
    shelf_location     VARCHAR(50) DEFAULT NULL,
    item_status        ENUM('Available','Borrowed','Reserved') NOT NULL DEFAULT 'Available',

    PRIMARY KEY (item_id),
    INDEX idx_item_title (title),
    INDEX idx_item_author (author),
    INDEX idx_item_category (category_id),

    CONSTRAINT fk_item_category
        FOREIGN KEY (category_id)
        REFERENCES categories (category_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- =========================
-- TABLE: schedule
-- =========================
CREATE TABLE schedule (
    schedule_id     INT NOT NULL AUTO_INCREMENT,
    user_id         INT NOT NULL,
    item_id         INT NOT NULL,
    reserve_date    DATE NOT NULL DEFAULT (CURRENT_DATE),
    expiration_date DATE NOT NULL,
    status          ENUM('Pending','Approved','Cancelled') NOT NULL DEFAULT 'Pending',

    PRIMARY KEY (schedule_id),
    INDEX idx_schedule_user (user_id),
    INDEX idx_schedule_item (item_id),
    INDEX idx_schedule_status (status),

    CONSTRAINT fk_schedule_user
        FOREIGN KEY (user_id)
        REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_schedule_item
        FOREIGN KEY (item_id)
        REFERENCES items (item_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

-- =========================
-- TABLE: history
-- =========================
CREATE TABLE history (
    history_id    INT NOT NULL AUTO_INCREMENT,
    user_id       INT NOT NULL,
    item_id       INT NOT NULL,
    borrowed_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    due_date      DATE NOT NULL,
    return_date   DATE DEFAULT NULL,
    borrow_status ENUM('Borrowed','Returned','Overdue') NOT NULL DEFAULT 'Borrowed',

    PRIMARY KEY (history_id),
    INDEX idx_history_user (user_id),
    INDEX idx_history_item (item_id),
    INDEX idx_history_status (borrow_status),

    CONSTRAINT fk_history_user
        FOREIGN KEY (user_id)
        REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_history_item
        FOREIGN KEY (item_id)
        REFERENCES items (item_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

-- =========================
-- TABLE: penalties
-- =========================
CREATE TABLE penalties (
    penalty_id     INT NOT NULL AUTO_INCREMENT,
    history_id     INT NOT NULL,
    penalty_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_status ENUM('Unpaid','Paid','Waived') NOT NULL DEFAULT 'Unpaid',
    payment_date   DATE DEFAULT NULL,

    PRIMARY KEY (penalty_id),
    INDEX idx_penalty_history (history_id),
    INDEX idx_penalty_status (payment_status),

    CONSTRAINT fk_penalty_history
        FOREIGN KEY (history_id)
        REFERENCES history (history_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);
