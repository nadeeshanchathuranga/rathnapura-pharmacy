@extends('layouts.installation')

@section('title', 'Database Connection Test')

@section('content')
<div class="step active">
    <h3>6. Test Database Connection</h3>

    @if($hibernateEnabled && $localTest && $remoteTest)
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
            <div style="padding: 15px; border-radius: 5px; {{ $localTest['success'] ? 'background: #d4edda; border: 1px solid #c3e6cb;' : 'background: #f8d7da; border: 1px solid #f5c6cb;' }}">
                <h4>📍 Local Database ({{ $localTest['host'] }})</h4>
                @if($localTest['success'])
                    <p style="color: #155724;">✅ Connection successful!</p>
                    <p style="font-size: 12px; color: #155724;">Database: {{ $localTest['database'] }} {{ $localTest['db_exists'] ? '(exists)' : '(will be created)' }}</p>
                @else
                    <p style="color: #721c24;">❌ Connection failed!</p>
                    <p style="font-size: 12px; color: #721c24;">{{ $localTest['error'] }}</p>
                @endif
            </div>

            <div style="padding: 15px; border-radius: 5px; {{ $remoteTest['success'] ? 'background: #d4edda; border: 1px solid #c3e6cb;' : 'background: #f8d7da; border: 1px solid #f5c6cb;' }}">
                <h4>🌐 Remote Database ({{ $remoteTest['host'] }})</h4>
                @if($remoteTest['success'])
                    <p style="color: #155724;">✅ Connection successful!</p>
                    <p style="font-size: 12px; color: #155724;">Database: {{ $remoteTest['database'] }} {{ $remoteTest['db_exists'] ? '(exists)' : '(will be created)' }}</p>
                @else
                    <p style="color: #721c24;">❌ Connection failed!</p>
                    <p style="font-size: 12px; color: #721c24;">{{ $remoteTest['error'] }}</p>
                @endif
            </div>
        </div>

        @if($localTest['success'] && $remoteTest['success'])
            <div style="padding: 15px; background: #d4edda; border-radius: 5px; color: #155724; margin: 20px 0;">
                <strong>🎉 Both database connections are successful!</strong><br>
                Your hibernate setup is ready to proceed.
            </div>
            <form method="POST" action="{{ route('installation.migrate-execute') }}">
                @csrf
                <button type="submit" class="btn">Run Migrations on Both Databases</button>
            </form>
        @else
            <div style="padding: 15px; background: #f8d7da; border-radius: 5px; color: #721c24; margin: 20px 0;">
                <strong>❌ One or both database connections failed!</strong><br>
                Please fix the connection issues before proceeding.
            </div>
            <a href="{{ route('installation.env-config') }}" class="btn btn-secondary">Back to Configuration</a>
        @endif
    @else
        @if($localTest && $localTest['success'])
            @if($localTest['db_exists'])
                <p style="color: green;">✅ Database connection successful! Database exists.</p>
                <form method="POST" action="{{ route('installation.migrate-execute') }}">
                    @csrf
                    <button type="submit" class="btn">Run Migrations</button>
                </form>
            @else
                <p style="color: orange;">⚠️ Database connection successful, but database doesn't exist.</p>
                <form method="POST" action="{{ route('installation.create-database') }}">
                    @csrf
                    <button type="submit" class="btn">Create Database</button>
                </form>
            @endif
        @else
            <p style="color: red;">❌ Database connection failed: {{ $localTest['error'] ?? 'Unknown error' }}</p>
            <a href="{{ route('installation.env-config') }}" class="btn btn-secondary">Back to Configuration</a>
        @endif
    @endif
</div>
@endsection
