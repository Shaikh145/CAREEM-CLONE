<?php
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirectJS('login.php');
}

// Get user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get user's ride history
$sql = "SELECT r.*, d.name as driver_name, d.phone as driver_phone, d.vehicle_type 
        FROM rides r 
        LEFT JOIN drivers d ON r.driver_id = d.id 
        WHERE r.user_id = ? 
        ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rides = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Careem Clone</title>
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
        
        .user-menu {
            position: relative;
        }
        
        .user-menu-btn {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: white;
            color: #49b649;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .user-menu-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 200px;
            padding: 10px 0;
            margin-top: 10px;
            display: none;
            z-index: 10;
        }
        
        .user-menu-dropdown.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        .user-menu-dropdown a {
            display: block;
            padding: 10px 20px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .user-menu-dropdown a:hover {
            background-color: #f8f9fa;
            color: #49b649;
        }
        
        .user-menu-dropdown hr {
            border: none;
            border-top: 1px solid #eee;
            margin: 5px 0;
        }
        
        .dashboard-content {
            padding: 40px 0;
        }
        
        .dashboard-header {
            margin-bottom: 30px;
        }
        
        .dashboard-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .dashboard-header p {
            color: #666;
        }
        
        .dashboard-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .dashboard-card {
            flex: 1;
            min-width: 250px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            background-color: rgba(73, 182, 73, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #49b649;
            margin-bottom: 15px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .card-value {
            font-size: 24px;
            font-weight: 700;
            color: #49b649;
            margin-bottom: 10px;
        }
        
        .card-description {
            color: #666;
            font-size: 14px;
        }
        
        .quick-actions {
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 20px;
            margin-bottom: 20px;
            color: #333;
            font-weight: 600;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
        }
        
        .action-button {
            flex: 1;
            min-width: 200px;
            padding: 15px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }
        
        .action-button:hover {
            border-color: #49b649;
            background-color: rgba(73, 182, 73, 0.05);
            transform: translateY(-3px);
        }
        
        .action-icon {
            width: 40px;
            height: 40px;
            background-color: rgba(73, 182, 73, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #49b649;
            margin-right: 15px;
        }
        
        .action-text h3 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .action-text p {
            font-size: 14px;
            color: #666;
        }
        
        .ride-history {
            margin-bottom: 40px;
        }
        
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .view-all {
            color: #49b649;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .view-all:hover {
            text-decoration: underline;
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .history-table th,
        .history-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .history-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .history-table tr:last-child td {
            border-bottom: none;
        }
        
        .history-table tr:hover td {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
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
        
        .action-link {
            color: #49b649;
            text-decoration: none;
            margin-right: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .action-link:hover {
            text-decoration: underline;
        }
        
        .map-section {
            margin-bottom: 40px;
        }
        
        .map-container {
            height: 300px;
            background-color: #f0f0f0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .profile-section {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 40px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: rgba(73, 182, 73, 0.1);
            color: #49b649;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: bold;
            margin-right: 20px;
        }
        
        .profile-info h2 {
            font-size: 24px;
            margin-bottom: 5px;
            color: #333;
        }
        
        .profile-info p {
            color: #666;
        }
        
        .profile-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .detail-item {
            margin-bottom: 15px;
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
        
        .edit-profile-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #49b649;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .edit-profile-btn:hover {
            background-color: #3a9c3a;
            transform: translateY(-2px);
        }
        
        footer {
            background-color: #333;
            color: white;
            padding: 20px 0;
            margin-top: 40px;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-logo {
            font-size: 20px;
            font-weight: bold;
        }
        
        .footer-logo span {
            color: #49b649;
        }
        
        .footer-links a {
            color: #ddd;
            text-decoration: none;
            margin-left: 15px;
            transition: all 0.3s ease;
        }
        
        .footer-links a:hover {
            color: #49b649;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .dashboard-cards,
            .action-buttons {
                flex-direction: column;
            }
            
            .history-table {
                display: block;
                overflow-x: auto;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-avatar {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
            
            .footer-links {
                margin-top: 15px;
            }
            
            .footer-links a {
                margin: 0 10px;
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
                    <li><a href="#">Support</a></li>
                </ul>
            </nav>
            <div class="user-menu">
                <div class="user-menu-btn" id="userMenuBtn">
                    <div class="user-avatar"><?php echo substr($user['name'], 0, 1); ?></div>
                    <span><?php echo $user['name']; ?></span>
                </div>
                <div class="user-menu-dropdown" id="userMenuDropdown">
                    <a href="profile.php">My Profile</a>
                    <a href="wallet.php">Wallet</a>
                    <a href="settings.php">Settings</a>
                    <hr>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="dashboard-content">
        <div class="container">
            <div class="dashboard-header">
                <h1>Welcome, <?php echo $user['name']; ?>!</h1>
                <p>Manage your rides and account information</p>
            </div>
            
            <div class="dashboard-cards">
                <div class="dashboard-card">
                    <div class="card-icon">🚗</div>
                    <div class="card-title">Total Rides</div>
                    <div class="card-value"><?php echo $rides->num_rows; ?></div>
                    <div class="card-description">Rides taken so far</div>
                </div>
                <div class="dashboard-card">
                    <div class="card-icon">💰</div>
                    <div class="card-title">Wallet Balance</div>
                    <div class="card-value">Rs. <?php echo number_format($user['wallet_balance'], 2); ?></div>
                    <div class="card-description">Available for rides</div>
                </div>
                <div class="dashboard-card">
                    <div class="card-icon">⭐</div>
                    <div class="card-title">Rating</div>
                    <div class="card-value">4.8</div>
                    <div class="card-description">Based on driver reviews</div>
                </div>
                <div class="dashboard-card">
                    <div class="card-icon">🎁</div>
                    <div class="card-title">Rewards</div>
                    <div class="card-value">250</div>
                    <div class="card-description">Points earned</div>
                </div>
            </div>
            
            <div class="quick-actions">
                <h2 class="section-title">Quick Actions</h2>
                <div class="action-buttons">
                    <a href="booking.php" class="action-button">
                        <div class="action-icon">🚗</div>
                        <div class="action-text">
                            <h3>Book a Ride</h3>
                            <p>Request a new ride</p>
                        </div>
                    </a>
                    <a href="wallet.php" class="action-button">
                        <div class="action-icon">💰</div>
                        <div class="action-text">
                            <h3>Add Money</h3>
                            <p>Top up your wallet</p>
                        </div>
                    </a>
                    <a href="history.php" class="action-button">
                        <div class="action-icon">📋</div>
                        <div class="action-text">
                            <h3>Ride History</h3>
                            <p>View past rides</p>
                        </div>
                    </a>
                    <a href="support.php" class="action-button">
                        <div class="action-icon">🎧</div>
                        <div class="action-text">
                            <h3>Support</h3>
                            <p>Get help with issues</p>
                        </div>
                    </a>
                </div>
            </div>
            
            <div class="ride-history">
                <div class="history-header">
                    <h2 class="section-title">Recent Rides</h2>
                    <a href="history.php" class="view-all">View All</a>
                </div>
                
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Fare</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rides->num_rows > 0): ?>
                            <?php $count = 0; ?>
                            <?php while ($ride = $rides->fetch_assoc()): ?>
                                <?php if ($count < 5): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($ride['created_at'])); ?></td>
                                        <td><?php echo $ride['pickup_location']; ?></td>
                                        <td><?php echo $ride['dropoff_location']; ?></td>
                                        <td>Rs. <?php echo number_format($ride['actual_fare'] ? $ride['actual_fare'] : $ride['estimated_fare'], 2); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($ride['status']) {
                                                case 'Pending':
                                                    $status_class = 'status-pending';
                                                    break;
                                                case 'Accepted':
                                                    $status_class = 'status-accepted';
                                                    break;
                                                case 'In Progress':
                                                    $status_class = 'status-in-progress';
                                                    break;
                                                case 'Completed':
                                                    $status_class = 'status-completed';
                                                    break;
                                                case 'Cancelled':
                                                    $status_class = 'status-cancelled';
                                                    break;
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>"><?php echo $ride['status']; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($ride['status'] === 'In Progress'): ?>
                                                <a href="tracking.php?id=<?php echo $ride['id']; ?>" class="action-link">Track</a>
                                            <?php elseif ($ride['status'] === 'Completed'): ?>
                                                <a href="receipt.php?id=<?php echo $ride['id']; ?>" class="action-link">Receipt</a>
                                            <?php elseif ($ride['status'] === 'Pending'): ?>
                                                <a href="cancel.php?id=<?php echo $ride['id']; ?>" class="action-link">Cancel</a>
                                            <?php endif; ?>
                                            <a href="details.php?id=<?php echo $ride['id']; ?>" class="action-link">Details</a>
                                        </td>
                                    </tr>
                                    <?php $count++; ?>
                                <?php endif; ?>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px;">No ride history found. Book your first ride now!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="map-section">
                <h2 class="section-title">Nearby Drivers</h2>
                <div class="map-container" id="map">
                    <!-- Map will be loaded here -->
                    <div style="height: 100%; display: flex; align-items: center; justify-content: center; flex-direction: column; background-color: #f0f0f0;">
                        <p style="margin-bottom: 10px;">Map showing nearby drivers</p>
                        <p>5 drivers available in your area</p>
                    </div>
                </div>
            </div>
            
            <div class="profile-section">
                <div class="profile-header">
                    <div class="profile-avatar"><?php echo substr($user['name'], 0, 1); ?></div>
                    <div class="profile-info">
                        <h2><?php echo $user['name']; ?></h2>
                        <p>Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
                
                <div class="profile-details">
                    <div>
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?php echo $user['email']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value"><?php echo $user['phone']; ?></div>
                        </div>
                    </div>
                    <div>
                        <div class="detail-item">
                            <div class="detail-label">Wallet Balance</div>
                            <div class="detail-value">Rs. <?php echo number_format($user['wallet_balance'], 2); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Total Rides</div>
                            <div class="detail-value"><?php echo $rides->num_rows; ?></div>
                        </div>
                    </div>
                </div>
                
                <a href="profile.php" class="edit-profile-btn">Edit Profile</a>
            </div>
        </div>
    </div>

    <footer>
        <div class="container footer-content">
            <div class="footer-logo">Careem<span>Clone</span></div>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Help Center</a>
                <a href="#">Contact Us</a>
            </div>
        </div>
    </footer>

    <script>
        // User menu dropdown
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userMenuDropdown = document.getElementById('userMenuDropdown');
        
        userMenuBtn.addEventListener('click', function() {
            userMenuDropdown.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        window.addEventListener('click', function(event) {
            if (!userMenuBtn.contains(event.target) && !userMenuDropdown.contains(event.target)) {
                userMenuDropdown.classList.remove('show');
            }
        });
        
        // Simulate map loading
        function initMap() {
            // In a real app, this would use Google Maps API
            const mapContainer = document.getElementById('map');
            mapContainer.innerHTML = `
                <div style="height: 100%; display: flex; align-items: center; justify-content: center; flex-direction: column; background-color: #f0f0f0;">
                    <p style="margin-bottom: 10px;">Map showing nearby drivers</p>
                    <p>5 drivers available in your area</p>
                </div>
            `;
        }
        
        // Call map initialization
        initMap();
    </script>
</body>
</html>
