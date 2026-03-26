@extends('layouts.installation')

@section('title', 'Setup Complete')

@section('content')
<div class="step active" style="background: #d4edda; border-color: #c3e6cb;">
    <h3 style="color: #155724;">✅ Setup Complete!</h3>
    <p style="color: #155724;">Your Laravel application is ready to use.</p>

    @if($isServerRunning)
        <p style="color: green; margin: 15px 0;">🟢 Server is running on <a href="http://127.0.0.1:8000" target="_blank">http://127.0.0.1:8000</a></p>
        <p style="font-size: 14px; margin: 10px 0; color: #155724;">
            <strong>Redirecting to your application in <span id="countdown">3</span> seconds...</strong>
        </p>
    @else
        <p style="color: orange; margin: 15px 0;">🟠 Server is not running</p>
        <p style="font-size: 14px; margin: 10px 0;">
            To start the development server, run: <code style="background: #f8f9fa; padding: 2px 8px; border-radius: 3px;">php artisan serve</code>
        </p>
    @endif

    <div style="margin-top: 20px;">
        <a href="/" class="btn">Go to Application</a>
    </div>
</div>

<div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px; border: 1px dashed #ccc;">
    <h4 style="margin: 0 0 10px 0; color: #666;">🔄 Reset Configuration</h4>
    <p style="font-size: 12px; color: #666; margin: 0 0 15px 0;">Need to reconfigure? This will delete the .env file and restart the setup process.</p>
    <form method="POST" action="{{ route('installation.reset-setup') }}" style="margin: 0;"
          onsubmit="return confirm('Are you sure? This will delete all configuration and restart the setup process.')">
        @csrf
        <button type="submit" class="btn btn-danger" style="font-size: 14px; padding: 10px 20px;">
            🔄 Reset to Defaults
        </button>
    </form>
</div>

<div class="info-box" style="margin-top: 20px;">
    <strong>📚 Next Steps:</strong>
    <ul>
        <li>Access your application at <a href="http://127.0.0.1:8000" target="_blank">http://127.0.0.1:8000</a></li>
        <li>Review your .env file for additional configuration</li>
        <li>Check the <a href="https://laravel.com/docs" target="_blank">Laravel documentation</a> for more information</li>
        <li>Start building your application!</li>
    </ul>
</div>

@if($isServerRunning)
    @push('scripts')
    <script>
        // Auto-redirect to home page when server is running
        let countdown = 3;
        const countdownElement = document.getElementById('countdown');

        const timer = setInterval(() => {
            countdown--;
            if (countdownElement) {
                countdownElement.textContent = countdown;
            }

            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = '/';
            }
        }, 1000);

        // Also redirect immediately if user clicks anywhere
        document.addEventListener('click', function(e) {
            if (!e.target.matches('form, form *, button, button *')) {
                window.location.href = '/';
            }
        });
    </script>
    @endpush
@endif
@endsection
