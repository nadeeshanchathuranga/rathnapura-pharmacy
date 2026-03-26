<?php
define('BASE_PATH', __DIR__);
define('LOCK_FILE', BASE_PATH . '/storage/installed.lock');

$isInstalled = file_exists(LOCK_FILE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JPOS System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f9fafb;
            min-height: 100vh;
        }

        .main-container {
            display: flex;
            min-height: 100vh;
        }

        /* Left Column - Features Section */
        .left-column {
            display: none;
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 50%, #7c3aed 100%);
            padding: 3rem;
            flex-direction: column;
            justify-content: center;
        }

        @media (min-width: 1024px) {
            .left-column {
                display: flex;
                width: 50%;
            }
        }

        .left-content {
            max-width: 32rem;
            margin: 0 auto;
        }

        /* Logo/Brand */
        .brand {
            margin-bottom: 3rem;
        }

        .brand-inner {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .brand-icon {
            height: 3rem;
            width: 3rem;
            background: #4f46e5;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-icon svg {
            height: 1.75rem;
            width: 1.75rem;
            color: white;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .brand-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }

        /* Main Heading */
        .main-heading {
            font-size: 2.25rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .main-description {
            font-size: 1.125rem;
            color: #dbeafe;
            margin-bottom: 3rem;
            line-height: 1.6;
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 1rem;
        }

        @media (min-width: 768px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 0.75rem;
            padding: 1.25rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .feature-card:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .feature-icon-wrapper {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .feature-icon {
            height: 2.5rem;
            width: 2.5rem;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            background: rgba(255, 255, 255, 0.4);
        }

        .feature-icon svg {
            height: 1.25rem;
            width: 1.25rem;
            color: white;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .feature-title {
            font-size: 1rem;
            font-weight: 600;
            color: white;
            margin-bottom: 0.5rem;
        }

        .feature-description {
            font-size: 0.875rem;
            color: #dbeafe;
            line-height: 1.5;
        }

        /* Right Column - Main Content */
        .right-column {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        @media (min-width: 1024px) {
            .right-column {
                width: 50%;
                padding: 3rem;
            }
        }

        .right-content {
            width: 100%;
            max-width: 28rem;
        }

        /* Mobile Logo */
        .mobile-brand {
            display: block;
            margin-bottom: 2rem;
            text-align: center;
        }

        @media (min-width: 1024px) {
            .mobile-brand {
                display: none;
            }
        }

        .mobile-brand-inner {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .mobile-brand-icon {
            height: 2.5rem;
            width: 2.5rem;
            background: #4f46e5;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .mobile-brand-icon svg {
            height: 1.5rem;
            width: 1.5rem;
            color: white;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .mobile-brand-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
        }

        /* Main Card */
        .main-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            padding: 2rem 2.5rem;
        }

        /* Card Header */
        .card-header {
            margin-bottom: 2rem;
        }

        .card-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .card-subtitle {
            color: #6b7280;
        }

        /* Buttons */
        .btn {
            display: block;
            width: 100%;
            padding: 0.875rem 1rem;
            margin: 0.875rem 0;
            text-align: center;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: #4f46e5;
            color: white;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .btn-success {
            background: #10b981;
            color: white;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #1f2937;
            border: 1px solid #e5e7eb;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
            transform: translateY(-1px);
        }

        .btn-icon {
            margin-right: 0.5rem;
        }

        /* Additional Content */
        .additional-content {
            margin-top: 1.5rem;
        }

        .info-section {
            padding: 1.25rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 0.625rem;
            text-align: center;
        }

        .info-title {
            color: white;
            margin-bottom: 0.625rem;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .info-description {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 0.938rem;
            font-size: 0.875rem;
        }

        .btn-download {
            background: white;
            color: #667eea;
            font-weight: 600;
            display: inline-block;
            width: auto;
            padding: 0.875rem 1.875rem;
            margin: 0.625rem 0 0 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            border-radius: 0.625rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-download:hover {
            background: #f9fafb;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        /* Footer */
        .footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.875rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Left Column - Features Section -->
        <div class="left-column">
            <div class="left-content">
                <!-- Logo/Brand -->
                <div class="brand">
                    <div class="brand-inner">
                        <div class="brand-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <span class="brand-name">JPOS System</span>
                    </div>
                </div>

                <!-- Main Heading -->
                <h1 class="main-heading">Powerful Point of Sale Solution</h1>
                <p class="main-description">
                    Streamline your business operations with our comprehensive inventory and sales management system.
                </p>

                <!-- Features Grid -->
                <div class="features-grid">
                    <!-- Feature 1 -->
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <div class="feature-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="feature-title">Real-time Analytics</h3>
                        <p class="feature-description">
                            Track sales, inventory, and performance metrics in real-time.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <div class="feature-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="feature-title">Inventory Management</h3>
                        <p class="feature-description">
                            Efficiently manage stock levels and product transfers.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <div class="feature-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="feature-title">Secure & Reliable</h3>
                        <p class="feature-description">
                            Enterprise-grade security with role-based access control.
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <div class="feature-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="feature-title">Financial Reports</h3>
                        <p class="feature-description">
                            Generate detailed financial reports for expenses and sales.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Main Content -->
        <div class="right-column">
            <div class="right-content">
                <!-- Mobile Logo -->
                <div class="mobile-brand">
                    <div class="mobile-brand-inner">
                        <div class="mobile-brand-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <span class="mobile-brand-name">JPOS System</span>
                    </div>
                </div>

                <!-- Main Card -->
                <div class="main-card">
                    <!-- Header -->
                    <div class="card-header">
                        <h2 class="card-title">
                            <?php echo $isInstalled ? 'Welcome Back' : 'Get Started'; ?>
                        </h2>
                        <p class="card-subtitle">
                            <?php echo $isInstalled 
                                ? 'Access your JPOS application and manage your business.' 
                                : 'Set up your JPOS system and start managing your business.'; ?>
                        </p>
                    </div>

                    <!-- Buttons -->
                    <?php if ($isInstalled): ?>
                        <a href="public/" class="btn btn-success">
                            <span class="btn-icon">🚀</span>
                            Go to Application
                        </a>
                        <a href="guides/user-guide-pos-2/home.html" class="btn btn-secondary">
                            <span class="btn-icon">📚</span>
                            User Guide
                        </a>
                    <?php else: ?>
                        <a href="install.php" class="btn btn-primary">
                            <span class="btn-icon">⚙️</span>
                            Run Installer
                        </a>
                        <a href="guides/user-guide-pos-2" class="btn btn-secondary">
                            <span class="btn-icon">📖</span>
                            Installation Guide
                        </a>
                    <?php endif; ?>

                    <!-- Additional Content -->
                    <div class="additional-content">
                        <div class="info-section">
                            <h3 class="info-title">📱 Mobile App</h3>
                            <p class="info-description">
                                Download the JPOS mobile app to access reports on the go.
                            </p>
                            <a href="public/downloads/jpos-mobile.apk" class="btn-download" download>
                                📲 Download Android App (APK)
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <p class="footer">
                    © <?php echo date('Y'); ?> JPOS System. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
