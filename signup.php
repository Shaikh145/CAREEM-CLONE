<?php
require_once 'db.php';

$error = '';
$success = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    redirectJS('dashboard.php');
}

// Process signup form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already exists';
        } else {
            // Hash password
            $hashed_password = hashPassword($password);
            
            // Insert new user
            $sql = "INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);
            
            if ($stmt->execute()) {
                $success = 'Account created successfully! You can now login.';
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 2000);
                </script>";
            } else {
                $error = 'Error creating account: ' . $conn->error;
            }
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Careem Clone</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 0;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
        }
        
        .signup-container {
            width: 100%;
            max-width: 500px;
            padding: 40px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease;
        }
        
        .signup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .signup-header h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .signup-header p {
            color: #666;
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
            width: 100%;
            padding: 12px;
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
        
        .signup-footer {
            text-align: center;
            margin-top: 20px;
        }
        
        .signup-footer a {
            color: #49b649;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .signup-footer a:hover {
            text-decoration: underline;
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
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo a {
            font-size: 28px;
            font-weight: bold;
            color: #49b649;
            text-decoration: none;
            letter-spacing: 1px;
        }
        
        .logo span {
            color: #333;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .terms-checkbox input {
            margin-right: 10px;
            margin-top: 5px;
        }
        
        .terms-checkbox label {
            font-size: 14px;
            color: #666;
        }
        
        .terms-checkbox label a {
            color: #49b649;
            text-decoration: none;
        }
        
        .terms-checkbox label a:hover {
            text-decoration: underline;
        }
        
        .or-divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        
        .or-divider::before,
        .or-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background-color: #ddd;
        }
        
        .or-divider span {
            padding: 0 10px;
            color: #666;
            font-size: 14px;
        }
        
        .social-signup {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin: 0 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .social-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }
        
        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
        }
        
        .back-home a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .back-home a:hover {
            color: #49b649;
        }
        
        .back-home a span {
            margin-left: 5px;
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
    </style>
</head>
<body>
    <div class="back-home">
        <a href="index.php">← <span>Back to Home</span></a>
    </div>
    
    <div class="signup-container">
        <div class="logo">
            <a href="index.php">Careem<span>Clone</span></a>
        </div>
        
        <div class="signup-header">
            <h1>Create an Account</h1>
            <p>Join us and enjoy convenient rides</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Enter your full name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="Enter your phone number" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Create a password" required>
                <div class="password-strength">
                    <div class="password-strength-meter" id="password-meter"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
            </div>
            
            <div class="terms-checkbox">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
            </div>
            
            <button type="submit" class="submit-btn">Sign Up</button>
            
            <div class="or-divider">
                <span>OR</span>
            </div>
            
            <div class="social-signup">
                <div class="social-btn">F</div>
                <div class="social-btn">G</div>
                <div class="social-btn">A</div>
            </div>
            
            <div class="signup-footer">
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
        </form>
    </div>
    
    <script>
        // Password strength meter
        const passwordInput = document.getElementById('password');
        const passwordMeter = document.getElementById('password-meter');
        
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
        
        // Confirm password validation
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
