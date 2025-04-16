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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ride Details - Careem Clone</title>
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
            max-width: 1200px;
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
        
        .details-content {
            padding: 40px 0;
        }
        
        .details-header {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .details-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .details-header p {
            color: #666;
        }
        
        .details-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .details-top {
            background-color: #49b649;
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .details-top-left h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .details-top-left p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            background-color: white;
        }
        
        .status-pending {
            color: #856404;
        }
        
        .status-accepted {
            color: #004085;
        }
        
        .status-in-progress {
            color: #0c5460;
        }
        
        .status-completed {
            color: #155724;
        }
        
        .status-cancelled {
            color: #721c24;
        }
        
        .details-body {
            padding: 30px;
        }
        
        .details-section {
            margin-bottom: 30px;
        }
        
        .details-section:last-child {
            margin-bottom: 0;
        }
        
        .details-section h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .details-map {
            height: 200px;
            background-color: #f0f0f0;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
        }
        
        .details-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .details-label {
            color: #666;
        }
        
        .details-value {
            font-weight: 500;
            color: #333;
        }
        
        .driver-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .driver-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: rgba(73, 182, 73, 0.1);
            color: #49b649;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .driver-details h4 {
            font-size: 16px;
            margin-bottom: 5px;
            color: #333;
        }
        
        .driver-rating {
            display: flex;
            align-items: center;
            color: #ffc107;
        }
        
        .driver-rating span {
            color: #666;
            margin-left: 5px;
            font-size: 14px;
        }
        
        .details-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        .details-btn {
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
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
        
        @media (max-width: 768px) {
            .details-top {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .status-badge {
                margin-top: 10px;
            }
            
            .details-actions {
                flex-direction: column;
            }
            
            .details-btn {
                text-align: center;
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
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="details-content">
        <div class="container">
            <div class="details-header">
                <h1>Ride Details</h1>
                <p>View detailed information about your ride</p>
            </div>
            
            <div class="details-container">
                <div class="details-top">
                    <div class="details-top-left">
                        <h2>Ride #<?php echo str_pad($ride['id'], 6, '0', STR_PAD_LEFT); ?></h2>
                        <p><?php echo date('M d, Y h:i A', strtotime($ride['created_at'])); ?></p>
                    </div>
                    
                    <?php
                    $statusClass = '';
                    switch ($ride['status']) {
                        case 'Pending':
                            $statusClass = 'status-pending';
                            break;
                        case 'Accepted':
                            $statusClass = 'status-accepted';
                            break;
                        case 'In Progress':
                            $statusClass = 'status-in-progress';
                            break;
                        case 'Completed':
                            $statusClass = 'status-completed';
                            break;
                        case 'Cancelled':
                            $statusClass = 'status-cancelled';
                            break;
                    }
                    ?>
                    <div class="status-badge <?php echo $statusClass; ?>"><?php echo $ride['status']; ?></div>
                </div>
                
                <div class="details-body">
                    <div class="details-section">
                        <h3>Ride Information</h3>
                        <div class="details-map">
                            Map showing route from <?php echo $ride['pickup_location']; ?> to <?php echo $ride['dropoff_location']; ?>
                        </div>
                        <div class="details-row">
                            <div class="details-label">Pickup Location</div>
                            <div class="details-value"><?php echo $ride['pickup_location']; ?></div>
                        </div>
                        <div class="details-row">
                            <div class="details-label">Drop-off Location</div>
                            <div class="details-value"><?php echo $ride['dropoff_location']; ?></div>
                        </div>
                        <div class="details-row">
                            <div class="details-label">Distance</div>
                            <div class="details-value"><?php echo $ride['distance']; ?> km</div>
                        </div>
                        <div class="details-row">
                            <div class="details-label">Ride Type</div>
                            <div class="details-value"><?php echo $ride['ride_type']; ?></div>
                        </div>
                        <div class="details-row">
                            <div class="details-label">Payment Method</div>
                            <div class="details-value"><?php echo $ride['payment_method']; ?></div>
                        </div>
                    </div>
                    
                    <?php if ($ride['driver_name']): ?>
                        <div class="details-section">
                            <h3>Driver Information</h3>
                            <div class="driver-info">
                                <div class="driver-avatar"><?php echo substr($ride['driver_name'], 0, 1); ?></div>
                                <div class="driver-details">
                                    <h4><?php echo $ride['driver_name']; ?></h4>
                                    <div class="driver-rating">
                                        ★★★★★ <span><?php echo number_format($ride['rating'], 1); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="details-row">
                                <div class="details-label">Phone Number</div>
                                <div class="details-value"><?php echo $ride['driver_phone']; ?></div>
                            </div>
                            <div class="details-row">
                                <div class="details-label">Vehicle</div>
                                <div class="details-value"><?php echo $ride['vehicle_type']; ?> - <?php echo $ride['vehicle_number']; ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="details-section">
                        <h3>Payment Details</h3>
                        <div class="details-row">
                            <div class="details-label">Estimated Fare</div>
                            <div class="details-value">Rs. <?php echo number_format($ride['estimated_fare'], 2); ?></div>
                        </div>
                        <?php if ($ride['actual_fare']): ?>
                            <div class="details-row">
                                <div class="details-label">Actual Fare</div>
                                <div class="details-value">Rs. <?php echo number_format($ride['actual_fare'], 2); ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="details-row">
                            <div class="details-label">Payment Status</div>
                            <div class="details-value"><?php echo $ride['payment_status'] ?? 'Pending'; ?></div>
                        </div>
                    </div>
                    
                    <div class="details-actions">
                        <?php if ($ride['status'] === 'In Progress'): ?>
                            <a href="tracking.php?id=<?php echo $ride['id']; ?>" class="details-btn primary-btn">Track Ride</a>
                        <?php elseif ($ride['status'] === 'Completed'): ?>
                            <a href="receipt.php?id=<?php echo $ride['id']; ?>" class="details-btn primary-btn">View Receipt</a>
                        <?php elseif ($ride['status'] === 'Pending' || $ride['status'] === 'Accepted'): ?>
                            <a href="cancel.php?id=<?php echo $ride['id']; ?>" class="details-btn secondary-btn">Cancel Ride</a>
                        <?php endif; ?>
                        <a href="history.php" class="details-btn secondary-btn">Back to History</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
