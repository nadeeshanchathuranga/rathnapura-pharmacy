<?php
set_time_limit(0);
ini_set('memory_limit', '512M');

$ROOT = __DIR__;
$ENV_FILE = "$ROOT/.env";
$LOCK_FILE = "$ROOT/storage/install.lock";
$IS_WIN = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

if (file_exists($LOCK_FILE)) {
    die("<h2 style='color:#e74c3c;text-align:center;margin-top:100px;'>❌ Application already installed.</h2>");
}

/* ---------------- UTILITIES ---------------- */
function js($s)
{
    echo "<script>$s</script>";
    @ob_flush();
    @flush();
}
function logMsg($m)
{
    js("log(" . json_encode($m) . ")");
}
function progress($p)
{
    js("setProgress($p)");
}

function execLogged($cmd, $cwd = null)
{
    if ($cwd) {
        $cmd = "cd " . escapeshellarg($cwd) . " && $cmd";
    }
    exec($cmd . " 2>&1", $out, $code);
    foreach ($out as $line) {
        //logMsg($line);
    }
    return $code === 0;
}

function fixPermissions($root, $win)
{
    $paths = ["$root/storage", "$root/bootstrap/cache"];
    foreach ($paths as $p) {
        if (!is_dir($p)) return false;
        if ($win) {
            if (!is_writable($p)) return false;
        } else {
            exec("chown -R www-data:www-data " . escapeshellarg($p));
            exec("chmod -R 775 " . escapeshellarg($p));
        }
    }
    return true;
}

function fixEnvPermissions($root, $win)
{
    if ($win) {
        if (!is_writable("$root/storage")) return false;
    } else {
        exec("chown -R www-data:www-data " . escapeshellarg("$root/storage"));
        exec("chmod -R 775 " . escapeshellarg("$root/storage"));
    }
    return true;
}

