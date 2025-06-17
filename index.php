<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fake News Detective - AI-Powered News Analysis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --danger-color: #dc2626;
            --success-color: #16a34a;
            --warning-color: #d97706;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .hero-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 3rem;
            margin: 2rem 0;
        }

        .analysis-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
            margin: 2rem 0;
        }

        .nav-tabs .nav-link {
            border: none;
            color: var(--primary-color);
            font-weight: 500;
            border-radius: 10px 10px 0 0;
        }

        .nav-tabs .nav-link.active {
            background: var(--primary-color);
            color: white;
        }

        .btn-analyze {
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-analyze:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.4);
            color: white;
        }

        .result-card {
            border-radius: 15px;
            border: none;
            margin: 1rem 0;
        }

        .result-fake {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border-left: 5px solid var(--danger-color);
        }

        .result-real {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            border-left: 5px solid var(--success-color);
        }

        .confidence-meter {
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            background: #e5e7eb;
        }

        .confidence-fill {
            height: 100%;
            transition: width 0.8s ease;
            border-radius: 10px;
        }

        .feature-icon {
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

        .upload-area {
            border: 2px dashed #cbd5e1;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.05);
        }

        .upload-area.dragover {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.1);
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .footer {
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 2rem 0;
            margin-top: 4rem;
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 2rem 1rem;
            }
            
            .analysis-card {
                margin: 1rem 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-shield-alt text-primary me-2"></i>
                Fake News Detective
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white px-3 ms-2" href="register.php">Get Started</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="container" style="margin-top: 100px;" id="home">
        <div class="hero-section text-center">
            <h1 class="display-4 fw-bold mb-4">
                <i class="fas fa-brain text-warning me-3"></i>
                AI-Powered Fake News Detection
            </h1>
            <p class="lead mb-4">
                Instantly analyze news articles, social media posts, and web content with our advanced machine learning technology. 
                Get confidence scores and detailed analysis to help you identify misinformation.
            </p>
            <div class="row justify-content-center">
                <div class="col-md-4 mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <span>98% Accuracy Rate</span>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-bolt text-warning me-2"></i>
                        <span>Real-time Analysis</span>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-shield-alt text-info me-2"></i>
                        <span>Secure & Private</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analysis Section -->
    <div class="container">
        <div class="card analysis-card">
            <div class="card-header bg-transparent border-0 pt-4">
                <h3 class="text-center mb-0">
                    <i class="fas fa-search text-primary me-2"></i>
                    Analyze News Content
                </h3>
                <p class="text-center text-muted mt-2">Choose your preferred input method below</p>
            </div>
            <div class="card-body p-4">
                <!-- Analysis Tabs -->
                <ul class="nav nav-tabs justify-content-center mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#text-tab">
                            <i class="fas fa-keyboard me-2"></i>Text Input
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#url-tab">
                            <i class="fas fa-link me-2"></i>URL Analysis
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#image-tab">
                            <i class="fas fa-image me-2"></i>Image OCR
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Text Input Tab -->
                    <div class="tab-pane fade show active" id="text-tab">
                        <form id="textAnalysisForm">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-edit text-primary me-2"></i>
                                    Enter News Text
                                </label>
                                <textarea 
                                    class="form-control" 
                                    name="content" 
                                    rows="6" 
                                    placeholder="Paste the news article or social media post you want to analyze..."
                                    required
                                ></textarea>
                                <div class="form-text">
                                    <i class="fas fa-info-circle text-info me-1"></i>
                                    Minimum 10 characters required for analysis
                                </div>
                            </div>
                            <input type="hidden" name="submission_type" value="text">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <div class="text-center">
                                <button type="submit" class="btn btn-analyze">
                                    <i class="fas fa-brain me-2"></i>
                                    Analyze Text
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- URL Input Tab -->
                    <div class="tab-pane fade" id="url-tab">
                        <form id="urlAnalysisForm">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-globe text-primary me-2"></i>
                                    Enter Article URL
                                </label>
                                <input 
                                    type="url" 
                                    class="form-control" 
                                    name="url" 
                                    placeholder="https://example.com/news-article"
                                    required
                                >
                                <div class="form-text">
                                    <i class="fas fa-info-circle text-info me-1"></i>
                                    We'll extract and analyze the content from the provided URL
                                </div>
                            </div>
                            <input type="hidden" name="submission_type" value="url">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <div class="text-center">
                                <button type="submit" class="btn btn-analyze">
                                    <i class="fas fa-download me-2"></i>
                                    Extract & Analyze
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Image Upload Tab -->
                    <div class="tab-pane fade" id="image-tab">
                        <form id="imageAnalysisForm" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-camera text-primary me-2"></i>
                                    Upload Image with Text
                                </label>
                                <div class="upload-area" id="uploadArea">
                                    <i class="fas fa-cloud-upload-alt text-primary mb-3" style="font-size: 3rem;"></i>
                                    <h5>Drop image here or click to upload</h5>
                                    <p class="text-muted mb-0">Supports JPG, PNG, GIF (Max 5MB)</p>
                                    <input type="file" name="image" id="imageInput" class="d-none" accept="image/*" required>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle text-info me-1"></i>
                                    We'll use OCR to extract text from the image and analyze it
                                </div>
                            </div>
                            <input type="hidden" name="submission_type" value="image">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <div class="text-center">
                                <button type="submit" class="btn btn-analyze">
                                    <i class="fas fa-eye me-2"></i>
                                    Extract & Analyze
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Results Section -->
                <div id="resultsSection" class="mt-4" style="display: none;">
                    <hr class="my-4">
                    <div id="analysisResults"></div>
                </div>

                <!-- Loading State -->
                <div id="loadingState" class="text-center mt-4" style="display: none;">
                    <div class="spinner-border text-primary me-3" role="status"></div>
                    <span class="fw-semibold">Analyzing content with AI...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="container" id="features">
        <h2 class="text-center text-white mb-5">
            <i class="fas fa-star text-warning me-2"></i>
            Why Choose Our Platform?
        </h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary mx-auto">
                            <i class="fas fa-robot"></i>
                        </div>
                        <h5>Advanced AI Technology</h5>
                        <p class="text-muted">
                            Powered by machine learning algorithms trained on millions of news articles
                            for superior accuracy in detecting misinformation.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-success mx-auto">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h5>Confidence Scoring</h5>
                        <p class="text-muted">
                            Get detailed confidence scores and explanations for each analysis,
                            helping you make informed decisions about content credibility.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-warning mx-auto">
                            <i class="fas fa-users"></i>
                        </div>
                        <h5>Community Verification</h5>
                        <p class="text-muted">
                            Join a community of fact-checkers and contribute to improving
                            the accuracy of news verification through collaborative feedback.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>
                        <i class="fas fa-shield-alt text-primary me-2"></i>
                        Fake News Detective
                    </h5>
                    <p class="text-light">
                        Empowering users with AI-powered tools to combat misinformation
                        and promote media literacy in the digital age.
                    </p>
                </div>
                <div class="col-md-6">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-light text-decoration-none">Privacy Policy</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Terms of Service</a></li>
                        <li><a href="#" class="text-light text-decoration-none">API Documentation</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Contact Support</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-light">
            <div class="text-center">
                <p class="mb-0">&copy; 2024 Fake News Detective. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image upload handling
        const uploadArea = document.getElementById('uploadArea');
        const imageInput = document.getElementById('imageInput');

        uploadArea.addEventListener('click', () => imageInput.click());

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                imageInput.files = files;
                updateUploadArea(files[0]);
            }
        });

        imageInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                updateUploadArea(e.target.files[0]);
            }
        });

        function updateUploadArea(file) {
            uploadArea.innerHTML = `
                <i class="fas fa-check-circle text-success mb-3" style="font-size: 3rem;"></i>
                <h5 class="text-success">File Selected</h5>
                <p class="text-muted mb-0">${file.name}</p>
                <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
            `;
        }

        // Form submission handling
        function setupFormSubmission(formId) {
            document.getElementById(formId).addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const loadingState = document.getElementById('loadingState');
                const resultsSection = document.getElementById('resultsSection');
                
                // Show loading
                loadingState.style.display = 'block';
                resultsSection.style.display = 'none';
                
                try {
                    const formData = new FormData(e.target);
                    
                    const response = await fetch('submit_news.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.error) {
                        throw new Error(result.error);
                    }
                    
                    // Display results
                    displayResults(result);
                    
                } catch (error) {
                    displayError(error.message);
                } finally {
                    loadingState.style.display = 'none';
                }
            });
        }

        function displayResults(result) {
            const resultsSection = document.getElementById('resultsSection');
            const analysisResults = document.getElementById('analysisResults');
            
            const isFake = result.prediction.toLowerCase() === 'fake';
            const confidence = Math.round(result.confidence * 100);
            
            analysisResults.innerHTML = `
                <div class="card result-card ${isFake ? 'result-fake' : 'result-real'}">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-2">
                                    <i class="fas ${isFake ? 'fa-exclamation-triangle text-danger' : 'fa-check-circle text-success'} me-2"></i>
                                    ${isFake ? 'Likely FAKE News' : 'Likely REAL News'}
                                </h4>
                                <p class="mb-3">
                                    Our AI analysis indicates this content is ${confidence}% likely to be ${result.prediction.toLowerCase()}.
                                </p>
                                <div class="mb-2">
                                    <small class="text-muted">Confidence Level</small>
                                    <div class="confidence-meter">
                                        <div class="confidence-fill bg-${isFake ? 'danger' : 'success'}" 
                                             style="width: ${confidence}%"></div>
                                    </div>
                                    <small class="text-muted">${confidence}% confidence</small>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="display-1 ${isFake ? 'text-danger' : 'text-success'}">
                                    ${confidence}%
                                </div>
                                <small class="text-muted">Confidence Score</small>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-top">
                            <div class="row text-center">
                                <div class="col-4">
                                    <small class="text-muted d-block">Processing Time</small>
                                    <strong>${result.processing_time}s</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Analysis ID</small>
                                    <strong>#${result.submission_id}</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Method</small>
                                    <strong>${result.submission_type || 'Text'}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            resultsSection.style.display = 'block';
            resultsSection.scrollIntoView({ behavior: 'smooth' });
        }

        function displayError(message) {
            const resultsSection = document.getElementById('resultsSection');
            const analysisResults = document.getElementById('analysisResults');
            
            analysisResults.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Analysis Failed:</strong> ${message}
                </div>
            `;
            
            resultsSection.style.display = 'block';
        }

        // Initialize form handlers
        setupFormSubmission('textAnalysisForm');
        setupFormSubmission('urlAnalysisForm');
        setupFormSubmission('imageAnalysisForm');

        // Demo mode notice (since user registration is required)
        function showDemoNotice() {
            if (!document.querySelector('.demo-notice')) {
                const notice = document.createElement('div');
                notice.className = 'alert alert-info demo-notice mt-3';
                notice.innerHTML = `
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Demo Mode:</strong> Please <a href="register.php" class="alert-link">register</a> 
                    or <a href="login.php" class="alert-link">login</a> to access the full analysis features.
                `;
                document.querySelector('.analysis-card .card-body').appendChild(notice);
            }
        }

        // Show demo notice after page load
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(showDemoNotice, 2000);
        });
    </script>
</body>
</html>