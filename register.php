<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Fake News Detective</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }
        
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }
        
        .btn-register {
            background: linear-gradient(45deg, #2563eb, #3b82f6);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
        }
        
        .btn-register:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.4);
        }
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background: #dc2626; }
        .strength-medium { background: #d97706; }
        .strength-strong { background: #16a34a; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card register-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-plus text-primary" style="font-size: 3rem;"></i>
                            <h2 class="mt-3 mb-1">Create Account</h2>
                            <p class="text-muted">Join our community of fact-checkers</p>
                        </div>
                        
                        <form id="registerForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user text-primary me-2"></i>
                                        First Name
                                    </label>
                                    <input type="text" class="form-control" name="first_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user text-primary me-2"></i>
                                        Last Name
                                    </label>
                                    <input type="text" class="form-control" name="last_name" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-envelope text-primary me-2"></i>
                                    Email Address
                                </label>
                                <input type="email" class="form-control" name="email" required>
                                <div class="form-text">We'll never share your email with anyone else.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-lock text-primary me-2"></i>
                                    Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="password" id="password" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password', 'passwordToggle1')">
                                        <i class="fas fa-eye" id="passwordToggle1"></i>
                                    </button>
                                </div>
                                <div class="password-strength" id="passwordStrength"></div>
                                <div class="form-text">
                                    Password must be at least 8 characters with uppercase, lowercase, and numbers.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-lock text-primary me-2"></i>
                                    Confirm Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="confirm_password" id="confirmPassword" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirmPassword', 'passwordToggle2')">
                                        <i class="fas fa-eye" id="passwordToggle2"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" class="text-primary">Terms of Service</a> and 
                                    <a href="#" class="text-primary">Privacy Policy</a>
                                </label>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="newsletter">
                                <label class="form-check-label" for="newsletter">
                                    Subscribe to our newsletter for updates on fake news detection
                                </label>
                            </div>
                            
                            <input type="hidden" name="csrf_token" value="demo_token">
                            
                            <button type="submit" class="btn btn-register text-white w-100 mb-3">
                                <i class="fas fa-user-plus me-2"></i>
                                Create Account
                            </button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-0">Already have an account?</p>
                            <a href="login.php" class="btn btn-outline-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Sign In
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="index.php" class="text-white text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId, toggleId) {
            const password = document.getElementById(inputId);
            const toggle = document.getElementById(toggleId);
            
            if (password.type === 'password') {
                password.type = 'text';
                toggle.className = 'fas fa-eye-slash';
            } else {
                password.type = 'password';
                toggle.className = 'fas fa-eye';
            }
        }
        
        function checkPasswordStrength(password) {
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            const strengthBar = document.getElementById('passwordStrength');
            
            if (strength < 3) {
                strengthBar.className = 'password-strength strength-weak';
                strengthBar.style.width = '33%';
            } else if (strength < 4) {
                strengthBar.className = 'password-strength strength-medium';
                strengthBar.style.width = '66%';
            } else {
                strengthBar.className = 'password-strength strength-strong';
                strengthBar.style.width = '100%';
            }
        }
        
        document.getElementById('password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });
        
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = this.password.value;
            const confirmPassword = this.confirm_password.value;
            const terms = this.terms.checked;
            
            if (!terms) {
                alert('Please accept the Terms of Service and Privacy Policy');
                return;
            }
            
            if (password !== confirmPassword) {
                alert('Passwords do not match');
                return;
            }
            
            if (password.length < 8) {
                alert('Password must be at least 8 characters long');
                return;
            }
            
            // Demo registration
            alert('Registration successful! Please check your email to verify your account.');
            window.location.href = 'login.php';
        });
    </script>
</body>
</html>