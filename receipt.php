<?php
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirectJS('login.php');
}

// Check if ride ID is provided
if (!isset($_GET['id'])) {
    redirectJS('dashboard.php');
}

$ride_id = sanitize($_GET['id']);
$user_id = $_SESSION['user_id'];

// Get ride details
$sql = "SELECT r.*, d.name as driver_name, d.phone as driver_phone, d.vehicle_type, d.vehicle_number, d.rating 
        FROM rides r 
        LEFT JOIN drivers d ON r.driver_id = d.id 
        WHERE r.id = ? AND r.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $ride_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirectJS('dashboard.php');
}

$ride = $result->fetch_assoc();
$stmt->close();

// Check if ride is completed
if ($ride['status'] !== 'Completed') {
    redirectJS('tracking.php?id=' . $ride_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ride Receipt - Careem Clone</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            color: #333;
            min-height: 100vh;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        header {
            background-color: #49b649;
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        
        .logo span {
            color: #e6e6e6;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 20px;
        }
        
        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        nav ul li a:hover {
            color: #e6e6e6;
        }
        
        .receipt-content {
            padding: 40px 0;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .receipt-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .receipt-header p {
            color: #666;
        }
        
        .receipt-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .receipt-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .receipt-logo {
            font-size: 24px;
            font-weight: bold;
            color: #49b649;
        }
        
        .receipt-info {
            text-align: right;
        }
        
        .receipt-id {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .receipt-date {
            font-size: 14px;
            color: #666;
        }
        
        .receipt-status {
            display: inline-block;
            padding: 5px 10px;
            background-color: #d4edda;
            color: #155724;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin-top: 5px;
        }
        
        .receipt-details {
            margin-bottom: 30px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 20px;
        }
        
        .detail-col {
            flex: 1;
        }
        
        .detail-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 16px;
            font-weight: 500;
            color: #333;
        }
        
        .receipt-map {
            height: 200px;
            background-color: #f0f0f0;
            border-radius: 8px;
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .receipt-summary {
            margin-bottom: 30px;
        }
        
        .summary-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .summary-item:last-child {
            border-bottom: none;
            font-weight: 600;
            padding-top: 15px;
            margin-top: 5px;
        }
        
        .summary-label {
            color: #666;
        }
        
        .summary-value {
            color: #333;
        }
        
        .receipt-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .action-btn {
            flex: 1;
            padding: 12px;
            text-align: center;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .primary-btn {
            background-color: #49b649;
            color: white;
        }
        
        .primary-btn:hover {
            background-color: #3a9c3a;
        }
        
        .secondary-btn {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .secondary-btn:hover {
            background-color: #e9ecef;
        }
        
        .thank-you {
            text-align: center;
            margin-top: 30px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .detail-row {
                flex-direction: column;
            }
            
            .detail-col {
                margin-bottom: 20px;
            }
            
            .receipt-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-content">
            <div class="logo">Careem<span>Clone</span></div>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="booking.php">Book a Ride</a></li>
                    <li><a href="history.php">Ride History</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="receipt-content">
        <div class="container">
            <div class="receipt-header">
                <h1>Ride Receipt</h1>
                <p>Thank you for riding with us!</p>
            </div>
            
            <div class="receipt-card">
                <div class="receipt-top">
                    <div class="receipt-logo">CareemClone</div>
                    <div class="receipt-info">
                        <div class="receipt-id">Receipt #<?php echo str_pad($ride['id'], 6, '0', STR_PAD_LEFT); ?></div>
                        <div class="receipt-date"><?php echo date('F d, Y h:i A', strtotime($ride['completed_at'])); ?></div>
                        <div class="receipt-status">Completed</div>
                    </div>
                </div>
                
                <div class="receipt-details">
                    <div class="detail-row">
                        <div class="detail-col">
                            <div class="detail-label">Pickup Location</div>
                            <div class="detail-value"><?php echo $ride['pickup_location']; ?></div>
                        </div>
                        <div class="detail-col">
                            <div class="detail-label">Drop-off Location</div>
                            <div class="detail-value"><?php echo $ride['dropoff_location']; ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-col">
                            <div class="detail-label">Date & Time</div>
                            <div class="detail-value"><?php echo date('F d, Y h:i A', strtotime($ride['created_at'])); ?></div>
                        </div>
                        <div class="detail-col">
                            <div class="detail-label">Ride Type</div>
                            <div class="detail-value"><?php echo $ride['ride_type']; ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-col">
                            <div class="detail-label">Driver</div>
                            <div class="detail-value"><?php echo $ride['driver_name']; ?></div>
                        </div>
                        <div class="detail-col">
                            <div class="detail-label">Vehicle</div>
                            <div class="detail-value"><?php echo $ride['vehicle_type'] . ' - ' . $ride['vehicle_number']; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="receipt-map">
                    <!-- Map would be displayed here in a real app -->
                    <div style="height: 100%; display: flex; align-items: center; justify-content: center; flex-direction: column; background-color: #f0f0f0;">
                        <p>Route from <?php echo $ride['pickup_location']; ?> to <?php echo $ride['dropoff_location']; ?></p>
                        <p>Distance: <?php echo $ride['distance']; ?> km</p>
                    </div>
                </div>
                
                <div class="receipt-summary">
                    <h3 class="summary-title">Payment Summary</h3>
                    
                    <div class="summary-item">
                        <div class="summary-label">Base Fare</div>
                        <div class="summary-value">Rs. 50.00</div>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-label">Distance Charge (<?php echo $ride['distance']; ?> km)</div>
                        <div class="summary-value">Rs. <?php echo number_format($ride['distance'] * ($ride['ride_type'] === 'Economy' ? 15 : ($ride['ride_type'] === 'Business' ? 25 : 35)), 2); ?></div>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-label">Service Fee</div>
                        <div class="summary-value">Rs. <?php echo number_format($ride['actual_fare'] * 0.1, 2); ?></div>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-label">Total Amount</div>
                        <div class="summary-value">Rs. <?php echo number_format($ride['actual_fare'], 2); ?></div>
                    </div>
                </div>
                
                <div class="receipt-actions">
                    <a href="history.php" class="action-btn secondary-btn">View Ride History</a>
                    <a href="booking.php" class="action-btn primary-btn">Book Another Ride</a>
                </div>
                
                <div class="thank-you">
                    <p>Thank you for choosing CareemClone!</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
