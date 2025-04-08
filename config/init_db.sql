CREATE DATABASE IF NOT EXISTS restaurant_db;
USE restaurant_db;

-- Customers table
CREATE TABLE IF NOT EXISTS CUSTOMERS (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NOT NULL
);

-- Tables table
CREATE TABLE IF NOT EXISTS TABLES (
    id INT PRIMARY KEY AUTO_INCREMENT,
    table_number INT NOT NULL UNIQUE,
    num_seats INT NOT NULL,
    seat_is_occupied BOOLEAN DEFAULT FALSE
);

-- Reservations table
CREATE TABLE IF NOT EXISTS RESERVATIONS (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    num_guests INT NOT NULL,
    reservation_date DATETIME NOT NULL,
    table_id INT NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES CUSTOMERS(id),
    FOREIGN KEY (table_id) REFERENCES TABLES(id)
);

-- Order status table
CREATE TABLE IF NOT EXISTS ORDER_STATUS (
    id INT PRIMARY KEY AUTO_INCREMENT,
    status_value VARCHAR(20) NOT NULL
);

-- Food order table
CREATE TABLE IF NOT EXISTS FOOD_ORDER (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    order_status_id INT NOT NULL,
    table_id INT NOT NULL,
    order_date DATETIME NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES CUSTOMERS(id),
    FOREIGN KEY (order_status_id) REFERENCES ORDER_STATUS(id),
    FOREIGN KEY (table_id) REFERENCES TABLES(id)
);

-- Menu items table
CREATE TABLE IF NOT EXISTS MENU_ITEM (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock_availability BOOLEAN DEFAULT TRUE
);

-- Order items table
CREATE TABLE IF NOT EXISTS ORDER_MENU_ITEM (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    qty_ordered INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES FOOD_ORDER(id),
    FOREIGN KEY (menu_item_id) REFERENCES MENU_ITEM(id)
);

-- Payment table
CREATE TABLE IF NOT EXISTS PAYMENT (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    payment_method VARCHAR(50),
    amount_paid DECIMAL(10,2),
    payment_date DATETIME,
    payment_status VARCHAR(20),
    FOREIGN KEY (order_id) REFERENCES FOOD_ORDER(id)
);

-- Employees table
CREATE TABLE IF NOT EXISTS EMPLOYEES (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role VARCHAR(20) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL
);

-- Schedules table
CREATE TABLE IF NOT EXISTS SCHEDULES (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    shift_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (employee_id) REFERENCES EMPLOYEES(id)
);

-- Insert initial order status values
INSERT INTO ORDER_STATUS (status_value) VALUES 
('Pending'),
('Preparing'),
('Ready'),
('Delivered'),
('Cancelled');

-- Insert initial restaurant tables
INSERT INTO TABLES (table_number, num_seats, seat_is_occupied) VALUES
(1, 2, FALSE),  -- Table for 2 people
(2, 2, FALSE),
(3, 4, FALSE),  -- Table for 4 people
(4, 4, FALSE),
(5, 4, FALSE),
(6, 6, FALSE),  -- Table for 6 people
(7, 6, FALSE),
(8, 8, FALSE),  -- Table for 8 people
(9, 8, FALSE),
(10, 10, FALSE); -- Table for 10 people
