<?php
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirectJS('login.php');
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get user data for default preferences
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Default values
$default_ride_type = $user['default_ride_type'] ?? 'Economy';
$default_payment = $user['default_payment'] ?? 'Cash';

// Process booking form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $pickup_location = sanitize($_POST['pickup_location']);
    $dropoff_location = sanitize($_POST['dropoff_location']);
    $ride_type = sanitize($_POST['ride_type']);
    $payment_method = sanitize($_POST['payment_method']);
    
    // Validate inputs
    if (empty($pickup_location) || empty($dropoff_location)) {
        $error = 'Please enter both pickup and drop-off locations';
    } else {
        // Calculate distance (in a real app, this would use Google Maps API)
        // For demo purposes, we'll generate a random distance between 1 and 20 km
        $distance = rand(1, 20);
        
        // Calculate estimated fare based on ride type and distance
        $base_fare = 50; // Base fare in Rs.
        
        // Per km rate based on ride type
        $per_km_rate = 15; // Default for Economy
        if ($ride_type === 'Business') {
            $per_km_rate = 25;
        } elseif ($ride_type === 'Premium') {
            $per_km_rate = 35;
        }
        
        $distance_charge = $distance * $per_km_rate;
        $service_fee = ($base_fare + $distance_charge) * 0.1; // 10% service fee
        $estimated_fare = $base_fare + $distance_charge + $service_fee;
        
        // Check wallet balance if payment method is wallet
        if ($payment_method === 'Wallet') {
            if ($user['wallet_balance'] < $estimated_fare) {
                $error = 'Insufficient wallet balance. Please add funds or choose another payment method.';
            }
        }
        
        if (empty($error)) {
            // Insert ride into database
            $sql = "INSERT INTO rides (user_id, pickup_location, dropoff_location, ride_type, payment_method, distance, estimated_fare, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issssdd", $user_id, $pickup_location, $dropoff_location, $ride_type, $payment_method, $distance, $estimated_fare);
            
            if ($stmt->execute()) {
                $ride_id = $stmt->insert_id;
                $success = 'Ride booked successfully! Searching for drivers...';
                
                // Redirect to tracking page after 3 seconds
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'tracking.php?id={$ride_id}';
                    }, 3000);
                </script>";
            } else {
                $error = 'Error booking ride: ' . $conn->error;
            }
            
            $stmt->close();
        }
    }
}

