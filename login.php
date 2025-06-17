<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Fake News Detective</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-card {
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
        
        .btn-login {
            background: linear-gradient(45deg, #2563eb, #3b82f6);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
        }
        
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card login-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-shield-alt text-primary" style="font-size: 3rem;"></i>
                            <h2 class="mt-3 mb-1">Welcome Back</h2>
                            <p class="text-muted">Sign in to your account</p>
                        </div>
                        
                        <form id="loginForm">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-envelope text-primary me-2"></i>
                                    Email Address
                                </label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-lock text-primary me-2"></i>
                                    Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="password" id="password" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                                        <i class="fas fa-eye" id="passwordToggle"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>
                            
                            <input type="hidden" name="csrf_token" value="demo_token">
                            
                            <button type="submit" class="btn btn-login text-white w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Sign In
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <a href="#" class="text-decoration-none text-muted">Forgot your password?</a>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-0">Don't have an account?</p>
                            <a href="register.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus me-2"></i>
                                Create Account
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
        function togglePassword() {
            const password = document.getElementById('password');
            const toggle = document.getElementById('passwordToggle');
            
            if (password.type === 'password') {
                password.type = 'text';
                toggle.className = 'fas fa-eye-slash';
            } else {
                password.type = 'password';
                toggle.className = 'fas fa-eye';
            }
        }
        
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Demo credentials
            const email = this.email.value;
            const password = this.password.value;
            
            if (email === 'admin@demo.com' && password === 'admin123') {
                alert('Admin login successful! Redirecting to dashboard...');
                window.location.href = 'admin-dashboard.php';
            } else if (email === 'user@demo.com' && password === 'user123') {
                alert('User login successful! Redirecting to dashboard...');
                window.location.href = 'dashboard.php';
            } else {
                alert('Demo credentials:\nAdmin: admin@demo.com / admin123\nUser: user@demo.com / user123');
            }
        });
    </script>
</body>
</html>