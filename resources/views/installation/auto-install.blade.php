@extends('layouts.installation')

@section('title', 'Auto Installation')

@section('content')
<div class="step active">
    <h3>🚀 Automatic Installation</h3>
    <p>Configure your database settings below, then click <strong>"Start Auto Install"</strong> to automatically install all dependencies, configure the environment, set up the database, and start your Laravel project.</p>

    <form id="autoInstallForm" style="margin-top: 30px;">
        @csrf

        <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <strong>⚡ Automated Installation Steps:</strong>
            <ul style="text-align: left; margin: 10px 0 0 20px; font-size: 14px;">
                <li>1️⃣ Create fresh .env file (removes old one if exists)</li>
                <li>2️⃣ Configure database settings</li>
                <li>3️⃣ Generate NEW application key (<code>php artisan key:generate</code>)</li>
                <li>4️⃣ Run <code>composer update</code></li>
                <li>5️⃣ Run <code>npm install</code></li>
                <li>6️⃣ Build frontend assets (<code>npm run build</code>)</li>
                <li>7️⃣ Run database migrations (<code>php artisan migrate --force</code>)</li>
                <li>8️⃣ Run database seeders (<code>php artisan db:seed --force</code>)</li>
                <li>9️⃣ Create storage symbolic link</li>
                <li>🔟 Optimize application (cache configs)</li>
                <li>🚀 Automatically start the project server</li>
            </ul>
            <p style="margin: 10px 0 0 0; font-size: 13px; color: #856404;">
                <strong>⚠️ Note:</strong> Every installation starts FRESH - new .env, new key, complete rebuild!
            </p>
        </div>

        <h4 style="margin: 20px 0 15px 0; text-align: left;">  Local Database Configuration</h4>

        <div class="form-group">
            <label for="db_host">Database Host:</label>
            <input type="text" id="db_host" name="db_host" value="127.0.0.1" required>
        </div>

        <div class="form-group">
            <label for="db_port">Database Port:</label>
            <input type="number" id="db_port" name="db_port" value="3306" required>
        </div>

        <div class="form-group">
            <label for="db_name">Database Name:</label>
            <input type="text" id="db_name" name="db_name" value="jpos_db" required>
        </div>

        <div class="form-group">
            <label for="db_user">Database Username:</label>
            <input type="text" id="db_user" name="db_user" value="root" required>
        </div>

        <div class="form-group">
            <label for="db_pass">Database Password:</label>
            <input type="password" id="db_pass" name="db_pass" placeholder="Leave empty if no password">
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <label style="display: flex; align-items: center; cursor: pointer;">
                <input type="checkbox" id="hibernate" name="hibernate" style="width: auto; margin-right: 10px;">
                <span>Enable Hibernate Mode (Dual Database - Local + Remote)</span>
            </label>
        </div>

        <div id="remoteDbConfig" style="display: none; margin-top: 20px; padding: 20px; background: #e3f2fd; border-radius: 5px;">
            <h4 style="margin: 0 0 15px 0;">🌐 Remote Database Configuration</h4>

            <div class="form-group">
                <label for="remote_db_host">Remote Database Host:</label>
                <input type="text" id="remote_db_host" name="remote_db_host">
            </div>

            <div class="form-group">
                <label for="remote_db_port">Remote Database Port:</label>
                <input type="number" id="remote_db_port" name="remote_db_port" value="3306">
            </div>

            <div class="form-group">
                <label for="remote_db_name">Remote Database Name:</label>
                <input type="text" id="remote_db_name" name="remote_db_name">
            </div>

            <div class="form-group">
                <label for="remote_db_user">Remote Database Username:</label>
                <input type="text" id="remote_db_user" name="remote_db_user">
            </div>

            <div class="form-group">
                <label for="remote_db_pass">Remote Database Password:</label>
                <input type="password" id="remote_db_pass" name="remote_db_pass">
            </div>
        </div>

        <div style="margin-top: 30px; display: flex; gap: 10px; justify-content: center;">
            <a href="{{ route('installation.system-check') }}" class="btn btn-secondary">← Back</a>
            <button type="submit" class="btn" id="startInstallBtn">
                🚀 Start Auto Install
            </button>
        </div>
    </form>
</div>

