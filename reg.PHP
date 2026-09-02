<?php
$current_page = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Authentication | Urban Thread</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Google Sign-In API -->
    <meta name="google-signin-client_id" content="479471245753-hi8bg3e9opac0om3lr33puhlrainhgql.apps.googleusercontent.com">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Common Styles -->
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/navbar.css">
    <!-- Registration Page Specific Styles -->
    <link rel="stylesheet" href="css/reg.css">
    <style>
       
        
        /* Welcome Toast Styles */
        .welcome-toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 1000;
            animation: slideInUp 0.5s ease-out;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Divider styling */
        .divider {
            margin: 20px 0;
            position: relative;
            text-align: center;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background-color: var(--border-color);
        }
        
        .divider::before {
            left: 0;
        }
        
        .divider::after {
            right: 0;
        }
        
        .divider span {
            background-color: var(--card-bg);
            padding: 0 10px;
            color: var(--text-light);
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>
    <!-- Navbar (Same as your other pages) -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Premium Split Layout Authentication -->
    <div class="auth-split-container">
        <!-- Left Panel - Branding with Logo -->
        <!-- CHANGE: Left panel background color will be updated in reg.css to match footer -->
        <div class="auth-left-panel">
            <div class="auth-brand-content">
                <!-- Big Brand Logo -->
                <div class="brand-logo-large">
                    <img src="img/logo.png" alt="Urban Thread" class="main-logo">
                    <h1 class="brand-title">Urban Thread</h1>
                    <p class="brand-tagline">Fashion for Every Story</p>
                </div>
                
                <h2 class="welcome-headline">Welcome Back</h2>
                
                <p class="welcome-subtext">
                    Join our fashion community for exclusive offers, latest trends, and personalized shopping experiences.
                    Your style journey begins here.
                </p>
                
                <!-- Features List -->
                <div class="brand-features">
                    <div class="feature">
                        <i class="bi bi-truck"></i>
                        <div>
                            <h5>Free Shipping</h5>
                            <p>On orders over $50</p>
                        </div>
                    </div>
                    <div class="feature">
                        <i class="bi bi-shield-check"></i>
                        <div>
                            <h5>Secure Payment</h5>
                            <p>100% protected transactions</p>
                        </div>
                    </div>
                    <div class="feature">
                        <i class="bi bi-arrow-repeat"></i>
                        <div>
                            <h5>Easy Returns</h5>
                            <p>30-day return policy</p>
                        </div>
                    </div>
                    <div class="feature">
                        <i class="bi bi-star"></i>
                        <div>
                            <h5>Member Benefits</h5>
                            <p>Exclusive deals & rewards</p>
                        </div>
                    </div>
                </div>
                
                <!-- Decorative Background Elements -->
                <div class="theme-visuals">
                    <div class="visual-shape shape-1"></div>
                    <div class="visual-shape shape-2"></div>
                    <div class="visual-shape shape-3"></div>
                    <div class="visual-shape shape-4"></div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Authentication Card -->
        <div class="auth-right-panel">
            <div class="auth-card-wrapper">
                <div class="auth-mode-indicator">
                    <span class="mode-dot active" data-mode="login"></span>
                    <span class="mode-dot" data-mode="register"></span>
                </div>
                
                <div class="auth-card" id="authCard">
                    <!-- Login Form (Default) -->
                    <div class="auth-form active" id="loginForm">
                        <div class="auth-header">
                            <h2>
                                <i class="bi bi-person-circle"></i>
                                User Login
                            </h2>
                            <p class="auth-subtitle">Sign in to your account to continue</p>
                        </div>
                        
                        <form id="login-form" method="POST" action="/api/auth/login.php" autocomplete="off">
                            <!-- BACKEND_CLUE: Form should submit to /api/auth/login.php-->
                            <div class="form-group">
                                <label class="input-label" for="login-email">
                                    <i class="bi bi-envelope"></i> Email / Username
                                </label>
                                <div class="input-with-icon">
                                    <input type="text" class="form-input" id="login-email" name="email" placeholder="your.email@example.com" required>
                                    <i class="input-icon bi bi-person"></i>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="input-label" for="login-password">
                                    <i class="bi bi-lock"></i> Password
                                </label>
                                <div class="input-with-icon">
                                    <input type="password" class="form-input" id="login-password" name="password" placeholder="Enter your password" required>
                                    <i class="input-icon bi bi-key"></i>
                                    <button type="button" class="password-toggle" id="toggleLoginPassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- CHANGE: Removed the entire form-options div with "Remember me" and "Forgot password?" -->
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
                                <label class="form-check-label" for="rememberMe">Remember me</label>
                            </div>
                            
                            <button type="submit" class="auth-primary-btn" id="login-btn">
                                <span class="btn-text">Login</span>
                                <i class="bi bi-arrow-right"></i>
                            </button>
                            
                            <div class="auth-toggle">
                                <span class="toggle-text">Don't have an account?</span>
                                <button type="button" class="toggle-link" data-action="switch-to-register">
                                    Sign up <i class="bi bi-arrow-right"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Register Form (Hidden by default) -->
                    <div class="auth-form" id="registerForm">
                        <div class="auth-header">
                            <h2>
                                <i class="bi bi-person-plus"></i>
                                Create Account
                            </h2>
                            <p class="auth-subtitle">Join our community in just a few steps</p>
                        </div>
                        
                        <form id="register-form" method="POST" action="/api/auth/register.php" autocomplete="off">
                            <!-- BACKEND_CLUE: Form should submit to /api/auth/register.php -->
                            <div class="form-group">
                                <label class="input-label" for="reg-name">
                                    <i class="bi bi-person"></i> Full Name
                                </label>
                                <div class="input-with-icon">
                                    <input type="text" class="form-input" id="reg-name" name="name" placeholder="John Doe" required>
                                    <i class="input-icon bi bi-person-badge"></i>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="input-label" for="reg-email">
                                    <i class="bi bi-envelope"></i> Email Address
                                </label>
                                <div class="input-with-icon">
                                    <input type="email" class="form-input" id="reg-email" name="email" placeholder="your.email@example.com" required>
                                    <i class="input-icon bi bi-at"></i>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="input-label" for="reg-password">
                                    <i class="bi bi-lock"></i> Password
                                </label>
                                <div class="input-with-icon">
                                    <input type="password" class="form-input" id="reg-password" name="password" placeholder="Create a strong password" required>
                                    <i class="input-icon bi bi-shield-lock"></i>
                                    <button type="button" class="password-toggle" id="toggleRegPassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength">
                                    <div class="strength-bar" id="password-strength-bar"></div>
                                    <span class="strength-text" id="strength-text">Password strength</span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="input-label" for="reg-confirm-password">
                                    <i class="bi bi-lock-fill"></i> Confirm Password
                                </label>
                                <div class="input-with-icon">
                                    <input type="password" class="form-input" id="reg-confirm-password" name="confirm_password" placeholder="Confirm your password" required>
                                    <i class="input-icon bi bi-lock-fill"></i>
                                </div>
                            </div>
                            
                            <div class="form-check terms-check">
                                <input type="checkbox" class="form-check-input" id="reg-terms" name="terms" required>
                                <label class="form-check-label" for="reg-terms">
                                    I agree to the <a href="#" class="terms-link">Terms & Conditions</a> and <a href="#" class="terms-link">Privacy Policy</a>
                                </label>
                            </div>
                            
                            <button type="submit" class="auth-primary-btn" id="register-btn">
                                <span class="btn-text">Sign Up</span>
                                <i class="bi bi-check-lg"></i>
                            </button>
                            
                            <div class="auth-toggle">
                                <span class="toggle-text">Already have an account?</span>
                                <button type="button" class="toggle-link" data-action="switch-to-login">
                                    Login <i class="bi bi-arrow-left"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="auth-message" id="authMessage"></div>
                
                <div class="auth-footer">
                    <p class="security-note">
                        <i class="bi bi-shield-check"></i>
                        Your data is securely encrypted and protected
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer (Same as your other pages) -->
    <footer class="bg-dark text-white">
        <div class="container py-3">
            <div class="row align-items-center">
                <div class="col-md-2 mb-2 text-start">
                    <img src="img/logo1.png" alt="Urban Thread" class="logo-footer" id="footer-logo">
                </div>
                <div class="col-md-3 mb-2">
                    <h5 class="fw-bold">Urban Thread</h5>
                    <p>Fashion for every story. Join our community for exclusive offers and trends.</p>
                </div>
                <div class="col-md-3 mb-2">
                    <h5 class="fw-bold">Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="shop.php" class="text-white">Shop Collection</a></li>
                        <li><a href="about.php" class="text-white">About Us</a></li>
                        <li><a href="contact.php" class="text-white">Contact</a></li>
                        <li><a href="faq.php" class="text-white">FAQ</a></li>
                        <li><a href="returns.php" class="text-white">Returns</a></li>
                        <li><a href="admin.php" class="text-white" id="footer-admin-link" style="display:none;">Admin</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-2">
                    <h5 class="fw-bold">Stay Connected</h5>
                    <div class="d-flex gap-3 mt-2">
                        <a href="https://instagram.com" target="_blank" class="social-icon">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="https://tiktok.com" target="_blank" class="social-icon">
                            <i class="bi bi-tiktok"></i>
                        </a>
                        <a href="https://facebook.com" target="_blank" class="social-icon">
                            <i class="bi bi-facebook"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="row mt-3 pt-3 border-top border-secondary">
                <div class="col text-center">
                    <small>&copy; 2025 Urban Thread. All Rights Reserved.</small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Floating Back to Top Button -->
    <button id="btn-top" class="btn btn-dark btn-floating-top" title="Back to Top">
        <i class="bi bi-arrow-up"></i>
    </button>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/common.js"></script>
    <script src="js/reg.js"></script>
</body>
</html>