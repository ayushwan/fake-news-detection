<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Fake News Detective</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #2563eb 0%, #1e40af 100%);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .sidebar-brand {
            padding: 1.5rem;
            color: white;
            text-decoration: none;
            display: block;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .sidebar-nav .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 24px;
            border: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-nav .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: none;
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .badge-status {
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            
            .sidebar.show {
                margin-left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <a href="#" class="sidebar-brand">
            <h4 class="mb-0">
                <i class="fas fa-shield-alt me-2"></i>
                Admin Panel
            </h4>
        </a>
        
        <nav class="sidebar-nav">
            <a href="#dashboard" class="nav-link active" data-section="dashboard">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="#users" class="nav-link" data-section="users">
                <i class="fas fa-users"></i>
                User Management
            </a>
            <a href="#submissions" class="nav-link" data-section="submissions">
                <i class="fas fa-newspaper"></i>
                Submissions
            </a>
            <a href="#analytics" class="nav-link" data-section="analytics">
                <i class="fas fa-chart-bar"></i>
                Analytics
            </a>
            <a href="#flags" class="nav-link" data-section="flags">
                <i class="fas fa-flag"></i>
                Flagged Content
            </a>
            <a href="#settings" class="nav-link" data-section="settings">
                <i class="fas fa-cog"></i>
                Settings
            </a>
            <hr class="border-light my-3">
            <a href="index.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Admin Dashboard</h1>
                <p class="text-muted">Monitor and manage the fake news detection system</p>
            </div>
            <div>
                <button class="btn btn-primary">
                    <i class="fas fa-download me-2"></i>
                    Export Report
                </button>
            </div>
        </div>
        
        <!-- Dashboard Section -->
        <div id="dashboard-section">
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="mb-1" id="totalUsers">1,247</h3>
                        <p class="text-muted mb-0">Total Users</p>
                        <small class="text-success">
                            <i class="fas fa-arrow-up"></i> +12% this month
                        </small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-success">
                            <i class="fas fa-newspaper"></i>
                        </div>
                        <h3 class="mb-1" id="totalSubmissions">8,932</h3>
                        <p class="text-muted mb-0">Submissions</p>
                        <small class="text-success">
                            <i class="fas fa-arrow-up"></i> +8% this week
                        </small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3 class="mb-1" id="fakeDetected">3,421</h3>
                        <p class="text-muted mb-0">Fake News Detected</p>
                        <small class="text-warning">
                            <i class="fas fa-arrow-up"></i> +5% this week
                        </small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-info">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <h3 class="mb-1" id="accuracy">94.2%</h3>
                        <p class="text-muted mb-0">Model Accuracy</p>
                        <small class="text-success">
                            <i class="fas fa-arrow-up"></i> +0.3% this month
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="chart-container">
                        <h5 class="mb-3">
                            <i class="fas fa-chart-line text-primary me-2"></i>
                            Submissions Trend
                        </h5>
                        <canvas id="submissionsChart" height="300"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="chart-container">
                        <h5 class="mb-3">
                            <i class="fas fa-chart-pie text-primary me-2"></i>
                            Content Distribution
                        </h5>
                        <canvas id="distributionChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="table-container">
                <h5 class="mb-3">
                    <i class="fas fa-clock text-primary me-2"></i>
                    Recent Activity
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Content Type</th>
                                <th>Result</th>
                                <th>Confidence</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody id="activityTable">
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center text-white me-2">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        john.doe@email.com
                                    </div>
                                </td>
                                <td>Text Analysis</td>
                                <td><span class="badge bg-info">Text</span></td>
                                <td><span class="badge badge-status bg-danger text-white">Fake</span></td>
                                <td>92%</td>
                                <td>2 minutes ago</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-success rounded-circle d-flex align-items-center justify-content-center text-white me-2">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        jane.smith@email.com
                                    </div>
                                </td>
                                <td>URL Analysis</td>
                                <td><span class="badge bg-warning">URL</span></td>
                                <td><span class="badge badge-status bg-success text-white">Real</span></td>
                                <td>87%</td>
                                <td>5 minutes ago</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-info rounded-circle d-flex align-items-center justify-content-center text-white me-2">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        admin@system.com
                                    </div>
                                </td>
                                <td>User Management</td>
                                <td><span class="badge bg-secondary">Admin</span></td>
                                <td><span class="badge badge-status bg-primary text-white">Suspended User</span></td>
                                <td>-</td>
                                <td>10 minutes ago</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navigation handling
        document.querySelectorAll('.nav-link[data-section]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Update active state
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                // Show section (for demo, just show dashboard)
                console.log('Switching to section:', this.dataset.section);
            });
        });
        
        // Initialize Charts
        function initializeCharts() {
            // Submissions Trend Chart
            const submissionsCtx = document.getElementById('submissionsChart').getContext('2d');
            new Chart(submissionsCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [
                        {
                            label: 'Total Submissions',
                            data: [420, 532, 748, 621, 834, 756, 892, 945, 1123, 987, 1245, 1156],
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Fake Detected',
                            data: [127, 189, 234, 198, 267, 234, 287, 301, 356, 312, 389, 365],
                            borderColor: '#dc2626',
                            backgroundColor: 'rgba(220, 38, 38, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Content Distribution Chart
            const distributionCtx = document.getElementById('distributionChart').getContext('2d');
            new Chart(distributionCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Real News', 'Fake News', 'Uncertain'],
                    datasets: [{
                        data: [5511, 3421, 1000],
                        backgroundColor: [
                            '#16a34a',
                            '#dc2626',
                            '#d97706'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        }
        
        // Update stats with animation
        function animateCounter(element, start, end, duration) {
            const startTime = performance.now();
            
            function updateCounter(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const current = Math.floor(progress * (end - start) + start);
                
                element.textContent = current.toLocaleString();
                
                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                }
            }
            
            requestAnimationFrame(updateCounter);
        }
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            
            // Animate counters
            setTimeout(() => {
                animateCounter(document.getElementById('totalUsers'), 0, 1247, 2000);
                animateCounter(document.getElementById('totalSubmissions'), 0, 8932, 2000);
                animateCounter(document.getElementById('fakeDetected'), 0, 3421, 2000);
            }, 500);
        });
        
        // Auto-refresh data every 30 seconds
        setInterval(() => {
            console.log('Refreshing dashboard data...');
            // In real implementation, fetch updated data from API
        }, 30000);
    </script>
</body>
</html>