<!-- Progress Modal -->
<div id="progressModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 10000; overflow-y: auto;">
    <div style="max-width: 900px; margin: 50px auto; background: white; border-radius: 10px; padding: 30px;">
        <h2 style="text-align: center; color: #333; margin-bottom: 20px;">
            <span id="progressTitle">⚙️ Installation in Progress...</span>
        </h2>

        <div style="background: #f8f9fa; border-radius: 5px; padding: 15px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <strong>Overall Progress:</strong>
                <span id="progressPercent">0%</span>
            </div>
            <div style="width: 100%; height: 20px; background: #e0e0e0; border-radius: 10px; overflow: hidden;">
                <div id="progressBarFill" style="width: 0%; height: 100%; background: linear-gradient(90deg, #667eea, #764ba2); transition: width 0.5s;"></div>
            </div>
        </div>

        <div style="background: #000; color: #0f0; padding: 20px; border-radius: 5px; font-family: 'Courier New', monospace; font-size: 13px; height: 400px; overflow-y: auto; margin-bottom: 20px;" id="logContainer">
            <div id="logContent">Initializing installation...</div>
        </div>

        <div id="completionActions" style="display: none; text-align: center;">
            <div style="background: #d4edda; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                <h3 style="color: #155724; margin: 0 0 10px 0;">✅ Installation Completed Successfully!</h3>
                <p style="color: #155724; margin: 0;">Laravel server is starting automatically...</p>
            </div>

            <button id="startServerBtn" class="btn" style="margin-right: 10px;">
                🚀 Manual Start Server (if needed)
            </button>
            <a href="http://127.0.0.1:8000" class="btn btn-secondary">
                🏠 Go to Application Now
            </a>
        </div>

        <div id="errorActions" style="display: none; text-align: center;">
            <div style="background: #f8d7da; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                <h3 style="color: #721c24; margin: 0 0 10px 0;">❌ Installation Failed</h3>
                <p style="color: #721c24; margin: 0;">Please check the log above for error details.</p>
            </div>

            <a href="{{ route('installation.auto-install') }}" class="btn btn-secondary">
                🔄 Try Again
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hibernateCheckbox = document.getElementById('hibernate');
    const remoteDbConfig = document.getElementById('remoteDbConfig');
    const autoInstallForm = document.getElementById('autoInstallForm');
    const progressModal = document.getElementById('progressModal');
    const logContent = document.getElementById('logContent');
    const progressBarFill = document.getElementById('progressBarFill');
    const progressPercent = document.getElementById('progressPercent');
    const progressTitle = document.getElementById('progressTitle');
    const completionActions = document.getElementById('completionActions');
    const errorActions = document.getElementById('errorActions');
    const startServerBtn = document.getElementById('startServerBtn');

    // Toggle remote database config
    hibernateCheckbox.addEventListener('change', function() {
        remoteDbConfig.style.display = this.checked ? 'block' : 'none';

        // Toggle required attribute on remote fields
        const remoteFields = remoteDbConfig.querySelectorAll('input');
        remoteFields.forEach(field => {
            if (field.type !== 'password') {
                field.required = this.checked;
            }
        });
    });

    // Handle form submission
    autoInstallForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Show progress modal
        progressModal.style.display = 'block';
        logContent.innerHTML = '<div style="color: #0f0;">🚀 Starting automatic installation...</div>';

        // Prepare form data
        const formData = new FormData(autoInstallForm);
        const data = {};
        formData.forEach((value, key) => {
            if (key === 'hibernate') {
                data[key] = hibernateCheckbox.checked ? 1 : 0;
            } else {
                data[key] = value;
            }
        });

        try {
            // Start installation
            updateProgress(5, 'Preparing installation...');

            const response = await fetch('{{ route("installation.auto-install-execute") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: 'Server error' }));
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                // Display log
                displayLog(result.log);
                updateProgress(100, '✅ Installation Completed!');
                progressTitle.innerHTML = '✅ Installation Completed Successfully!';

                // Check if server started automatically
                if (result.server_started) {
                    logContent.innerHTML += '<div style="color: #0f0; margin-top: 10px; font-size: 14px;">🚀 Laravel server started automatically!</div>';
                    logContent.innerHTML += '<div style="color: #0f0; font-size: 14px;">🌐 Application URL: ' + result.server_url + '</div>';
                    logContent.innerHTML += '<div style="color: #ff0; margin-top: 10px; font-size: 14px;">⏳ Redirecting to application in 5 seconds...</div>';

                    // Auto-redirect to application after 5 seconds
                    setTimeout(() => {
                        window.location.href = result.server_url;
                    }, 5000);
                } else {
                    // Show manual start button if auto-start failed
                    completionActions.style.display = 'block';
                }
            } else {
                displayLog(result.log || result.message);
                progressTitle.innerHTML = '❌ Installation Failed';
                errorActions.style.display = 'block';
            }
        } catch (error) {
            console.error('Installation error:', error);
            let errorMsg = error.message;
            let errorDetails = '';

            if (error.message === 'Failed to fetch') {
                errorMsg = 'Connection error. This could be due to:';
                errorDetails = '<div style="margin-top: 10px; font-size: 12px;">';
                errorDetails += '• Server is not responding (check if PHP artisan serve is running)<br>';
                errorDetails += '• Network connectivity issue<br>';
                errorDetails += '• Browser blocking the request<br>';
                errorDetails += '• CORS policy issue<br>';
                errorDetails += '<br>Current URL: ' + window.location.href + '<br>';
                errorDetails += 'Target URL: {{ route("installation.auto-install-execute") }}<br>';
                errorDetails += 'Server running on: http://127.0.0.1:8000';
                errorDetails += '</div>';
            }

            logContent.innerHTML += '<div style="color: #f00;">❌ Error: ' + errorMsg + errorDetails + '</div>';
            progressTitle.innerHTML = '❌ Installation Failed';
            errorActions.style.display = 'block';
        }
    });

    // Start server button
    startServerBtn.addEventListener('click', async function() {
        startServerBtn.disabled = true;
        startServerBtn.innerHTML = '⏳ Starting server...';

        try {
            const response = await fetch('{{ route("installation.start-server") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                logContent.innerHTML += '<div style="color: #0f0; margin-top: 10px;">✅ ' + result.message + '</div>';
                logContent.innerHTML += '<div style="color: #0f0;">🌐 Server URL: ' + result.url + '</div>';
                startServerBtn.innerHTML = '✅ Server Started!';

                // Auto-redirect after 3 seconds
                setTimeout(() => {
                    window.location.href = result.url;
                }, 3000);
            } else {
                logContent.innerHTML += '<div style="color: #f00; margin-top: 10px;">❌ ' + result.message + '</div>';
                startServerBtn.innerHTML = '❌ Failed to Start';
                startServerBtn.disabled = false;
            }
        } catch (error) {
            logContent.innerHTML += '<div style="color: #f00; margin-top: 10px;">❌ Error: ' + error.message + '</div>';
            startServerBtn.innerHTML = '❌ Failed to Start';
            startServerBtn.disabled = false;
        }
    });

    function updateProgress(percent, message) {
        progressPercent.textContent = percent + '%';
        progressBarFill.style.width = percent + '%';
        if (message) {
            logContent.innerHTML += '<div style="color: #0ff; margin-top: 5px;">' + message + '</div>';
        }
        // Auto-scroll to bottom
        document.getElementById('logContainer').scrollTop = document.getElementById('logContainer').scrollHeight;
    }

    function displayLog(log) {
        if (!log) return;

        const lines = log.split('\n');
        let html = '';

        lines.forEach(line => {
            if (line.includes('✅')) {
                html += '<div style="color: #0f0;">' + escapeHtml(line) + '</div>';
            } else if (line.includes('❌')) {
                html += '<div style="color: #f00;">' + escapeHtml(line) + '</div>';
            } else if (line.includes('⚠️')) {
                html += '<div style="color: #ff0;">' + escapeHtml(line) + '</div>';
            } else if (line.includes('Step')) {
                html += '<div style="color: #0ff; font-weight: bold; margin-top: 10px;">' + escapeHtml(line) + '</div>';
            } else {
                html += '<div style="color: #0f0;">' + escapeHtml(line) + '</div>';
            }
        });

        logContent.innerHTML = html;
        document.getElementById('logContainer').scrollTop = document.getElementById('logContainer').scrollHeight;

        // Calculate progress based on steps
        const totalSteps = 12;
        const completedSteps = (log.match(/✅/g) || []).length;
        const progress = Math.min(100, Math.round((completedSteps / totalSteps) * 100));
        updateProgress(progress, null);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
@endsection
