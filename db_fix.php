<?php
require_once 'db.php';

// This script will fix database issues and add test data

// Check if drivers table exists, if not create it
$sql = "SHOW TABLES LIKE 'drivers'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    $sql = "CREATE TABLE drivers (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        vehicle_type VARCHAR(50) NOT NULL,
        vehicle_number VARCHAR(20) NOT NULL,
        license_number VARCHAR(50) NOT NULL,
        status ENUM('Available', 'Busy', 'Offline') DEFAULT 'Available',
        rating DECIMAL(3,1) DEFAULT 4.5,
        wallet_balance DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    echo "Drivers table created.<br>";
}

// Add test drivers if none exist
$sql = "SELECT COUNT(*) as count FROM drivers";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    // Add 5 test drivers
    $drivers = [
        ['name' => 'Ahmed Khan', 'phone' => '03001234567', 'vehicle_type' => 'Toyota Corolla', 'vehicle_number' => 'ABC-123'],
        ['name' => 'Bilal Ahmed', 'phone' => '03011234567', 'vehicle_type' => 'Honda City', 'vehicle_number' => 'XYZ-456'],
        ['name' => 'Farhan Ali', 'phone' => '03021234567', 'vehicle_type' => 'Suzuki Swift', 'vehicle_number' => 'DEF-789'],
        ['name' => 'Kamran Shah', 'phone' => '03031234567', 'vehicle_type' => 'Toyota Prius', 'vehicle_number' => 'GHI-101'],
        ['name' => 'Zubair Hassan', 'phone' => '03041234567', 'vehicle_type' => 'Honda Civic', 'vehicle_number' => 'JKL-202']
    ];
    
    foreach ($drivers as $driver) {
        $sql = "INSERT INTO drivers (name, phone, email, password, vehicle_type, vehicle_number, license_number, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Available')";
        $stmt = $conn->prepare($sql);
        $email = strtolower(str_replace(' ', '.', $driver['name'])) . '@example.com';
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $license = 'LIC-' . rand(10000, 99999);
        $stmt->bind_param("sssssss", $driver['name'], $driver['phone'], $email, $password, $driver['vehicle_type'], $driver['vehicle_number'], $license);
        $stmt->execute();
        $stmt->close();
    }
    echo "5 test drivers added.<br>";
}

// Check if rides table has all required columns
$sql = "SHOW COLUMNS FROM rides";
$result = $conn->query($sql);
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

// Add missing columns if needed
$required_columns = [
    'driver_id' => "ALTER TABLE rides ADD COLUMN driver_id INT(11) NULL AFTER user_id",
    'accepted_at' => "ALTER TABLE rides ADD COLUMN accepted_at TIMESTAMP NULL AFTER created_at",
    'started_at' => "ALTER TABLE rides ADD COLUMN started_at TIMESTAMP NULL AFTER accepted_at",
    'completed_at' => "ALTER TABLE rides ADD COLUMN completed_at TIMESTAMP NULL AFTER started_at",
    'cancelled_at' => "ALTER TABLE rides ADD COLUMN cancelled_at TIMESTAMP NULL AFTER completed_at",
    'cancellation_reason' => "ALTER TABLE rides ADD COLUMN cancellation_reason VARCHAR(255) NULL AFTER cancelled_at",
    'actual_fare' => "ALTER TABLE rides ADD COLUMN actual_fare DECIMAL(10,2) NULL AFTER estimated_fare",
    'payment_status' => "ALTER TABLE rides ADD COLUMN payment_status ENUM('Pending', 'Paid', 'Failed') DEFAULT 'Pending' AFTER actual_fare"
];

foreach ($required_columns as $column => $sql) {
    if (!in_array($column, $columns)) {
        $conn->query($sql);
        echo "Added missing column: $column<br>";
    }
}

echo "<br>Database structure fixed successfully. <a href='dashboard.php'>Go to Dashboard</a>";
?>
