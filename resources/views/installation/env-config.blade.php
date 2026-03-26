@extends('layouts.installation')

@section('title', 'Database Configuration')

@section('content')
<div class="step active">
    <h3>5. Configure Database</h3>
    <p>Set up your database connection:</p>

    <form method="POST" action="{{ route('installation.update-env') }}" id="dbConfigForm">
        @csrf

        <div style="margin-bottom: 20px; padding: 15px; background: #e3f2fd; border-radius: 5px;">
            <label style="display: flex; align-items: center; cursor: pointer;">
                <input type="checkbox" name="hibernate" value="1" id="hibernateCheck" onchange="toggleRemoteDB()" style="margin-right: 10px;">
                <strong>🔄 Enable Hibernate (Dual Database Support)</strong>
            </label>
            <p style="font-size: 12px; margin: 5px 0 0 25px; color: #666;">Enable this to configure both local and remote database connections</p>
        </div>

        <h4>📍 Local Database Configuration</h4>
        <div class="form-group">
            <label>Database Host:</label>
            <input type="text" name="db_host" value="localhost" required>
        </div>
        <div class="form-group">
            <label>Database Port:</label>
            <input type="text" name="db_port" value="3306" required>
        </div>
        <div class="form-group">
            <label>Database Name:</label>
            <input type="text" name="db_name" value="mini_zone" required>
        </div>
        <div class="form-group">
            <label>Database Username:</label>
            <input type="text" name="db_user" value="root" required>
        </div>
        <div class="form-group">
            <label>Database Password:</label>
            <input type="password" name="db_pass" value="">
        </div>

        <div id="remoteDbConfig" style="display: none; margin-top: 30px; padding: 20px; border: 2px dashed #ccc; border-radius: 5px;">
            <h4>🌐 Remote Database Configuration</h4>
            <div class="form-group">
                <label>Remote Database Host:</label>
                <input type="text" name="remote_db_host" placeholder="e.g., remote.example.com">
            </div>
            <div class="form-group">
                <label>Remote Database Port:</label>
                <input type="text" name="remote_db_port" value="3306">
            </div>
            <div class="form-group">
                <label>Remote Database Name:</label>
                <input type="text" name="remote_db_name" placeholder="e.g., mini_zone_remote">
            </div>
            <div class="form-group">
                <label>Remote Database Username:</label>
                <input type="text" name="remote_db_user" placeholder="Remote DB username">
            </div>
            <div class="form-group">
                <label>Remote Database Password:</label>
                <input type="password" name="remote_db_pass" placeholder="Remote DB password">
            </div>
        </div>

        <button type="submit" class="btn" style="margin-top: 20px;">Update Configuration</button>
    </form>
</div>

@push('scripts')
<script>
function toggleRemoteDB() {
    const hibernateCheck = document.getElementById('hibernateCheck');
    const remoteDbConfig = document.getElementById('remoteDbConfig');
    const remoteInputs = remoteDbConfig.querySelectorAll('input[name^="remote_"]');

    if (hibernateCheck.checked) {
        remoteDbConfig.style.display = 'block';
        remoteInputs.forEach(input => {
            if (input.name !== 'remote_db_pass') {
                input.required = true;
            }
        });
    } else {
        remoteDbConfig.style.display = 'none';
        remoteInputs.forEach(input => {
            input.required = false;
        });
    }
}
</script>
@endpush
@endsection
