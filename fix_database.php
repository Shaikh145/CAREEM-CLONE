<?php
require_once 'db.php';

echo "<h1>Fixing Database Issues</h1>";

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
    if ($conn->query($sql)) {
        echo "<p>✅ Drivers table created successfully.</p>";
    } else {
        echo "<p>❌ Error creating drivers table: " . $conn->error . "</p>";
    }
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
    
    $success = true;
    foreach ($drivers as $driver) {
        $sql = "INSERT INTO drivers (name, phone, email, password, vehicle_type, vehicle_number, license_number, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Available')";
        $stmt = $conn->prepare($sql);
        $email = strtolower(str_replace(' ', '.', $driver['name'])) . '@example.com';
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $license = 'LIC-' . rand(10000, 99999);
        $stmt->bind_param("sssssss", $driver['name'], $driver['phone'], $email, $password, $driver['vehicle_type'], $driver['vehicle_number'], $license);
        if (!$stmt->execute()) {
            echo "<p>❌ Error adding driver " . $driver['name'] . ": " . $stmt->error . "</p>";
            $success = false;
        }
        $stmt->close();
    }
    if ($success) {
        echo "<p>✅ 5 test drivers added successfully.</p>";
    }
}

// Check if rides table has all required columns
$sql = "SHOW COLUMNS FROM rides";
$result = $conn->query($sql);
if (!$result) {
    echo "<p>❌ Error checking rides table: " . $conn->error . "</p>";
} else {
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
    
    $added_columns = 0;
    foreach ($required_columns as $column => $sql) {
        if (!in_array($column, $columns)) {
            if ($conn->query($sql)) {
                echo "<p>✅ Added missing column: $column</p>";
                $added_columns++;
            } else {
                echo "<p>❌ Error adding column $column: " . $conn->error . "</p>";
            }
        }
    }
    
    if ($added_columns == 0) {
        echo "<p>✅ All required columns already exist in rides table.</p>";
    }
}

// Make sure all drivers are set to Available
$sql = "UPDATE drivers SET status = 'Available' WHERE status != 'Available'";
if ($conn->query($sql)) {
    echo "<p>✅ Reset all drivers to Available status.</p>";
} else {
    echo "<p>❌ Error resetting driver status: " . $conn->error . "</p>";
}

echo "<p><strong>Database fixes completed!</strong></p>";
echo "<p>Now you can <a href='dashboard.php'>go to Dashboard</a> and book a ride.</p>";
echo "<p>When tracking your ride, click the 'Simulate Driver Accept' button to manually assign a driver.</p>";
?>
