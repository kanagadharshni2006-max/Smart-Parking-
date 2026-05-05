<?php
require_once 'config.php';

// Create users table
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('User', 'Admin') DEFAULT 'User',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_users) === TRUE) {
    echo "Table 'users' created successfully.<br>";
} else {
    echo "Error creating table 'users': " . $conn->error . "<br>";
}

// Create slots table
$sql_slots = "CREATE TABLE IF NOT EXISTS slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slot_number VARCHAR(10) NOT NULL UNIQUE,
    floor VARCHAR(10) NOT NULL,
    status ENUM('Available', 'Booked', 'Reserved') DEFAULT 'Available',
    vehicle_type ENUM('Car', 'Motorcycle') DEFAULT 'Car',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_slots) === TRUE) {
    echo "Table 'slots' created successfully.<br>";
} else {
    echo "Error creating table 'slots': " . $conn->error . "<br>";
}

// Create bookings table (updated to include user_id)
$sql_bookings = "CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    slot_id INT NOT NULL,
    vehicle_number VARCHAR(20) NOT NULL,
    vehicle_model VARCHAR(50),
    arrival_time TIME NOT NULL,
    duration INT NOT NULL,
    total_price DECIMAL(10, 2),
    status ENUM('Confirmed', 'Cancelled', 'Completed') DEFAULT 'Confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (slot_id) REFERENCES slots(id)
)";

if ($conn->query($sql_bookings) === TRUE) {
    echo "Table 'bookings' created successfully.<br>";
} else {
    echo "Error creating table 'bookings': " . $conn->error . "<br>";
}

// Seed admin user if not exists
$check_admin = $conn->query("SELECT * FROM users WHERE role = 'Admin'");
if ($check_admin->num_rows == 0) {
    $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (name, email, password, role) VALUES ('Admin User', 'admin@smartpark.com', '$admin_pass', 'Admin')");
    echo "Default admin created: admin@smartpark.com / admin123<br>";
}

// Seed slots if empty
$check_slots = $conn->query("SELECT COUNT(*) as count FROM slots");
$row = $check_slots->fetch_assoc();
if ($row['count'] == 0) {
    for ($i = 1; $i <= 32; $i++) {
        $slot_num = "A-" . ($i < 10 ? "0$i" : $i);
        $conn->query("INSERT INTO slots (slot_number, floor, status) VALUES ('$slot_num', 'B1', 'Available')");
    }
    echo "Seeded 32 slots.<br>";
}

echo "<br>Database setup complete. <a href='index.html'>Go to Home</a>";
?>
