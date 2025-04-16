<?php
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirectJS('login.php');
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get user's ride statistics
$sql = "SELECT 
            COUNT(*) as total_rides,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_rides,
            SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_rides,
            SUM(CASE WHEN status = 'Completed' THEN actual_fare ELSE 0 END) as total_spent
        FROM rides 
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Process profile update form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    
    // Validate input
    if (empty($name) || empty($email) || empty($phone)) {
        $error = 'Please fill in all required fields';
    } else {
        // Check if email already exists for another user
        $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already exists for another user';
        } else {
            // Update user profile
            $sql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
            
            if ($stmt->execute()) {
                $success = 'Profile updated successfully!';
                
                // Update session variables
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                
                // Refresh user data
                $sql = "SELECT * FROM users WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            } else {
                $error = 'Error updating profile: ' . $conn->error;
            }
        }
        
        $stmt->close();
    }
}

// Process password change form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all password fields';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long';
    } else {
        // Verify current password
        if (verifyPassword($current_password, $user['password'])) {
            // Update password
            $hashed_password = hashPassword($new_password);
            
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Error changing password: ' . $conn->error;
            }
            
            $stmt->close();
        } else {
            $error = 'Current password is incorrect';
        }
    }
}

// Process profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_picture'])) {
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['profile_pic']['name'];
        $file_tmp = $_FILES['profile_pic']['tmp_name'];
        $file_size = $_FILES['profile_pic']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Check file extension
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_ext, $allowed_extensions)) {
            $error = 'Only JPG, JPEG, PNG, and GIF files are allowed';
        } elseif ($file_size > 2097152) { // 2MB max
            $error = 'File size must be less than 2MB';
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/profile_pics/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $new_file_name = $user_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Update user profile picture in database
                $sql = "UPDATE users SET profile_pic = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $new_file_name, $user_id);
                
                if ($stmt->execute()) {
                    $success = 'Profile picture updated successfully!';
                    
                    // Refresh user data
                    $sql = "SELECT * FROM users WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $user = $stmt->get_result()->fetch_assoc();
                } else {
                    $error = 'Error updating profile picture: ' . $conn->error;
                }
                
                $stmt->close();
            } else {
                $error = 'Error uploading file';
            }
        }
    } else {
        $error = 'Please select a file to upload';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Careem Clone</title>
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
            overflow: hidden;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
        
        .profile-content {
            padding: 40px 0;
        }
        
        .profile-header {
            margin-bottom: 30px;
        }
        
        .profile-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .profile-header p {
            color: #666;
        }
        
        .profile-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        
        .profile-sidebar {
            flex: 1;
            min-width: 250px;
            max-width: 300px;
        }
        
        .profile-main {
            flex: 3;
            min-width: 300px;
        }
        
        .profile-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .profile-picture {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .profile-avatar-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: rgba(73, 182, 73, 0.1);
            color: #49b649;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            font-weight: bold;
            margin: 0 auto 15px;
            overflow: hidden;
        }
        
        .profile-avatar-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .upload-btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #49b649;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }
        
        .upload-btn:hover {
            background-color: #3a9c3a;
            transform: translateY(-2px);
        }
        
        .profile-stats {
            margin-top: 30px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .stat-item:last-child {
            border-bottom: none;
        }
        
        .stat-label {
            color: #666;
        }
        
        .stat-value {
            font-weight: 600;
            color: #333;
        }
        
        .profile-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .profile-tab {
            padding: 10px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
            font-weight: 500;
        }
        
        .profile-tab:hover {
            color: #49b649;
        }
        
        .profile-tab.active {
            color: #49b649;
            border-bottom-color: #49b649;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
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
        
        .submit-btn {
            padding: 12px 20px;
            background-color: #49b649;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            background-color: #3a9c3a;
            transform: translateY(-2px);
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
        
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 3px;
            background-color: #ddd;
            overflow: hidden;
        }
        
        .password-strength-meter {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }
        
        .weak {
            width: 33%;
            background-color: #dc3545;
        }
        
        .medium {
            width: 66%;
            background-color: #ffc107;
        }
        
        .strong {
            width: 100%;
            background-color: #28a745;
        }
        
        .upload-form {
            display: none;
            margin-top: 15px;
        }
        
        .upload-form.show {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        .file-input-container {
            position: relative;
            margin-bottom: 15px;
        }
        
        .file-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-label {
            display: block;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px dashed #ddd;
            border-radius: 4px;
            text-align: center;
            color: #666;
            transition: all 0.3s ease;
        }
        
        .file-input-label:hover {
            background-color: #e9ecef;
        }
        
        .file-name {
            margin-top: 5px;
            font-size: 14px;
            color: #666;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .profile-container {
                flex-direction: column;
            }
            
            .profile-sidebar {
                max-width: none;
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
                    <div class="user-avatar">
                        <?php if (!empty($user['profile_pic']) && file_exists('uploads/profile_pics/' . $user['profile_pic'])): ?>
                            <img src="uploads/profile_pics/<?php echo $user['profile_pic']; ?>" alt="<?php echo $user['name']; ?>">
                        <?php else: ?>
                            <?php echo substr($user['name'], 0, 1); ?>
                        <?php endif; ?>
                    </div>
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

    <div class="profile-content">
        <div class="container">
            <div class="profile-header">
                <h1>My Profile</h1>
                <p>Manage your personal information and account settings</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="profile-container">
                <div class="profile-sidebar">
                    <div class="profile-card">
                        <div class="profile-picture">
                            <div class="profile-avatar-large">
                                <?php if (!empty($user['profile_pic']) && file_exists('uploads/profile_pics/' . $user['profile_pic'])): ?>
                                    <img src="uploads/profile_pics/<?php echo $user['profile_pic']; ?>" alt="<?php echo $user['name']; ?>">
                                <?php else: ?>
                                    <?php echo substr($user['name'], 0, 1); ?>
                                <?php endif; ?>
                            </div>
                            <h3><?php echo $user['name']; ?></h3>
                            <p>Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                            <button class="upload-btn" id="showUploadForm">Change Picture</button>
                            
                            <div class="upload-form" id="uploadForm">
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <div class="file-input-container">
                                        <label class="file-input-label">
                                            Click to select a file or drag and drop
                                            <input type="file" name="profile_pic" class="file-input" id="profilePic" accept="image/*">
                                        </label>
                                        <div class="file-name" id="fileName">No file selected</div>
                                    </div>
                                    <button type="submit" name="upload_picture" class="upload-btn">Upload</button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="profile-stats">
                            <div class="stat-item">
                                <div class="stat-label">Total Rides</div>
                                <div class="stat-value"><?php echo $stats['total_rides']; ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Completed Rides</div>
                                <div class="stat-value"><?php echo $stats['completed_rides']; ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Cancelled Rides</div>
                                <div class="stat-value"><?php echo $stats['cancelled_rides']; ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Total Spent</div>
                                <div class="stat-value">Rs. <?php echo number_format($stats['total_spent'], 2); ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Wallet Balance</div>
                                <div class="stat-value">Rs. <?php echo number_format($user['wallet_balance'], 2); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="profile-main">
                    <div class="profile-card">
                        <div class="profile-tabs">
                            <div class="profile-tab active" data-tab="personal-info">Personal Information</div>
                            <div class="profile-tab" data-tab="change-password">Change Password</div>
                            <div class="profile-tab" data-tab="preferences">Preferences</div>
                        </div>
                        
                        <div class="tab-content active" id="personal-info">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="name">Full Name</label>
                                    <input type="text" id="name" name="name" class="form-control" value="<?php echo $user['name']; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" id="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo $user['phone']; ?>" required>
                                </div>
                                
                                <button type="submit" name="update_profile" class="submit-btn">Update Profile</button>
                            </form>
                        </div>
                        
                        <div class="tab-content" id="change-password">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                                    <div class="password-strength">
                                        <div class="password-strength-meter" id="password-meter"></div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                </div>
                                
                                <button type="submit" name="change_password" class="submit-btn">Change Password</button>
                            </form>
                        </div>
                        
                        <div class="tab-content" id="preferences">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="default_payment">Default Payment Method</label>
                                    <select id="default_payment" name="default_payment" class="form-control">
                                        <option value="Cash" <?php echo ($user['default_payment'] ?? '') === 'Cash' ? 'selected' : ''; ?>>Cash</option>
                                        <option value="Card" <?php echo ($user['default_payment'] ?? '') === 'Card' ? 'selected' : ''; ?>>Card</option>
                                        <option value="Wallet" <?php echo ($user['default_payment'] ?? '') === 'Wallet' ? 'selected' : ''; ?>>Wallet</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="default_ride_type">Default Ride Type</label>
                                    <select id="default_ride_type" name="default_ride_type" class="form-control">
                                        <option value="Economy" <?php echo ($user['default_ride_type'] ?? '') === 'Economy' ? 'selected' : ''; ?>>Economy</option>
                                        <option value="Business" <?php echo ($user['default_ride_type'] ?? '') === 'Business' ? 'selected' : ''; ?>>Business</option>
                                        <option value="Premium" <?php echo ($user['default_ride_type'] ?? '') === 'Premium' ? 'selected' : ''; ?>>Premium</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Notification Preferences</label>
                                    <div style="margin-top: 10px;">
                                        <input type="checkbox" id="email_notifications" name="email_notifications" <?php echo ($user['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                        <label for="email_notifications" style="display: inline; font-weight: normal;">Email Notifications</label>
                                    </div>
                                    <div style="margin-top: 10px;">
                                        <input type="checkbox" id="sms_notifications" name="sms_notifications" <?php echo ($user['sms_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                        <label for="sms_notifications" style="display: inline; font-weight: normal;">SMS Notifications</label>
                                    </div>
                                    <div style="margin-top: 10px;">
                                        <input type="checkbox" id="push_notifications" name="push_notifications" <?php echo ($user['push_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                        <label for="push_notifications" style="display: inline; font-weight: normal;">Push Notifications</label>
                                    </div>
                                </div>
                                
                                <button type="submit" name="update_preferences" class="submit-btn">Save Preferences</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
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
        
        // Profile tabs
        const profileTabs = document.querySelectorAll('.profile-tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        profileTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Remove active class from all tabs and contents
                profileTabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to current tab and content
                this.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Password strength meter
        const passwordInput = document.getElementById('new_password');
        const passwordMeter = document.getElementById('password-meter');
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 6) {
                    strength += 1;
                }
                
                if (password.match(/[A-Z]/)) {
                    strength += 1;
                }
                
                if (password.match(/[0-9]/)) {
                    strength += 1;
                }
                
                if (password.match(/[^A-Za-z0-9]/)) {
                    strength += 1;
                }
                
                passwordMeter.className = 'password-strength-meter';
                
                if (strength === 0) {
                    passwordMeter.style.width = '0';
                } else if (strength <= 2) {
                    passwordMeter.classList.add('weak');
                } else if (strength === 3) {
                    passwordMeter.classList.add('medium');
                } else {
                    passwordMeter.classList.add('strong');
                }
            });
        }
        
        // Confirm password validation
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        if (confirmPasswordInput && passwordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                if (this.value !== passwordInput.value) {
                    this.setCustomValidity('Passwords do not match');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
        
        // Profile picture upload
        const showUploadFormBtn = document.getElementById('showUploadForm');
        const uploadForm = document.getElementById('uploadForm');
        const profilePicInput = document.getElementById('profilePic');
        const fileNameDisplay = document.getElementById('fileName');
        
        if (showUploadFormBtn) {
            showUploadFormBtn.addEventListener('click', function() {
                uploadForm.classList.toggle('show');
            });
        }
        
        if (profilePicInput) {
            profilePicInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    fileNameDisplay.textContent = this.files[0].name;
                } else {
                    fileNameDisplay.textContent = 'No file selected';
                }
            });
        }
    </script>
</body>
</html>
