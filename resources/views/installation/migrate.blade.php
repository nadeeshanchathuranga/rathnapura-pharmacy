@extends('layouts.installation')

@section('title', 'Database Migrations')

@section('content')
<div class="step active">
    <h3>7. Run Database Migrations</h3>

    @if($hibernateEnabled)
        <div style="padding: 15px; background: #e3f2fd; border-radius: 5px; margin: 15px 0;">
            <strong>🔄 Hibernate Mode Active</strong><br>
            <p style="font-size: 14px; margin: 5px 0;">Migrations will run on both local and remote databases.</p>
        </div>
    @endif

    <div class="info-box">
        <strong>ℹ️ What's happening?</strong>
        This step will create all necessary database tables and structures defined in your migration files.
    </div>

    <form method="POST" action="{{ route('installation.migrate-execute') }}">
        @csrf
        <button type="submit" class="btn">Run Migrations</button>
    </form>
</div>
@endsection
