@extends('layouts.installation')

@section('title', 'One-Click Installation')

@section('content')
<div class="step active">
    <h3>🚀 Complete One-Click Installation</h3>
    <p>Click the button below to automatically prepare and install your entire project.</p>

    <div style="background: #e3f2fd; padding: 20px; border-radius: 5px; margin: 30px 0;">
        <h4 style="margin: 0 0 15px 0;">📋 What will happen automatically:</h4>
        <ul style="text-align: left; margin: 10px 0 0 20px; font-size: 15px; line-height: 1.8;">
            <li><strong>Phase 1: System Preparation</strong>
                <ul style="margin: 5px 0 0 20px;">
                    <li>✅ Run <code>composer update</code></li>
                    <li>✅ Run <code>npm install</code></li>
                    <li>✅ Run <code>npm run build</code></li>
                    <li>✅ Start <code>php artisan serve</code></li>
                </ul>
            </li>
            <br>
            <li><strong>Phase 2: Project Installation</strong> (Opens automatically after Phase 1)
                <ul style="margin: 5px 0 0 20px;">
                    <li>✅ Create fresh .env file</li>
                    <li>✅ Configure database</li>
                    <li>✅ Generate application key</li>
                    <li>✅ Run migrations</li>
                    <li>✅ Seed database</li>
                    <li>✅ Optimize application</li>
                </ul>
            </li>
        </ul>
    </div>

    <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 30px;">
        <strong>⚠️ Important:</strong> This process may take 5-10 minutes depending on your internet speed and system performance. Please don't close this window until completion.
    </div>

    <button onclick="startCompleteInstallation()" class="btn" id="startBtn" style="font-size: 18px; padding: 15px 40px;">
        🚀 Start Complete Installation
    </button>

    <!-- Progress Modal -->
    <div id="progressModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center;">
        <div style="background: white; padding: 40px; border-radius: 10px; max-width: 700px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <h3 style="margin: 0 0 20px 0;">⚙️ Preparing System...</h3>
            
            <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <div id="progressBar" style="width: 0%; height: 30px; background: linear-gradient(90deg, #4CAF50, #45a049); border-radius: 5px; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                    0%
                </div>
            </div>

            <div id="statusMessage" style="margin: 20px 0; font-size: 16px; font-weight: bold; color: #333;">
                Initializing...
            </div>

            <div id="terminalLog" style="background: #1e1e1e; color: #00ff00; padding: 15px; border-radius: 5px; font-family: 'Courier New', monospace; font-size: 13px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word;">
                > System preparation starting...\n
            </div>

            <div id="completionMessage" style="display: none; margin-top: 20px; padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724;">
                <h4 style="margin: 0 0 10px 0;">✅ Phase 1 Complete!</h4>
                <p style="margin: 0;">Redirecting to installation page in <span id="countdown">3</span> seconds...</p>
            </div>
        </div>
    </div>
</div>

<script>
let progressInterval;
let progress = 0;

function updateProgress(percent, message) {
    progress = percent;
    const progressBar = document.getElementById('progressBar');
    progressBar.style.width = percent + '%';
    progressBar.textContent = percent + '%';
    document.getElementById('statusMessage').textContent = message;
}

function appendLog(message) {
    const log = document.getElementById('terminalLog');
    log.textContent += message + '\n';
    log.scrollTop = log.scrollHeight;
}

async function startCompleteInstallation() {
    const startBtn = document.getElementById('startBtn');
    const modal = document.getElementById('progressModal');
    
    startBtn.disabled = true;
    startBtn.textContent = '⏳ Please wait...';
    modal.style.display = 'flex';
    
    updateProgress(10, 'Starting system preparation...');
    appendLog('> Sending preparation request...');
    
    try {
        // Simulate progress during preparation
        progressInterval = setInterval(() => {
            if (progress < 90) {
                updateProgress(progress + 2, 'Preparing system...');
            }
        }, 1000);
        
        const response = await fetch('{{ route('installation.prepare-system') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        
        clearInterval(progressInterval);
        
        const data = await response.json();
        
        if (data.success) {
            updateProgress(100, 'System preparation complete!');
            
            appendLog('\n> ✅ Composer: ' + (data.results.composer ? 'SUCCESS' : 'FAILED'));
            appendLog('> ✅ NPM Install: ' + (data.results.npm ? 'SUCCESS' : 'FAILED'));
            appendLog('> ✅ NPM Build: ' + (data.results.build ? 'SUCCESS' : 'FAILED'));
            appendLog('> ✅ Server: ' + (data.results.server ? 'RUNNING' : 'FAILED'));
            appendLog('\n> 🎉 Phase 1 Complete! Starting Phase 2...\n');
            
            // Show completion message with countdown
            document.getElementById('completionMessage').style.display = 'block';
            
            let countdown = 3;
            const countdownInterval = setInterval(() => {
                countdown--;
                document.getElementById('countdown').textContent = countdown;
                
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    window.location.href = data.redirect_url;
                }
            }, 1000);
            
        } else {
            updateProgress(0, '❌ Preparation failed');
            appendLog('\n> ❌ ERROR: ' + data.message);
            startBtn.disabled = false;
            startBtn.textContent = '🔄 Try Again';
        }
        
    } catch (error) {
        clearInterval(progressInterval);
        updateProgress(0, '❌ Connection error');
        appendLog('\n> ❌ ERROR: ' + error.message);
        startBtn.disabled = false;
        startBtn.textContent = '🔄 Try Again';
    }
}
</script>

<style>
#terminalLog::-webkit-scrollbar {
    width: 8px;
}

#terminalLog::-webkit-scrollbar-track {
    background: #2d2d2d;
}

#terminalLog::-webkit-scrollbar-thumb {
    background: #555;
    border-radius: 4px;
}

#terminalLog::-webkit-scrollbar-thumb:hover {
    background: #777;
}
</style>
@endsection
