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

// Check if ride belongs to user and can be cancelled
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

// Only allow cancellation of pending or accepted rides
if ($ride['status'] !== 'Pending' && $ride['status'] !== 'Accepted') {
    redirectJS('history.php');
}

$success = '';
$error = '';

// Process cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = isset($_POST['reason']) ? sanitize($_POST['reason']) : 'User cancelled';

    // Update ride status
    $sql = "UPDATE rides SET status = 'Cancelled', cancelled_at = NOW(), cancellation_reason = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $reason, $ride_id);

    if ($stmt->execute()) {
        $success = 'Ride cancelled successfully';
        
        // If a driver was assigned, update their status back to Available
        if ($ride['driver_id']) {
            $sql = "UPDATE drivers SET status = 'Available' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $ride['driver_id']);
            $stmt->execute();
        }
        
        // Redirect to dashboard after 2 seconds
        echo "<script>
            setTimeout(function() {
                window.location.href = 'dashboard.php';
            }, 2000);
        </script>";
    } else {
        $error = 'Error cancelling ride: ' . $conn->error;
    }

    $stmt->close();
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
    <title>Cancel Ride - Careem Clone</title>
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
        
        .cancel-content {
            padding: 40px 0;
        }
        
        .cancel-header {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .cancel-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .cancel-header p {
            color: #666;
        }
        
        .cancel-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }
        
        .ride-details {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .detail-label {
            color: #666;
        }
        
        .detail-value {
            font-weight: 500;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #49b649;
            outline: none;
            box-shadow: 0 0 0 3px rgba(73, 182, 73, 0.2);
        }
        
        .cancel-warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .action-buttons {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
        }
        
        .cancel-btn {
            background-color: #dc3545;
            color: white;
            border: none;
        }
        
        .cancel-btn:hover {
            background-color: #c82333;
        }
        
        .back-btn {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .back-btn:hover {
            background-color: #e9ecef;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .driver-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .driver-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: rgba(73, 182, 73, 0.1);
            color: #49b649;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
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
            font-size: 14px;
        }
        
        .driver-rating span {
            color: #666;
            margin-left: 5px;
        }
        
        @media (max-width: 768px) {
            .action-buttons {
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
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="cancel-content">
        <div class="container">
            <div class="cancel-header">
                <h1>Cancel Ride</h1>
                <p>Please provide a reason for cancellation</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="cancel-container">
                <div class="ride-details">
                    <h3 style="margin-bottom: 15px;">Ride Details</h3>
                    <div class="detail-row">
                        <div class="detail-label">Pickup</div>
                        <div class="detail-value"><?php echo $ride['pickup_location']; ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Drop-off</div>
                        <div class="detail-value"><?php echo $ride['dropoff_location']; ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Estimated Fare</div>
                        <div class="detail-value">Rs. <?php echo number_format($ride['estimated_fare'], 2); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Status</div>
                        <div class="detail-value"><?php echo $ride['status']; ?></div>
                    </div>
                </div>
                
                <?php if ($driver): ?>
                <div class="driver-info">
                    <div class="driver-avatar"><?php echo substr($driver['name'], 0, 1); ?></div>
                    <div class="driver-details">
                        <h4><?php echo $driver['name']; ?></h4>
                        <div class="driver-rating">
                            ★★★★★ <span><?php echo number_format($driver['rating'], 1); ?></span>
                        </div>
                        <div style="font-size: 14px; color: #666; margin-top: 5px;">
                            <?php echo $driver['vehicle_type']; ?> - <?php echo $driver['vehicle_number']; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="cancel-warning">
                    <strong>Note:</strong> Cancelling a ride may incur a cancellation fee if a driver has already been assigned.
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="reason">Reason for Cancellation</label>
                        <select id="reason" name="reason" class="form-control" required>
                            <option value="">Select a reason</option>
                            <option value="Wait time too long">Wait time too long</option>
                            <option value="Changed my mind">Changed my mind</option>
                            <option value="Booked by mistake">Booked by mistake</option>
                            <option value="Driver asked to cancel">Driver asked to cancel</option>
                            <option value="Found another ride">Found another ride</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="<?php echo $ride['status'] === 'Pending' ? 'dashboard.php' : 'tracking.php?id=' . $ride_id; ?>" class="btn back-btn">Go Back</a>
                        <button type="submit" class="btn cancel-btn">Confirm Cancellation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
