<?php
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirectJS('login.php');
}

$user_id = $_SESSION['user_id'];

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
    <title>Ride History - Careem Clone</title>
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
        
        .history-content {
            padding: 40px 0;
        }
        
        .history-header {
            margin-bottom: 30px;
        }
        
        .history-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .history-header p {
            color: #666;
        }
        
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .filter-options {
            display: flex;
            gap: 15px;
        }
        
        .filter-option {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .filter-option:hover, .filter-option.active {
            border-color: #49b649;
            background-color: rgba(73, 182, 73, 0.1);
            color: #49b649;
        }
        
        .search-box {
            display: flex;
            align-items: center;
        }
        
        .search-input {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.3s ease;
            width: 200px;
        }
        
        .search-input:focus {
            border-color: #49b649;
            outline: none;
            box-shadow: 0 0 0 3px rgba(73, 182, 73, 0.2);
        }
        
        .search-btn {
            padding: 8px 15px;
            background-color: #49b649;
            color: white;
            border: none;
            border-radius: 4px;
            margin-left: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            background-color: #3a9c3a;
        }
        
        .history-table-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .history-table th,
        .history-table td {
            padding: 15px 20px;
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
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        
        .pagination-item {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ddd;
            margin: 0 5px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #333;
            text-decoration: none;
        }
        
        .pagination-item:hover, .pagination-item.active {
            border-color: #49b649;
            background-color: #49b649;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }
        
        .empty-icon {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-message {
            font-size: 18px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .book-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #49b649;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .book-btn:hover {
            background-color: #3a9c3a;
            transform: translateY(-2px);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .filter-options {
                width: 100%;
                overflow-x: auto;
                padding-bottom: 10px;
            }
            
            .search-box {
                width: 100%;
            }
            
            .search-input {
                flex: 1;
            }
            
            .history-table {
                display: block;
                overflow-x: auto;
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
                    <div class="user-avatar"><?php echo substr($_SESSION['user_name'], 0, 1); ?></div>
                    <span><?php echo $_SESSION['user_name']; ?></span>
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

    <div class="history-content">
        <div class="container">
            <div class="history-header">
                <h1>Ride History</h1>
                <p>View all your past rides and bookings</p>
            </div>
            
            <div class="filter-bar">
                <div class="filter-options">
                    <div class="filter-option active" data-filter="all">All</div>
                    <div class="filter-option" data-filter="completed">Completed</div>
                    <div class="filter-option" data-filter="cancelled">Cancelled</div>
                    <div class="filter-option" data-filter="in-progress">In Progress</div>
                </div>
                
                <div class="search-box">
                    <input type="text" class="search-input" placeholder="Search by location...">
                    <button class="search-btn">Search</button>
                </div>
            </div>
            
            <div class="history-table-container">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Driver</th>
                            <th>Fare</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rides->num_rows > 0): ?>
                            <?php while ($ride = $rides->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($ride['created_at'])); ?></td>
                                    <td><?php echo $ride['pickup_location']; ?></td>
                                    <td><?php echo $ride['dropoff_location']; ?></td>
                                    <td><?php echo $ride['driver_name'] ? $ride['driver_name'] : 'Not assigned'; ?></td>
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
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="empty-icon">📋</div>
                                        <div class="empty-message">No ride history found</div>
                                        <a href="booking.php" class="book-btn">Book Your First Ride</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($rides->num_rows > 10): ?>
                <div class="pagination">
                    <a href="#" class="pagination-item active">1</a>
                    <a href="#" class="pagination-item">2</a>
                    <a href="#" class="pagination-item">3</a>
                    <span class="pagination-item">...</span>
                    <a href="#" class="pagination-item">Next</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

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
        
        // Filter options
        const filterOptions = document.querySelectorAll('.filter-option');
        
        filterOptions.forEach(option => {
            option.addEventListener('click', function() {
                filterOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                // In a real app, this would filter the table rows based on the selected filter
                console.log('Filter selected:', filter);
            });
        });
        
        // Search functionality
        const searchBtn = document.querySelector('.search-btn');
        const searchInput = document.querySelector('.search-input');
        
        searchBtn.addEventListener('click', function() {
            const searchTerm = searchInput.value.trim();
            if (searchTerm) {
                // In a real app, this would search the table rows based on the search term
                console.log('Search term:', searchTerm);
            }
        });
        
        // Allow search on Enter key press
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchBtn.click();
            }
        });
    </script>
</body>
</html>
