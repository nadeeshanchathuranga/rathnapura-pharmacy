@extends('layouts.installation')

@section('title', 'System Requirements Check')

@section('content')
<div class="step active">
    <h3>🔍 System Requirements Check</h3>
    <p>Checking your system compatibility with Laravel requirements...</p>

    <div class="system-info">
        <h4>📋 System Information</h4>
        <div class="info-grid">
            <div><strong>OS:</strong> {{ $systemInfo['os'] }}</div>
            <div><strong>PHP SAPI:</strong> {{ $systemInfo['php_sapi'] }}</div>
            <div><strong>Memory Limit:</strong> {{ $systemInfo['memory_limit'] }}</div>
            <div><strong>Max Execution Time:</strong> {{ $systemInfo['max_execution_time'] }}s</div>
        </div>
    </div>

    <h4>🔧 PHP Requirements</h4>
    @foreach($phpCheck['details'] as $name => $req)
        <div class="requirement-check {{ $req['status'] ? 'passed' : 'failed' }}">
            <div class="requirement-name">
                {{ ucfirst(str_replace('_', ' ', $name)) }}
            </div>
            <div class="requirement-value">
                {{ $req['current'] }}
                {{ $req['status'] ? '✅' : '❌' }}
            </div>
        </div>
    @endforeach

    <h4 style="margin-top: 20px;">📦 Development Tools</h4>
    <div class="requirement-check {{ $composerCheck['installed'] ? 'passed' : 'failed' }}">
        <div class="requirement-name">Composer</div>
        <div class="requirement-value">
            {{ $composerCheck['installed'] ? 'v' . $composerCheck['version'] . ' ✅' : 'Not installed ❌' }}
        </div>
    </div>

    <div class="requirement-check {{ $nodeCheck['node_installed'] ? 'passed' : 'failed' }}">
        <div class="requirement-name">Node.js</div>
        <div class="requirement-value">
            {{ $nodeCheck['node_installed'] ? 'v' . $nodeCheck['node_version'] . ' ' . ($nodeCheck['node_version_ok'] ? '✅' : '⚠️') : 'Not installed ❌' }}
        </div>
    </div>

    <div class="requirement-check {{ $nodeCheck['npm_installed'] ? 'passed' : 'failed' }}">
        <div class="requirement-name">NPM</div>
        <div class="requirement-value">
            {{ $nodeCheck['npm_installed'] ? 'v' . $nodeCheck['npm_version'] . ' ✅' : 'Not installed ❌' }}
        </div>
    </div>

    <h4 style="margin-top: 20px;">📁 Laravel Project Structure</h4>
    @foreach($laravelCheck as $name => $status)
        <div class="requirement-check {{ $status ? 'passed' : 'failed' }}">
            <div class="requirement-name">
                {{ ucfirst(str_replace('_', ' ', $name)) }}
            </div>
            <div class="requirement-value">
                {{ $status ? 'Found ✅' : 'Missing ❌' }}
            </div>
        </div>
    @endforeach

    @php
        $canProceed = $phpCheck['passed'] && $composerCheck['installed'] && $nodeCheck['node_installed'] && $nodeCheck['npm_installed'];
        $allLaravelFilesExist = $laravelCheck['composer_json'] && $laravelCheck['artisan'] && $laravelCheck['app_directory'];
    @endphp

    @if($canProceed && $allLaravelFilesExist)
        <div style="margin-top: 20px; padding: 15px; background: #d4edda; border-radius: 5px; color: #155724;">
            <strong>✅ All system requirements are met!</strong><br>
            You can proceed with the Laravel setup.
        </div>

        <div style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 2px solid #667eea;">
            <h4 style="margin: 0 0 15px 0; color: #333;">📦 Choose Installation Mode</h4>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <!-- Auto Install Option -->
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; color: white; text-align: center;">
                    <div style="font-size: 48px; margin-bottom: 10px;">🚀</div>
                    <h5 style="margin: 0 0 10px 0; font-size: 18px;">Automatic Installation</h5>
                    <p style="font-size: 13px; margin: 0 0 15px 0; opacity: 0.9;">
                        One-click setup! All steps run automatically including database configuration, migrations, and server startup.
                    </p>
                    <a href="{{ route('installation.auto-install') }}" class="btn" style="background: white; color: #667eea; font-size: 14px; padding: 10px 20px;">
                        ⚡ Quick Auto Install
                    </a>
                </div>

                <!-- Manual Install Option -->
                <div style="background: white; padding: 20px; border-radius: 8px; border: 2px solid #e0e0e0; text-align: center;">
                    <div style="font-size: 48px; margin-bottom: 10px;">🔧</div>
                    <h5 style="margin: 0 0 10px 0; font-size: 18px; color: #333;">Manual Installation</h5>
                    <p style="font-size: 13px; margin: 0 0 15px 0; color: #666;">
                        Step-by-step guided setup. Install each component individually with full control over the process.
                    </p>
                    <form method="POST" action="{{ route('installation.proceed-setup') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-secondary" style="font-size: 14px; padding: 10px 20px;">
                            📋 Step-by-Step Setup
                        </button>
                    </form>
                </div>
            </div>

            <div style="background: #fff3cd; padding: 12px; border-radius: 5px; font-size: 13px; text-align: center; color: #856404;">
                <strong>💡 Recommendation:</strong> Use <strong>Auto Install</strong> for fastest setup. Use <strong>Manual Install</strong> for learning or troubleshooting.
            </div>
        </div>
    @else
        <div style="margin-top: 20px; padding: 15px; background: #f8d7da; border-radius: 5px; color: #721c24;">
            <strong>❌ System requirements not met!</strong><br>
            Please install the missing requirements before proceeding.
        </div>
    @endif

    @if(file_exists(base_path('.env')))
        <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 5px; border: 1px solid #ffeaa7;">
            <strong>⚠️ Existing Configuration Found</strong><br>
            <p style="font-size: 12px; margin: 5px 0;">An .env file already exists. You can reset to start fresh.</p>
            <form method="POST" action="{{ route('installation.reset-setup') }}" style="margin-top: 10px;"
                  onsubmit="return confirm('This will delete your current .env file and restart setup. Continue?')">
                @csrf
                <button type="submit" class="btn btn-danger" style="font-size: 14px; padding: 8px 16px;">
                    🔄 Reset Configuration
                </button>
            </form>
        </div>
    @endif
</div>

<div class="info-box">
    <strong>Laravel Setup Wizard</strong>
    This wizard will guide you through the complete Laravel project setup process including:
    <ul>
        <li>Composer package installation</li>
        <li>NPM package installation and asset building</li>
        <li>Environment configuration</li>
        <li>Database setup and migration</li>
        <li>Application key generation</li>
        <li>Storage link creation</li>
    </ul>
</div>
@endsection
