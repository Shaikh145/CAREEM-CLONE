<?php
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Careem Clone - Book a Ride</title>
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
            font-size: 28px;
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
        
        .auth-buttons a {
            display: inline-block;
            padding: 8px 20px;
            background-color: white;
            color: #49b649;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            margin-left: 10px;
            transition: all 0.3s ease;
        }
        
        .auth-buttons a:hover {
            background-color: #e6e6e6;
            transform: translateY(-2px);
        }
        
        .hero {
            height: 500px;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }
        
        .hero-content {
            max-width: 700px;
        }
        
        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            animation: fadeInDown 1s ease;
        }
        
        .hero p {
            font-size: 18px;
            margin-bottom: 30px;
            animation: fadeInUp 1s ease;
        }
        
        .cta-button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #49b649;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            font-size: 18px;
            transition: all 0.3s ease;
            animation: fadeIn 1.5s ease;
        }
        
        .cta-button:hover {
            background-color: #3a9c3a;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .booking-section {
            padding: 60px 0;
            background-color: white;
        }
        
        .booking-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            max-width: 1000px;
            margin: 0 auto;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .booking-form {
            flex: 1;
            min-width: 300px;
            padding: 30px;
            background-color: white;
        }
        
        .booking-map {
            flex: 1;
            min-width: 300px;
            min-height: 400px;
            background-color: #f0f0f0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 40px;
            color: #333;
        }
        
        .section-title h2 {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .section-title p {
            color: #666;
            font-size: 18px;
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
        
        .ride-options {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .ride-option {
            flex: 1;
            text-align: center;
            padding: 15px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 5px;
        }
        
        .ride-option:hover, .ride-option.active {
            border-color: #49b649;
            background-color: rgba(73, 182, 73, 0.1);
        }
        
        .ride-option i {
            font-size: 24px;
            margin-bottom: 10px;
            color: #49b649;
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
        
        .fare-estimate {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 4px;
            text-align: center;
            display: none;
        }
        
        .fare-estimate.show {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        .features-section {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        
        .features-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        
        .feature-box {
            flex-basis: calc(33.33% - 30px);
            min-width: 250px;
            margin-bottom: 40px;
            padding: 30px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .feature-box:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            background-color: rgba(73, 182, 73, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: #49b649;
        }
        
        .feature-box h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .feature-box p {
            color: #666;
            line-height: 1.6;
        }
        
        .testimonials-section {
            padding: 80px 0;
            background-color: white;
        }
        
        .testimonials-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .testimonial-slider {
            display: flex;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 20px;
        }
        
        .testimonial-slide {
            flex: 0 0 100%;
            scroll-snap-align: start;
            padding: 30px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-right: 20px;
        }
        
        .testimonial-content {
            font-style: italic;
            margin-bottom: 20px;
            color: #555;
            line-height: 1.6;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
        }
        
        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
        }
        
        .author-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .author-info h4 {
            margin-bottom: 5px;
            color: #333;
        }
        
        .author-info p {
            color: #666;
        }
        
        .slider-dots {
            display: flex;
            justify-content: center;
        }
        
        .dot {
            width: 10px;
            height: 10px;
            background-color: #ddd;
            border-radius: 50%;
            margin: 0 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .dot.active {
            background-color: #49b649;
        }
        
        footer {
            background-color: #333;
            color: white;
            padding: 60px 0 20px;
        }
        
        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        
        .footer-column {
            flex-basis: calc(25% - 30px);
            min-width: 200px;
            margin-bottom: 30px;
        }
        
        .footer-column h3 {
            font-size: 18px;
            margin-bottom: 20px;
            color: #49b649;
        }
        
        .footer-column ul {
            list-style: none;
        }
        
        .footer-column ul li {
            margin-bottom: 10px;
        }
        
        .footer-column ul li a {
            color: #ddd;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .footer-column ul li a:hover {
            color: #49b649;
        }
        
        .social-links {
            display: flex;
            margin-top: 15px;
        }
        
        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background-color: #444;
            border-radius: 50%;
            margin-right: 10px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            background-color: #49b649;
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #444;
        }
        
        .footer-bottom p {
            color: #aaa;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            nav ul {
                margin: 15px 0;
            }
            
            .auth-buttons {
                margin-top: 15px;
            }
            
            .hero h1 {
                font-size: 36px;
            }
            
            .feature-box {
                flex-basis: calc(50% - 20px);
            }
        }
        
        @media (max-width: 576px) {
            .feature-box {
                flex-basis: 100%;
            }
            
            .booking-container {
                flex-direction: column;
            }
            
            .ride-options {
                flex-direction: column;
            }
            
            .ride-option {
                margin: 5px 0;
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
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </nav>
            <div class="auth-buttons">
                <a href="login.php">Login</a>
                <a href="signup.php">Sign Up</a>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
            <h1>Book Your Ride in Seconds</h1>
            <p>Safe, reliable, and affordable rides at your fingertips. Join thousands of happy customers who trust us for their daily commute.</p>
            <a href="#booking" class="cta-button">Book Now</a>
        </div>
    </section>

    <section id="booking" class="booking-section">
        <div class="container">
            <div class="section-title">
                <h2>Book Your Ride</h2>
                <p>Enter your pickup and drop-off locations to get started</p>
            </div>
            <div class="booking-container">
                <div class="booking-form">
                    <form id="ride-form">
                        <div class="form-group">
                            <label for="pickup">Pickup Location</label>
                            <input type="text" id="pickup" class="form-control" placeholder="Enter pickup location" required>
                        </div>
                        <div class="form-group">
                            <label for="dropoff">Drop-off Location</label>
                            <input type="text" id="dropoff" class="form-control" placeholder="Enter drop-off location" required>
                        </div>
                        <div class="form-group">
                            <label>Select Ride Type</label>
                            <div class="ride-options">
                                <div class="ride-option active" data-type="Economy" data-rate="15">
                                    <i>🚗</i>
                                    <h4>Economy</h4>
                                    <p>Rs. 15/km</p>
                                </div>
                                <div class="ride-option" data-type="Business" data-rate="25">
                                    <i>🚙</i>
                                    <h4>Business</h4>
                                    <p>Rs. 25/km</p>
                                </div>
                                <div class="ride-option" data-type="Premium" data-rate="35">
                                    <i>🚘</i>
                                    <h4>Premium</h4>
                                    <p>Rs. 35/km</p>
                                </div>
                            </div>
                        </div>
                        <button type="button" id="estimate-btn" class="submit-btn">Get Fare Estimate</button>
                        <div id="fare-estimate" class="fare-estimate">
                            <h3>Estimated Fare</h3>
                            <p id="estimated-fare">Rs. 0</p>
                            <p id="estimated-distance">0 km</p>
                            <button type="button" id="book-btn" class="submit-btn" style="margin-top: 15px;">Book Now</button>
                        </div>
                    </form>
                </div>
                <div class="booking-map" id="map">
                    <!-- Map will be loaded here -->
                </div>
            </div>
        </div>
    </section>

    <section id="services" class="features-section">
        <div class="container">
            <div class="section-title">
                <h2>Why Choose Us</h2>
                <p>Experience the best ride-hailing service in town</p>
            </div>
            <div class="features-container">
                <div class="feature-box">
                    <div class="feature-icon">🔒</div>
                    <h3>Safe Rides</h3>
                    <p>All our drivers are verified and trained to ensure your safety. Track your ride in real-time and share your trip details with loved ones.</p>
                </div>
                <div class="feature-box">
                    <div class="feature-icon">💰</div>
                    <h3>Affordable Prices</h3>
                    <p>Enjoy competitive rates with no hidden charges. Get fare estimates before booking and choose from different ride options.</p>
                </div>
                <div class="feature-box">
                    <div class="feature-icon">⏱️</div>
                    <h3>Quick Pickups</h3>
                    <p>Our large network of drivers ensures you get picked up within minutes of booking. No more waiting for rides.</p>
                </div>
                <div class="feature-box">
                    <div class="feature-icon">🌟</div>
                    <h3>Top-Rated Drivers</h3>
                    <p>Our drivers maintain high ratings and provide excellent service. Rate your experience after each ride.</p>
                </div>
                <div class="feature-box">
                    <div class="feature-icon">💳</div>
                    <h3>Multiple Payment Options</h3>
                    <p>Pay with cash, credit card, or through our in-app wallet. Choose the payment method that works best for you.</p>
                </div>
                <div class="feature-box">
                    <div class="feature-icon">📱</div>
                    <h3>Easy Booking</h3>
                    <p>Book a ride with just a few taps on your smartphone. Our user-friendly app makes booking rides quick and hassle-free.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="testimonials-section">
        <div class="container">
            <div class="section-title">
                <h2>What Our Customers Say</h2>
                <p>Hear from people who use our service every day</p>
            </div>
            <div class="testimonials-container">
                <div class="testimonial-slider">
                    <div class="testimonial-slide">
                        <div class="testimonial-content">
                            <p>"I use CareemClone every day for my commute to work. The drivers are always on time and professional. The fare estimates are accurate, and I love the tracking feature!"</p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <img src="/placeholder.svg?height=50&width=50" alt="Sarah J.">
                            </div>
                            <div class="author-info">
                                <h4>Sarah J.</h4>
                                <p>Regular User</p>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-slide">
                        <div class="testimonial-content">
                            <p>"As a business traveler, I appreciate the reliability and professionalism of CareemClone. The business class option is perfect for client meetings, and the receipt system makes expense reporting easy."</p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <img src="/placeholder.svg?height=50&width=50" alt="Ahmed K.">
                            </div>
                            <div class="author-info">
                                <h4>Ahmed K.</h4>
                                <p>Business Traveler</p>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-slide">
                        <div class="testimonial-content">
                            <p>"I feel safe using CareemClone, even late at night. The driver tracking and trip sharing features give me peace of mind, and the drivers are always respectful."</p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <img src="/placeholder.svg?height=50&width=50" alt="Fatima R.">
                            </div>
                            <div class="author-info">
                                <h4>Fatima R.</h4>
                                <p>Student</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="slider-dots">
                    <span class="dot active" data-slide="0"></span>
                    <span class="dot" data-slide="1"></span>
                    <span class="dot" data-slide="2"></span>
                </div>
            </div>
        </div>
    </section>

    <footer id="contact">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>CareemClone</h3>
                    <p>Your trusted ride-hailing service. Available 24/7 for all your transportation needs.</p>
                    <div class="social-links">
                        <a href="#"><span>FB</span></a>
                        <a href="#"><span>TW</span></a>
                        <a href="#"><span>IG</span></a>
                        <a href="#"><span>LI</span></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#contact">Contact</a></li>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="signup.php">Sign Up</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Services</h3>
                    <ul>
                        <li><a href="#">Economy Rides</a></li>
                        <li><a href="#">Business Rides</a></li>
                        <li><a href="#">Premium Rides</a></li>
                        <li><a href="#">Airport Transfers</a></li>
                        <li><a href="#">Intercity Travel</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <ul>
                        <li>Email: info@careemclone.com</li>
                        <li>Phone: +92 300 1234567</li>
                        <li>Address: 123 Main Street, Lahore, Pakistan</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 CareemClone. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Ride options selection
        const rideOptions = document.querySelectorAll('.ride-option');
        rideOptions.forEach(option => {
            option.addEventListener('click', function() {
                rideOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Fare estimation
        const estimateBtn = document.getElementById('estimate-btn');
        const fareEstimate = document.getElementById('fare-estimate');
        const estimatedFare = document.getElementById('estimated-fare');
        const estimatedDistance = document.getElementById('estimated-distance');
        const bookBtn = document.getElementById('book-btn');
        
        estimateBtn.addEventListener('click', function() {
            const pickup = document.getElementById('pickup').value;
            const dropoff = document.getElementById('dropoff').value;
            
            if (pickup && dropoff) {
                // Simulate distance calculation (in a real app, this would use Google Maps API)
                const distance = Math.floor(Math.random() * 20) + 5; // Random distance between 5-25 km
                
                // Get selected ride type and rate
                const selectedRide = document.querySelector('.ride-option.active');
                const ratePerKm = parseFloat(selectedRide.getAttribute('data-rate'));
                const rideType = selectedRide.getAttribute('data-type');
                
                // Calculate fare
                const fare = (distance * ratePerKm).toFixed(2);
                
                // Update UI
                estimatedFare.textContent = `Rs. ${fare}`;
                estimatedDistance.textContent = `${distance} km`;
                fareEstimate.classList.add('show');
                
                // Simulate map (in a real app, this would show a Google Map)
                const map = document.getElementById('map');
                map.innerHTML = `<div style="height: 100%; display: flex; align-items: center; justify-content: center; flex-direction: column; background-color: #f0f0f0;">
                    <p style="margin-bottom: 10px;">Route from ${pickup} to ${dropoff}</p>
                    <p style="margin-bottom: 10px;">Distance: ${distance} km</p>
                    <p>Estimated Time: ${Math.floor(distance * 2) + 5} minutes</p>
                </div>`;
            } else {
                alert('Please enter both pickup and drop-off locations');
            }
        });
        
        // Book ride
        bookBtn.addEventListener('click', function() {
            const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
            
            if (isLoggedIn) {
                window.location.href = 'booking.php';
            } else {
                alert('Please login to book a ride');
                window.location.href = 'login.php';
            }
        });
        
        // Testimonial slider
        const dots = document.querySelectorAll('.dot');
        const slider = document.querySelector('.testimonial-slider');
        
        dots.forEach(dot => {
            dot.addEventListener('click', function() {
                const slideIndex = this.getAttribute('data-slide');
                const slideWidth = document.querySelector('.testimonial-slide').offsetWidth + 20; // 20px for margin
                
                slider.scrollLeft = slideWidth * slideIndex;
                
                dots.forEach(d => d.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Auto slide testimonials
        let currentSlide = 0;
        const totalSlides = dots.length;
        
        setInterval(() => {
            currentSlide = (currentSlide + 1) % totalSlides;
            const slideWidth = document.querySelector('.testimonial-slide').offsetWidth + 20;
            
            slider.scrollLeft = slideWidth * currentSlide;
            
            dots.forEach(d => d.classList.remove('active'));
            dots[currentSlide].classList.add('active');
        }, 5000);
    </script>
</body>
</html>