// Get saved locations for the user
$sql = "SELECT DISTINCT pickup_location FROM rides WHERE user_id = ? 
        UNION 
        SELECT DISTINCT dropoff_location FROM rides WHERE user_id = ? 
        ORDER BY pickup_location 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$saved_locations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Ride - Careem Clone</title>
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
        
        .booking-content {
            padding: 40px 0;
        }
        
        .booking-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .booking-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .booking-header p {
            color: #666;
        }
        
        .booking-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        
        .booking-form {
            flex: 1;
            min-width: 300px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }
        
        .booking-map {
            flex: 1;
            min-width: 300px;
            height: 500px;
            background-color: #f0f0f0;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
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
        
        .location-input-container {
            position: relative;
        }
        
        .location-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background-color: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 4px 4px;
            z-index: 10;
            max-height: 200px;
            overflow-y: auto;
            display: none;
        }
        
        .location-suggestion {
            padding: 10px 15px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .location-suggestion:hover {
            background-color: #f8f9fa;
        }
        
        .ride-options {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .ride-option {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .ride-option:hover {
            border-color: #49b649;
            transform: translateY(-2px);
        }
        
        .ride-option.selected {
            border-color: #49b649;
            background-color: rgba(73, 182, 73, 0.1);
        }
        
        .ride-option-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .ride-option-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .ride-option-price {
            font-size: 14px;
            color: #666;
        }
        
        .payment-options {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .payment-option {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-option:hover {
            border-color: #49b649;
            transform: translateY(-2px);
        }
        
        .payment-option.selected {
            border-color: #49b649;
            background-color: rgba(73, 182, 73, 0.1);
        }
        
        .payment-option-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .payment-option-name {
            font-weight: 600;
        }
        
        .fare-estimate {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .fare-estimate-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .fare-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .fare-item:last-child {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-weight: 600;
        }
        
        .fare-label {
            color: #666;
        }
        
        .fare-value {
            color: #333;
        }
        
        .book-btn {
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
        
        .book-btn:hover {
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
        
        @media (max-width: 768px) {
            .booking-container {
                flex-direction: column;
            }
            
            .booking-map {
                height: 300px;
                order: -1;
            }
            
            .ride-options, .payment-options {
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

    <div class="booking-content">
        <div class="container">
            <div class="booking-header">
                <h1>Book a Ride</h1>
                <p>Enter your pickup and drop-off locations to get started</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="booking-container">
                <div class="booking-form">
                    <form method="POST" action="" id="bookingForm">
                        <div class="form-group">
                            <label for="pickup_location">Pickup Location</label>
                            <div class="location-input-container">
                                <input type="text" id="pickup_location" name="pickup_location" class="form-control" placeholder="Enter pickup location" required>
                                <div class="location-suggestions" id="pickupSuggestions">
                                    <?php foreach ($saved_locations as $location): ?>
                                        <div class="location-suggestion" onclick="selectLocation('pickup_location', '<?php echo $location['pickup_location']; ?>')"><?php echo $location['pickup_location']; ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="dropoff_location">Drop-off Location</label>
                            <div class="location-input-container">
                                <input type="text" id="dropoff_location" name="dropoff_location" class="form-control" placeholder="Enter drop-off location" required>
                                <div class="location-suggestions" id="dropoffSuggestions">
                                    <?php foreach ($saved_locations as $location): ?>
                                        <div class="location-suggestion" onclick="selectLocation('dropoff_location', '<?php echo $location['pickup_location']; ?>')"><?php echo $location['pickup_location']; ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Select Ride Type</label>
                            <div class="ride-options">
                                <div class="ride-option <?php echo $default_ride_type === 'Economy' ? 'selected' : ''; ?>" onclick="selectRideType('Economy')">
                                    <div class="ride-option-icon">🚗</div>
                                    <div class="ride-option-name">Economy</div>
                                    <div class="ride-option-price">Rs. 15/km</div>
                                </div>
                                <div class="ride-option <?php echo $default_ride_type === 'Business' ? 'selected' : ''; ?>" onclick="selectRideType('Business')">
                                    <div class="ride-option-icon">🚙</div>
                                    <div class="ride-option-name">Business</div>
                                    <div class="ride-option-price">Rs. 25/km</div>
                                </div>
                                <div class="ride-option <?php echo $default_ride_type === 'Premium' ? 'selected' : ''; ?>" onclick="selectRideType('Premium')">
                                    <div class="ride-option-icon">🚘</div>
                                    <div class="ride-option-name">Premium</div>
                                    <div class="ride-option-price">Rs. 35/km</div>
                                </div>
                            </div>
                            <input type="hidden" id="ride_type" name="ride_type" value="<?php echo $default_ride_type; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Payment Method</label>
                            <div class="payment-options">
                                <div class="payment-option <?php echo $default_payment === 'Cash' ? 'selected' : ''; ?>" onclick="selectPaymentMethod('Cash')">
                                    <div class="payment-option-icon">💵</div>
                                    <div class="payment-option-name">Cash</div>
                                </div>
                                <div class="payment-option <?php echo $default_payment === 'Card' ? 'selected' : ''; ?>" onclick="selectPaymentMethod('Card')">
                                    <div class="payment-option-icon">💳</div>
                                    <div class="payment-option-name">Card</div>
                                </div>
                                <div class="payment-option <?php echo $default_payment === 'Wallet' ? 'selected' : ''; ?>" onclick="selectPaymentMethod('Wallet')">
                                    <div class="payment-option-icon">👛</div>
                                    <div class="payment-option-name">Wallet</div>
                                    <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                        Balance: Rs. <?php echo number_format($user['wallet_balance'] ?? 0, 2); ?>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="payment_method" name="payment_method" value="<?php echo $default_payment; ?>">
                        </div>
                        
                        <div class="fare-estimate" id="fareEstimate" style="display: none;">
                            <div class="fare-estimate-title">Fare Estimate</div>
                            <div class="fare-item">
                                <div class="fare-label">Base Fare</div>
                                <div class="fare-value">Rs. 50.00</div>
                            </div>
                            <div class="fare-item">
                                <div class="fare-label">Distance Charge (<span id="estimatedDistance">0</span> km)</div>
                                <div class="fare-value">Rs. <span id="distanceCharge">0.00</span></div>
                            </div>
                            <div class="fare-item">
                                <div class="fare-label">Service Fee</div>
                                <div class="fare-value">Rs. <span id="serviceFee">0.00</span></div>
                            </div>
                            <div class="fare-item">
                                <div class="fare-label">Total Fare</div>
                                <div class="fare-value">Rs. <span id="totalFare">0.00</span></div>
                            </div>
                        </div>
                        
                        <button type="submit" class="book-btn">Book Now</button>
                    </form>
                </div>
                
                <div class="booking-map">
                    <!-- Map would be displayed here in a real app -->
                    <div style="height: 100%; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                        <p style="font-size: 18px; margin-bottom: 10px;">Interactive Map</p>
                        <p style="color: #666; text-align: center; padding: 0 20px;">
                            In a real application, this would display an interactive map using Google Maps or a similar service to select pickup and drop-off locations.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show location suggestions when input is focused
        document.getElementById('pickup_location').addEventListener('focus', function() {
            document.getElementById('pickupSuggestions').style.display = 'block';
        });
        
        document.getElementById('dropoff_location').addEventListener('focus', function() {
            document.getElementById('dropoffSuggestions').style.display = 'block';
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('#pickup_location') && !event.target.closest('#pickupSuggestions')) {
                document.getElementById('pickupSuggestions').style.display = 'none';
            }
            
            if (!event.target.closest('#dropoff_location') && !event.target.closest('#dropoffSuggestions')) {
                document.getElementById('dropoffSuggestions').style.display = 'none';
            }
        });
        
        // Select location from suggestions
        function selectLocation(inputId, location) {
            document.getElementById(inputId).value = location;
            document.getElementById(inputId === 'pickup_location' ? 'pickupSuggestions' : 'dropoffSuggestions').style.display = 'none';
            updateFareEstimate();
        }
        
        // Select ride type
        function selectRideType(type) {
            document.getElementById('ride_type').value = type;
            
            // Update UI
            const rideOptions = document.querySelectorAll('.ride-option');
            rideOptions.forEach(option => {
                option.classList.remove('selected');
                if (option.querySelector('.ride-option-name').textContent === type) {
                    option.classList.add('selected');
                }
            });
            
            updateFareEstimate();
        }
        
        // Select payment method
        function selectPaymentMethod(method) {
            document.getElementById('payment_method').value = method;
            
            // Update UI
            const paymentOptions = document.querySelectorAll('.payment-option');
            paymentOptions.forEach(option => {
                option.classList.remove('selected');
                if (option.querySelector('.payment-option-name').textContent === method) {
                    option.classList.add('selected');
                }
            });
        }
        
        // Update fare estimate
        function updateFareEstimate() {
            const pickup = document.getElementById('pickup_location').value;
            const dropoff = document.getElementById('dropoff_location').value;
            
            if (pickup && dropoff) {
                // In a real app, this would call a Google Maps API to get the distance
                // For demo purposes, we'll generate a random distance between 1 and 20 km
                const distance = Math.floor(Math.random() * 20) + 1;
                
                const rideType = document.getElementById('ride_type').value;
                let perKmRate = 15; // Default for Economy
                
                if (rideType === 'Business') {
                    perKmRate = 25;
                } else if (rideType === 'Premium') {
                    perKmRate = 35;
                }
                
                const baseFare = 50;
                const distanceCharge = distance * perKmRate;
                const serviceFee = (baseFare + distanceCharge) * 0.1; // 10% service fee
                const totalFare = baseFare + distanceCharge + serviceFee;
                
                // Update the fare estimate display
                document.getElementById('estimatedDistance').textContent = distance;
                document.getElementById('distanceCharge').textContent = distanceCharge.toFixed(2);
                document.getElementById('serviceFee').textContent = serviceFee.toFixed(2);
                document.getElementById('totalFare').textContent = totalFare.toFixed(2);
                
                // Show the fare estimate section
                document.getElementById('fareEstimate').style.display = 'block';
            }
        }
        
        // Listen for input changes to update fare estimate
        document.getElementById('pickup_location').addEventListener('input', updateFareEstimate);
        document.getElementById('dropoff_location').addEventListener('input', updateFareEstimate);
        
        // Form validation
        document.getElementById('bookingForm').addEventListener('submit', function(event) {
            const pickup = document.getElementById('pickup_location').value;
            const dropoff = document.getElementById('dropoff_location').value;
            
            if (!pickup || !dropoff) {
                event.preventDefault();
                alert('Please enter both pickup and drop-off locations');
            }
        });
    </script>
</body>
</html>
