<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Laravel Installation')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 800px;
            width: 100%;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: #6c757d;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
        }

        .btn-danger {
            background: #dc3545;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
        }

        .message {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
            font-size: 16px;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message.warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .message.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .step {
            text-align: left;
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f8f9fa;
        }

        .step.active {
            background: #e3f2fd;
            border-color: #2196f3;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from {
                box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
            }
            to {
                box-shadow: 0 0 20px rgba(102, 126, 234, 0.8);
            }
        }

        .step h3 {
            margin: 0 0 15px 0;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }

        .requirement-check {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .requirement-check:hover {
            transform: translateX(5px);
        }

        .requirement-check.passed {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .requirement-check.failed {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .requirement-name {
            font-weight: bold;
        }

        .requirement-value {
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }

        .system-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            font-size: 14px;
            text-align: left;
        }

        .system-info h4 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(102, 126, 234, 0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            text-align: center;
            color: white;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        .loading-text {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .loading-subtitle {
            font-size: 14px;
            opacity: 0.8;
        }

        .progress-container {
            width: 300px;
            height: 6px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
            margin: 20px auto;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #fff, #f0f0f0, #fff);
            border-radius: 3px;
            animation: progress 2s ease-in-out infinite;
        }

        @keyframes progress {
            0% {
                width: 0%;
            }
            50% {
                width: 70%;
            }
            100% {
                width: 100%;
            }
        }

        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: left;
            font-size: 14px;
            border: 1px solid #ddd;
        }

        .info-box strong {
            display: block;
            margin-bottom: 10px;
            color: #333;
        }

        .info-box ul {
            margin: 10px 0 0 20px;
        }

        .info-box li {
            margin: 5px 0;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="container">
        <h1>🚀 @yield('heading', 'Laravel Application Setup')</h1>

        @if(session('success'))
            <div class="message success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="message error">
                {{ session('error') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="message warning">
                {{ session('warning') }}
            </div>
        @endif

        @if(session('info'))
            <div class="message info">
                {{ session('info') }}
            </div>
        @endif

        @yield('content')
    </div>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <div class="loading-text" id="loadingText">Processing...</div>
            <div class="loading-subtitle" id="loadingSubtitle">Please wait...</div>
            <div class="progress-container">
                <div class="progress-bar"></div>
            </div>
        </div>
    </div>

    <script>
        const loadingMessages = {
            'composer-install': {
                text: 'Installing Composer Dependencies',
                subtitle: 'Downloading and installing PHP packages...'
            },
            'npm-install-execute': {
                text: 'Installing NPM Dependencies',
                subtitle: 'Setting up Node.js packages...'
            },
            'npm-build-execute': {
                text: 'Building Assets',
                subtitle: 'Compiling CSS and JavaScript files...'
            },
            'create-env': {
                text: 'Creating Environment File',
                subtitle: 'Setting up configuration...'
            },
            'update-env': {
                text: 'Updating Database Configuration',
                subtitle: 'Configuring database connections...'
            },
            'create-database': {
                text: 'Creating Databases',
                subtitle: 'Setting up database structures...'
            },
            'migrate-execute': {
                text: 'Running Database Migrations',
                subtitle: 'Creating database tables...'
            },
            'seed-databases-execute': {
                text: 'Seeding Databases',
                subtitle: 'Populating databases with initial data...'
            },
            'generate-key-execute': {
                text: 'Generating Application Key',
                subtitle: 'Creating security keys...'
            },
            'storage-link-execute': {
                text: 'Creating Storage Links',
                subtitle: 'Setting up file storage...'
            },
            'reset-setup': {
                text: 'Resetting Setup',
                subtitle: 'Clearing configuration...'
            }
        };

        function showLoading(action) {
            const overlay = document.getElementById('loadingOverlay');
            const loadingText = document.getElementById('loadingText');
            const loadingSubtitle = document.getElementById('loadingSubtitle');

            if (loadingMessages[action]) {
                loadingText.textContent = loadingMessages[action].text;
                loadingSubtitle.textContent = loadingMessages[action].subtitle;
            }

            overlay.style.display = 'flex';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const action = form.action.split('/').pop();
                    showLoading(action);
                });
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