function testDb($h, $d, $u, $p)
{
    try {
        new PDO(
            "mysql:host=$h;dbname=$d;charset=utf8mb4",
            $u,
            $p,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return true;
    } catch (Throwable $e) {
        logMsg("❌ DB Error: " . $e->getMessage());
        return false;
    }
}

function getCurrentURL()
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $url_without_install = str_replace('install.php', '', $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    $url_without_install = rtrim($url_without_install, '/');
    return $url_without_install;
}

/* ---------------- SYSTEM CHECK ---------------- */
function systemCheck(&$nodeAvailable)
{
    $r = [];
    $r['PHP'] = [
        'ok' => version_compare(PHP_VERSION, '8.2.0', '>='),
        'cur' => PHP_VERSION,
        'req' => '>= 8.2.0'
    ];

    foreach (['openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo', 'gd', 'zip'] as $e) {
        $r[$e] = [
            'ok' => extension_loaded($e),
            'cur' => extension_loaded($e) ? 'Enabled' : 'Missing',
            'req' => 'Enabled'
        ];
    }

    $nodeAvailable = false;
    $nodeCmds = [
        'node --version',
        '"C:\\Program Files\\nodejs\\node.exe" --version',
        '"C:\\Program Files (x86)\\nodejs\\node.exe" --version'
    ];
    foreach ($nodeCmds as $cmd) {
        exec($cmd, $no, $nc);
        if ($nc === 0) {
            $nodeAvailable = true;
            break;
        }
    }

    $r['Node.js'] = [
        'ok' => $nodeAvailable,
        'cur' => $nodeAvailable ? 'Detected' : 'Not detected',
        'req' => 'Optional'
    ];

    return $r;
}

/* ---------------- WIZARD ---------------- */
$STEP = $_POST['step'] ?? 1;
$nodeAvailable = false;
$sys = systemCheck($nodeAvailable);
$block = false;
foreach ($sys as $v) {
    if (!$v['ok'] && $v['req'] !== 'Optional') $block = true;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JPOS System - Installation Wizard</title>
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
            overflow: hidden;
        }

        .main-container {
            display: flex;
            min-height: 100vh;
            max-height: 100vh;
        }

        /* Left Column - Features Section */
        .left-column {
            display: none;
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 50%, #7c3aed 100%);
            padding: 3rem;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }

        @media (min-width: 1024px) {
            .left-column {
                display: flex;
                width: 50%;
                position: fixed;
                left: 0;
                top: 0;
                bottom: 0;
                height: 100vh;
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
            flex-direction: column;
            overflow-y: auto;
            max-height: 100vh;
        }

        @media (min-width: 1024px) {
            .right-column {
                width: 50%;
                margin-left: 50%;
            }
        }

        /* Mobile Logo */
        .mobile-brand {
            display: block;
            padding: 2rem 1.5rem;
            text-align: center;
            background: white;
            border-bottom: 1px solid #e5e7eb;
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

        /* Step Indicator */
        .step-indicator {
            display: flex;
            justify-content: center;
            padding: 2rem 1.5rem;
            background: white;
            border-bottom: 1px solid #e5e7eb;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0 1.5rem;
            position: relative;
        }

        .step-number {
            width: 2.5rem;
            height: 2.5rem;
            background: #e5e7eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 0.5rem;
            transition: all 0.3s;
            color: #6b7280;
        }

        .step.active .step-number {
            background: #4f46e5;
            color: white;
        }

        .step-label {
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            text-align: center;
        }

        .step.active .step-label {
            color: #4f46e5;
        }

        /* Content Area */
        .installer-content {
            flex: 1;
            padding: 2rem 1.5rem;
            background: #f9fafb;
        }

        @media (min-width: 768px) {
            .installer-content {
                padding: 3rem;
            }
        }

        .content-wrapper {
            max-width: 42rem;
            margin: 0 auto;
        }

        .main-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            padding: 2rem;
        }

        @media (min-width: 768px) {
            .main-card {
                padding: 2.5rem;
            }
        }

        .card-header {
            margin-bottom: 2rem;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .card-subtitle {
            color: #6b7280;
            line-height: 1.5;
        }

        .panel {
            display: none;
        }

        .panel.active {
            display: block;
        }

        .requirements-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
            font-size: 0.875rem;
        }

        .requirements-table th {
            background: #f9fafb;
            padding: 0.75rem;
            text-align: left;
            color: #374151;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }

        .requirements-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .requirements-table tr:last-child td {
            border-bottom: none;
        }

        .input-group {
            margin-bottom: 1.25rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
        }

        .input-group input[type="text"],
        .input-group input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .input-group input[type="text"]:focus,
        .input-group input[type="password"]:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 0.625rem;
            width: 1rem;
            height: 1rem;
            accent-color: #4f46e5;
        }

        .checkbox-group label {
            font-size: 0.875rem;
            color: #374151;
        }

        .secondary-db {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-top: 1.5rem;
            border-left: 4px solid #4f46e5;
            display: none;
        }

        .secondary-db.active {
            display: block;
        }

        .secondary-db h3 {
            font-size: 1.125rem;
            color: #111827;
            margin-bottom: 1.25rem;
            font-weight: 600;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            text-align: center;
        }

        .btn:hover {
            background: #4338ca;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #1f2937;
            border: 1px solid #e5e7eb;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .progress-container {
            margin: 2rem 0;
        }

        .progress-text {
            text-align: center;
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .progress-bar {
            height: 0.75rem;
            background: #e5e7eb;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(135deg, #4f46e5 0%, #10b981 100%);
            border-radius: 0.5rem;
            transition: width 0.5s ease;
        }

        .log-container {
            background: #111827;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-top: 2rem;
            height: 300px;
            overflow-y: auto;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.75rem;
            line-height: 1.5;
        }

        .log-line {
            color: #e5e7eb;
            margin-bottom: 0.25rem;
            word-break: break-all;
        }

        .log-line.success {
            color: #10b981;
        }

        .log-line.error {
            color: #ef4444;
        }

        .log-line.info {
            color: #3b82f6;
        }

        .log-line.warning {
            color: #f59e0b;
        }

        .error-message {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1.5rem 0;
            color: #991b1b;
        }

        .error-message strong {
            display: block;
            margin-bottom: 0.5rem;
        }

        .success-message {
            background: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1.5rem 0;
            color: #166534;
        }

        .success-message strong {
            display: block;
            margin-bottom: 0.5rem;
        }

        .hidden {
            display: none;
        }

        .btn-group {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            gap: 0.75rem;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                max-height: 0;
            }
            to {
                opacity: 1;
                max-height: 500px;
            }
        }
    </style>
    <script>
        function showPanel(panelId) {
            document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
            document.getElementById(panelId).classList.add('active');
        }

        function log(message) {
            const logContainer = document.getElementById('log');
            const line = document.createElement('div');
            line.className = 'log-line';

            if (message.includes('✅') || message.includes('▶')) {
                line.classList.add('info');
            } else if (message.includes('❌')) {
                line.classList.add('error');
            } else if (message.includes('✓')) {
                line.classList.add('success');
            } else if (message.includes('⚠')) {
                line.classList.add('warning');
            }

            line.textContent = message;
            logContainer.appendChild(line);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        function setProgress(percent) {
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');

            if (progressFill) {
                progressFill.style.width = percent + '%';
            }
            if (progressText) {
                progressText.textContent = percent + '% Complete';
            }
        }

        function toggleSecondaryDB() {
            const secondaryDB = document.getElementById('secondaryDB');
            const checkbox = document.querySelector('input[name="use_db2"]');

            if (checkbox.checked) {
                secondaryDB.classList.add('active');
            } else {
                secondaryDB.classList.remove('active');
            }
        }

        function validateForm() {
            const host = document.querySelector('input[name="db_host"]').value;
            const database = document.querySelector('input[name="db_database"]').value;
            const username = document.querySelector('input[name="db_username"]').value;

            if (!host || !database || !username) {
                alert('Please fill in all required database fields');
                return false;
            }

            const useSecondary = document.querySelector('input[name="use_db2"]').checked;
            if (useSecondary) {
                const host2 = document.querySelector('input[name="db2_host"]').value;
                const database2 = document.querySelector('input[name="db2_database"]').value;
                const username2 = document.querySelector('input[name="db2_username"]').value;

                if (!host2 || !database2 || !username2) {
                    alert('Please fill in all secondary database fields when enabled');
                    return false;
                }
            }

            return true;
        }
    </script>
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
                <h1 class="main-heading">Installation Wizard</h1>
                <p class="main-description">
                    Follow these steps to set up your Point of Sale system and start managing your business operations.
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

        <!-- Right Column - Installation Content -->
        <div class="right-column">
            <!-- Mobile Logo -->
            <div class="mobile-brand">
                <div class="mobile-brand-inner">
                    <div class="mobile-brand-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <span class="mobile-brand-name">JPOS Installation</span>
                </div>
            </div>

            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?= $STEP == 1 ? 'active' : '' ?>">
                    <div class="step-number">1</div>
                    <div class="step-label">System</div>
                </div>
                <div class="step <?= $STEP == 2 ? 'active' : '' ?>">
                    <div class="step-number">2</div>
                    <div class="step-label">Database</div>
                </div>
                <div class="step <?= $STEP == 3 ? 'active' : '' ?>">
                    <div class="step-number">3</div>
                    <div class="step-label">Install</div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="installer-content">
                <div class="content-wrapper">
            <?php if ($STEP == 1): ?>
                <div class="panel active" id="systemCheck">
                    <div class="main-card">
                        <div class="card-header">
                            <h2 class="card-title">System Requirements</h2>
                            <p class="card-subtitle">Verify that your server meets all requirements before proceeding.</p>
                        </div>

                    <table class="requirements-table">
                        <thead>
                            <tr>
                                <th>Requirement</th>
                                <th>Status</th>
                                <th>Current</th>
                                <th>Required</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sys as $k => $v): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($k) ?></strong></td>
                                    <td>
                                        <?php if ($v['ok']): ?>
                                            <span style="color:#10b981;">✅ Pass</span>
                                        <?php else: ?>
                                            <span style="color:<?= $v['req'] == 'Optional' ? '#f59e0b' : '#ef4444' ?>">
                                                <?= $v['req'] == 'Optional' ? '⚠ Optional' : '❌ Fail' ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($v['cur']) ?></td>
                                    <td><?= htmlspecialchars($v['req']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if ($block): ?>
                        <div class="error-message">
                            <strong>❌ Requirements Not Met</strong>
                            <p>Please fix the failed requirements before proceeding with the installation.</p>
                        </div>
                        <button class="btn btn-secondary" onclick="location.reload()">Re-check System</button>
                    <?php else: ?>
                        <div class="success-message">
                            <strong>✅ All Requirements Met</strong>
                            <p>Your system meets all the necessary requirements. You can proceed to the next step.</p>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="step" value="2">
                            <button type="submit" class="btn">Continue to Database Setup →</button>
                        </form>
                    <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($STEP == 2): ?>
                <div class="panel active" id="databaseSetup">
                    <div class="main-card">
                        <div class="card-header">
                            <h2 class="card-title">Database Configuration</h2>
                            <p class="card-subtitle">Configure your primary and optional secondary database connections.</p>
                        </div>

                    <form method="POST" onsubmit="return validateForm()">
                        <input type="hidden" name="step" value="3">

                        <div class="input-group">
                            <label for="db_host">Primary Database Host *</label>
                            <input type="text" id="db_host" name="db_host" required placeholder="localhost">
                        </div>

                        <div class="input-group">
                            <label for="db_database">Primary Database Name *</label>
                            <input type="text" id="db_database" name="db_database" required placeholder="jpos_primary">
                        </div>

                        <div class="input-group">
                            <label for="db_username">Primary Database Username *</label>
                            <input type="text" id="db_username" name="db_username" required placeholder="root">
                        </div>

                        <div class="input-group">
                            <label for="db_password">Primary Database Password</label>
                            <input type="password" id="db_password" name="db_password" placeholder="(optional)">
                        </div>

                        <div class="checkbox-group">
                            <input type="checkbox" id="use_db2" name="use_db2" onchange="toggleSecondaryDB()">
                            <label for="use_db2">Enable Secondary Database (for reports/analytics)</label>
                        </div>

                        <div class="secondary-db" id="secondaryDB">
                            <h3>Secondary Database (Optional)</h3>
                            <div class="input-group">
                                <label for="db2_host">Secondary Database Host</label>
                                <input type="text" id="db2_host" name="db2_host" placeholder="localhost">
                            </div>

                            <div class="input-group">
                                <label for="db2_database">Secondary Database Name</label>
                                <input type="text" id="db2_database" name="db2_database" placeholder="jpos_secondary">
                            </div>

                            <div class="input-group">
                                <label for="db2_username">Secondary Database Username</label>
                                <input type="text" id="db2_username" name="db2_username" placeholder="root">
                            </div>

                            <div class="input-group">
                                <label for="db2_password">Secondary Database Password</label>
                                <input type="password" id="db2_password" name="db2_password" placeholder="(optional)">
                            </div>
                        </div>

                        <div class="btn-group">
                            <button type="button" class="btn btn-secondary" onclick="history.back()">← Back</button>
                            <button type="submit" class="btn">Start Installation →</button>
                        </div>
                    </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($STEP == 3):
                if (!testDb($_POST['db_host'], $_POST['db_database'], $_POST['db_username'], $_POST['db_password'])) {
                    echo "<script>alert('❌ Primary database connection failed! Please check your credentials.');window.history.back();</script>";
                    exit;
                }

                if (isset($_POST['use_db2'])) {
                    if (!testDb($_POST['db2_host'], $_POST['db2_database'], $_POST['db2_username'], $_POST['db2_password'])) {
                        echo "<script>alert('❌ Secondary database connection failed! Please check your credentials.');window.history.back();</script>";
                        exit;
                    }
                }

                $db_host = $_POST['db_host'];
                $db_database = $_POST['db_database'];
                $db_username = $_POST['db_username'];
                $db_password = $_POST['db_password'];

                $db2_host = $_POST['db2_host'] ?? '';
                $db2_database = $_POST['db2_database'] ?? '';
                $db2_username = $_POST['db2_username'] ?? '';
                $db2_password = $_POST['db2_password'] ?? '';

                $appUrl = getCurrentURL();
            ?>
                <div class="panel active" id="installationProgress">
                    <div class="main-card">
                        <div class="card-header">
                            <h2 class="card-title">Installation in Progress</h2>
                            <p class="card-subtitle">Please wait while we set up your JPOS System. This may take a few minutes.</p>
                        </div>

                    <div class="progress-container">
                        <div class="progress-text" id="progressText">0% Complete</div>
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                    </div>

                    <div class="log-container" id="log"></div>

                    <div class="success-message hidden" id="completionMessage">
                        <strong>✅ Installation Complete!</strong>
                        <p>Your JPOS System has been successfully installed. You will be redirected to the login page shortly.</p>
                    </div>
                    </div>
                </div>


                <?php
                // Start installation process
                logMsg("▶ Starting JPOS System Installation...");
                progress(10);

                logMsg("▶ Generating environment configuration...");
                progress(30);

                $env = <<<ENV
APP_NAME="JPOS System"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=$appUrl

DB_CONNECTION=mysql
DB_HOST=$db_host
DB_PORT=3306
DB_DATABASE=$db_database
DB_USERNAME=$db_username
DB_PASSWORD=$db_password

DB_SECOND_CONNECTION=mysql
DB_HOST_SECOND=$db2_host
DB_PORT_SECOND=3306
DB_DATABASE_SECOND=$db2_database
DB_USERNAME_SECOND=$db2_username
DB_PASSWORD_SECOND=$db2_password

SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_DRIVER=database
QUEUE_CONNECTION=database

MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@jpos-system.com
MAIL_FROM_NAME="JPOS System"

VITE_APP_NAME="JPOS System"
ENV;

                file_put_contents($ENV_FILE, $env);
                logMsg("✅ Environment file created successfully");

                logMsg("▶ Generating application encryption key...");
                progress(40);
                execLogged("php artisan key:generate --force", $ROOT);
                logMsg("✅ Application encryption key generated successfully");

                logMsg("▶ Running database migrations...");
                progress(50);
                execLogged("php artisan migrate --force", $ROOT);
                logMsg("✅ Database migrations completed successfully");

                logMsg("▶ Seeding database with default data...");
                progress(55);
                execLogged("php artisan db:seed --force", $ROOT);
                logMsg("✅ Database seeded successfully");

                logMsg("▶ Creating storage symbolic link...");
                progress(65);
                execLogged("php artisan storage:link", $ROOT);
                logMsg("✅ Storage link created successfully");

                logMsg("▶ Setting up file permissions...");
                fixPermissions($ROOT, $IS_WIN);
                fixEnvPermissions($ROOT, $IS_WIN);
                logMsg("✅ Permissions configured successfully");

                logMsg("▶ Optimizing application performance...");
                execLogged("php artisan optimize:clear", $ROOT);
                progress(90);
                logMsg("✅ Application optimized successfully");

                file_put_contents($LOCK_FILE, 'installed at ' . date('Y-m-d H:i:s'));
                logMsg("✅ Installation lock file created");

                logMsg("🎉 Installation completed successfully!");
                progress(100);
                ?>
                <script>
                    // Show completion message and redirect
                    setTimeout(() => {
                        document.getElementById('completionMessage').classList.remove('hidden');
                        setTimeout(() => {
                            window.location.href = 'public/';
                        }, 3000);
                    }, 1000);
                </script>
                <?php
                if (file_exists(__FILE__)) {
                    if (unlink(__FILE__)) {
                        logMsg("✅ Installer script removed for security");
                    }
                }
                ?>
            <?php endif; ?>                </div>
            </div>        </div>
    </div>
</body>

</html>
