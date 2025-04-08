# Restaurant Management System

A comprehensive web-based restaurant management system built with PHP, MySQL, and Bootstrap. This system helps restaurants manage reservations, orders, menu items, employees, and payments efficiently.

## Table of Contents
1. [Features](#features)
2. [Database Schema](#database-schema)
3. [Project Setup](#project-setup)
4. [Project Structure](#project-structure)
5. [Page Descriptions](#page-descriptions)
6. [Development Guide](#development-guide)
7. [Security](#security)
8. [Troubleshooting](#troubleshooting)

## Features

- **Customer Management**
  - Track customer information and history
  - Manage reservations and preferences

- **Table Management**
  - Real-time table availability
  - Visual seating layout
  - Occupancy tracking

- **Order Processing**
  - Digital menu interface
  - Real-time order status updates
  - Order history tracking

- **Menu Management**
  - Item availability control
  - Price management
  - Category organization

- **Employee Management**
  - Staff scheduling
  - Shift management
  - Role-based access

- **Payment Processing**
  - Multiple payment methods
  - Transaction history
  - Receipt generation

- **Admin Dashboard**
  - System statistics
  - Database maintenance
  - Backup functionality

## Database Schema

```sql
CUSTOMERS(
    id INT PRIMARY KEY,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone VARCHAR(20)
)

TABLES(
    id INT PRIMARY KEY,
    table_number INT,
    num_seats INT,
    seat_is_occupied BOOLEAN
)

RESERVATIONS(
    id INT PRIMARY KEY,
    customer_id INT,
    num_guests INT,
    reservation_date DATETIME,
    table_id INT
)

ORDER_STATUS(
    id INT PRIMARY KEY,
    status_value VARCHAR(20)
)

FOOD_ORDER(
    id INT PRIMARY KEY,
    customer_id INT,
    order_status_id INT,
    table_id INT,
    order_date DATETIME,
    total_price DECIMAL(10,2)
)

MENU_ITEM(
    id INT PRIMARY KEY,
    item_name VARCHAR(100),
    price DECIMAL(10,2),
    stock_availability BOOLEAN
)

ORDER_MENU_ITEM(
    id INT PRIMARY KEY,
    order_id INT,
    menu_item_id INT,
    qty_ordered INT
)

PAYMENT(
    id INT PRIMARY KEY,
    order_id INT,
    payment_method VARCHAR(50),
    amount_paid DECIMAL(10,2),
    payment_date DATETIME,
    payment_status VARCHAR(20)
)

EMPLOYEES(
    id INT PRIMARY KEY,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    role VARCHAR(20),
    phone VARCHAR(20),
    email VARCHAR(100)
)

SCHEDULES(
    id INT PRIMARY KEY,
    employee_id INT,
    shift_date DATE,
    start_time TIME,
    end_time TIME
)
```

## Project Setup

### Prerequisites
- XAMPP 8.0 or higher (includes PHP 8.0+, MySQL 8.0+, Apache)
- Web browser (Chrome/Firefox recommended)
- Git (optional)

### Installation Steps

1. **Install XAMPP**
   - Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - Install with default options
   - Start Apache and MySQL modules

2. **Set Up Project Files**
   ```bash
   # Navigate to XAMPP's htdocs
   cd C:\xampp\htdocs

   # Clone or copy project
   git clone [repository-url] restaurant
   # OR copy files manually to C:\xampp\htdocs\restaurant
   ```

3. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create new database 'restaurant_db'
   - Import schema from `config/init_db.sql`

4. **Configure Database Connection**
   - Open `config/database.php`
   - Update credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'restaurant_db');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // Update if you set a password
   ```

5. **Access Application**
   - Open http://localhost/restaurant in your browser
   - Default admin credentials:
     - Username: admin
     - Password: admin123

## Project Structure

```
restaurant/
├── config/                 # Configuration files
│   ├── database.php        # Database connection
│   └── init_db.sql         # Database schema
├── css/                    # Stylesheets
│   ├── style.css          # Custom styles
│   └── bootstrap.min.css   # Bootstrap framework
├── js/                     # JavaScript files
│   ├── main.js            # Custom scripts
│   └── validation.js      # Form validation
├── includes/              # Reusable components
│   ├── header.php         # Page header
│   ├── footer.php         # Page footer
│   ├── nav.php            # Navigation menu
│   └── functions.php      # Helper functions
├── pages/                 # Main application pages
│   ├── reservations.php   # Reservation management
│   ├── menu.php          # Menu display/management
│   ├── orders.php        # Order processing
│   ├── employees.php     # Staff management
│   ├── payments.php      # Payment processing
│   └── admin.php         # Admin dashboard
├── uploads/              # Uploaded files (images)
├── backups/              # Database backups
├── index.php             # Home page
└── README.md            # Documentation
```

## Page Descriptions

### index.php (Home Page)
- Dashboard overview
- Quick access to key features
- Real-time statistics
- Today's reservations and orders

### pages/reservations.php
- Table availability calendar
- Reservation form
- Reservation list/management
- Table status visualization

### pages/menu.php
- Menu item display
- Category management
- Price updates
- Stock control

### pages/orders.php
- Order creation interface
- Real-time order tracking
- Order modification
- Order history

### pages/employees.php
- Staff information
- Schedule management
- Shift assignment
- Contact details

### pages/payments.php
- Payment processing
- Transaction history
- Receipt generation
- Payment method management

### pages/admin.php
- System statistics
- User management
- Database maintenance
- Configuration settings

## Development Guide

### Adding New Features
1. Create necessary database tables
2. Add corresponding PHP files in `/pages`
3. Update navigation in `includes/nav.php`
4. Add any required JS/CSS in respective folders

### Code Standards
- Use prepared statements for all queries
- Validate all user inputs
- Follow PSR-12 coding style
- Comment complex logic

### Common Functions (includes/functions.php)
- Database operations
- Input validation
- Date/time handling
- Error logging

## Security

### Database Security
- PDO prepared statements
- Input validation/sanitization
- SQL injection prevention

### Authentication
- Session management
- Password hashing (bcrypt)
- Role-based access control

### Data Protection
- XSS prevention
- CSRF tokens
- Secure file uploads

## Troubleshooting

### Common Issues
1. **Database Connection Failed**
   - Verify MySQL is running
   - Check credentials in database.php
   - Confirm database exists

2. **Permission Errors**
   - Check folder permissions
   - Verify PHP has write access
   - Check file ownership

3. **Page Not Found**
   - Confirm Apache is running
   - Check file paths
   - Verify .htaccess settings

### Support
- Report issues on GitHub
- Check documentation
- Contact development team

## Contributing

1. Fork the repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

## License

MIT License - See LICENSE file for details
