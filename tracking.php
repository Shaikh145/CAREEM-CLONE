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
$message = '';

// Get ride details
$sql = "SELECT * FROM rides WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $ride_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirectJS('dashboard.php');
}

$ride = $result->fetch_assoc();
$stmt->close();

// Manual accept button was clicked
if (isset($_POST['manual_accept']) && $ride['status'] === 'Pending') {
    // Get a random available driver
    $sql = "SELECT * FROM drivers WHERE status = 'Available' ORDER BY RAND() LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $driver = $result->fetch_assoc();
        $driver_id = $driver['id'];
        
        // Update ride with driver info
        $sql = "UPDATE rides SET driver_id = ?, status = 'Accepted', accepted_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $driver_id, $ride_id);
        
        if ($stmt->execute()) {
            // Update driver status
            $sql = "UPDATE drivers SET status = 'Busy' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $driver_id);
            $stmt->execute();
            $stmt->close();
            
            // Refresh ride data
            $sql = "SELECT * FROM rides WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $ride_id);
            $stmt->execute();
            $ride = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            $message = "Driver assigned successfully!";
        } else {
            $message = "Error assigning driver: " . $conn->error;
        }
    } else {
        $message = "No available drivers found. Please try again.";
    }
}

// For demo purposes: Update ride status to "In Progress" if it's been accepted for more than 10 seconds
if ($ride['status'] === 'Accepted' && !empty($ride['accepted_at'])) {
    $accepted_time = strtotime($ride['accepted_at']);
    $current_time = time();
    
    if (($current_time - $accepted_time) > 10) {
        $sql = "UPDATE rides SET status = 'In Progress', started_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $ride_id);
        $stmt->execute();
        $stmt->close();
        
        // Refresh ride data
        $sql = "SELECT * FROM rides WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $ride_id);
        $stmt->execute();
        $ride = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

// For demo purposes: Complete the ride if it's been in progress for more than 20 seconds
if ($ride['status'] === 'In Progress' && !empty($ride['started_at'])) {
    $started_time = strtotime($ride['started_at']);
    $current_time = time();
    
    if (($current_time - $started_time) > 20) {
        // Calculate actual fare (for demo, we'll use the estimated fare plus a small random amount)
        $actual_fare = $ride['estimated_fare'] + (rand(-50, 100) / 10);
        if ($actual_fare < 0) $actual_fare = $ride['estimated_fare'];
        
        $sql = "UPDATE rides SET status = 'Completed', completed_at = NOW(), actual_fare = ?, payment_status = 'Paid' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $actual_fare, $ride_id);
        $stmt->execute();
        $stmt->close();
        
        // Update driver status
        if ($ride['driver_id']) {
            $sql = "UPDATE drivers SET status = 'Available' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $ride['driver_id']);
            $stmt->execute();
            $stmt->close();
        }
        
        // Refresh ride data
        $sql = "SELECT * FROM rides WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $ride_id);
        $stmt->execute();
        $ride = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Redirect to receipt page
        redirectJS('receipt.php?id=' . $ride_id);
    }
}

// Get driver details if assigned
$driver = null;
if ($ride['driver_id']) {
    $sql = "SELECT * FROM drivers WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ride['driver_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $driver = $result->fetch_assoc();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Ride - Careem Clone</title>
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
        
        .tracking-content {
            padding: 40px 0;
        }
        
        .tracking-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .tracking-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .tracking-header p {
            color: #666;
        }
        
        .tracking-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        
        .tracking-map {
            flex: 2;
            min-width: 300px;
            height: 400px;
            background-color: #f0f0f0;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .tracking-info {
            flex: 1;
            min-width: 300px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .tracking-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }
        
        .tracking-status {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-accepted {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-in-progress {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-text {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .status-description {
            color: #666;
        }
        
        .ride-details {
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .detail-label {
            color: #666;
        }
        
        .detail-value {
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
        
        .driver-details h3 {
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
        
        .tracking-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .action-btn {
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
            border: none;
            cursor: pointer;
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
        
        .danger-btn {
            background-color: #dc3545;
            color: white;
            border: none;
        }
        
        .danger-btn:hover {
            background-color: #c82333;
        }
        
        .eta {
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .searching-animation {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100px;
        }
        
        .searching-dot {
            width: 15px;
            height: 15px;
            background-color: #49b649;
            border-radius: 50%;
            margin: 0 5px;
            animation: searching 1.4s infinite ease-in-out both;
        }
        
        .searching-dot:nth-child(1) {
            animation-delay: -0.32s;
        }
        
        .searching-dot:nth-child(2) {
            animation-delay: -0.16s;
        }
        
        @keyframes searching {
            0%, 80%, 100% {
                transform: scale(0);
            }
            40% {
                transform: scale(1);
            }
        }
        
        .progress-container {
            margin: 20px 0;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 30px;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #ddd;
            z-index: 1;
        }
        
        .progress-step {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 30px;
        }
        
        .step-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: white;
            border: 2px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
        }
        
        .step-circle.active {
            background-color: #49b649;
            border-color: #49b649;
            color: white;
        }
        
        .step-circle.completed {
            background-color: #49b649;
            border-color: #49b649;
            color: white;
        }
        
        .step-label {
            font-size: 12px;
            color: #666;
            text-align: center;
            width: 80px;
            margin-left: -25px;
        }
        
        .progress-bar {
            height: 5px;
            background-color: #ddd;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background-color: #49b649;
            width: 0%;
            transition: width 0.5s ease;
        }
        
        .manual-accept-form {
            margin-top: 20px;
            text-align: center;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
            background-color: #d4edda;
            color: #155724;
        }
        
        @media (max-width: 768px) {
            .tracking-container {
                flex-direction: column;
            }
            
            .tracking-map {
                height: 300px;
                order: -1;
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

    <div class="tracking-content">
        <div class="container">
            <div class="tracking-header">
                <h1>Track Your Ride</h1>
                <p>Real-time tracking of your ride</p>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="tracking-container">
                <div class="tracking-map">
                    <!-- Map would be displayed here in a real app -->
                    <div style="height: 100%; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                        <p style="font-size: 18px; margin-bottom: 10px;">Live Tracking Map</p>
                        <p style="color: #666; text-align: center; padding: 0 20px;">
                            In a real application, this would display a live map showing the driver's location and route to your pickup location.
                        </p>
                    </div>
                </div>
                
                <div class="tracking-info">
                    <div class="tracking-card">
                        <div class="tracking-status">
                            <?php
                            $statusClass = '';
                            $statusText = '';
                            $statusDescription = '';
                            $progressPercentage = 0;
                            
                            switch ($ride['status']) {
                                case 'Pending':
                                    $statusClass = 'status-pending';
                                    $statusText = 'Searching for Driver';
                                    $statusDescription = 'We are looking for a driver near your location';
                                    $progressPercentage = 25;
                                    break;
                                case 'Accepted':
                                    $statusClass = 'status-accepted';
                                    $statusText = 'Driver Assigned';
                                    $statusDescription = 'Your driver is on the way to your pickup location';
                                    $progressPercentage = 50;
                                    break;
                                case 'In Progress':
                                    $statusClass = 'status-in-progress';
                                    $statusText = 'Ride in Progress';
                                    $statusDescription = 'You are on your way to the destination';
                                    $progressPercentage = 75;
                                    break;
                                case 'Completed':
                                    $statusClass = 'status-completed';
                                    $statusText = 'Ride Completed';
                                    $statusDescription = 'You have reached your destination';
                                    $progressPercentage = 100;
                                    break;
                                case 'Cancelled':
                                    $statusClass = 'status-cancelled';
                                    $statusText = 'Ride Cancelled';
                                    $statusDescription = 'This ride has been cancelled';
                                    $progressPercentage = 0;
                                    break;
                            }
                            ?>
                            <div class="status-badge <?php echo $statusClass; ?>"><?php echo $ride['status']; ?></div>
                            <div class="status-text"><?php echo $statusText; ?></div>
                            <div class="status-description"><?php echo $statusDescription; ?></div>
                        </div>
                        
                        <?php if ($ride['status'] === 'Pending'): ?>
                            <div class="searching-animation">
                                <div class="searching-dot"></div>
                                <div class="searching-dot"></div>
                                <div class="searching-dot"></div>
                            </div>
                            
                            <!-- For demo purposes: Add a button to manually accept the ride -->
                            <form method="POST" action="" class="manual-accept-form">
                                <button type="submit" name="manual_accept" class="action-btn primary-btn">
                                    Simulate Driver Accept (Demo)
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="progress-container">
                                <div class="progress-steps">
                                    <div class="progress-step">
                                        <div class="step-circle <?php echo $progressPercentage >= 25 ? 'completed' : ''; ?>">1</div>
                                        <div class="step-label">Searching</div>
                                    </div>
                                    <div class="progress-step">
                                        <div class="step-circle <?php echo $progressPercentage >= 50 ? 'completed' : ''; ?>">2</div>
                                        <div class="step-label">Driver Assigned</div>
                                    </div>
                                    <div class="progress-step">
                                        <div class="step-circle <?php echo $progressPercentage >= 75 ? 'completed' : ''; ?>">3</div>
                                        <div class="step-label">In Progress</div>
                                    </div>
                                    <div class="progress-step">
                                        <div class="step-circle <?php echo $progressPercentage >= 100 ? 'completed' : ''; ?>">4</div>
                                        <div class="step-label">Completed</div>
                                    </div>
                                </div>
                                
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progressPercentage; ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="ride-details">
                            <div class="detail-row">
                                <div class="detail-label">Pickup Location</div>
                                <div class="detail-value"><?php echo $ride['pickup_location']; ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Drop-off Location</div>
                                <div class="detail-value"><?php echo $ride['dropoff_location']; ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Ride Type</div>
                                <div class="detail-value"><?php echo $ride['ride_type']; ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Estimated Fare</div>
                                <div class="detail-value">Rs. <?php echo number_format($ride['estimated_fare'], 2); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Payment Method</div>
                                <div class="detail-value"><?php echo $ride['payment_method']; ?></div>
                            </div>
                        </div>
                        
                        <?php if ($driver): ?>
                            <div class="driver-info">
                                <div class="driver-avatar"><?php echo substr($driver['name'], 0, 1); ?></div>
                                <div class="driver-details">
                                    <h3><?php echo $driver['name']; ?></h3>
                                    <div class="driver-rating">
                                        ★★★★★ <span><?php echo number_format($driver['rating'], 1); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="detail-row">
                                <div class="detail-label">Vehicle</div>
                                <div class="detail-value"><?php echo $driver['vehicle_type']; ?> - <?php echo $driver['vehicle_number']; ?></div>
                            </div>
                            
                            <div class="detail-row">
                                <div class="detail-label">Phone</div>
                                <div class="detail-value"><?php echo $driver['phone']; ?></div>
                            </div>
                            
                            <?php if ($ride['status'] === 'Accepted'): ?>
                                <div class="eta">
                                    ETA: <?php echo rand(3, 15); ?> minutes
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tracking-actions">
                        <?php if ($ride['status'] === 'Pending' || $ride['status'] === 'Accepted'): ?>
                            <a href="cancel  === 'Pending' || $ride['status'] === 'Accepted'): ?>
                            <a href="cancel.php?id=<?php echo $ride['id']; ?>" class="action-btn danger-btn">Cancel Ride</a>
                        <?php endif; ?>
                        <a href="details.php?id=<?php echo $ride['id']; ?>" class="action-btn secondary-btn">View Details</a>
                        <a href="dashboard.php" class="action-btn secondary-btn">Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh the page every 5 seconds to simulate real-time updates
        <?php if ($ride['status'] !== 'Completed' && $ride['status'] !== 'Cancelled'): ?>
        setTimeout(function() {
            window.location.reload();
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>
