@extends('layouts.installation')

@section('title', 'Seed Databases')

@section('content')
<div class="step active">
    <h3>8. Seed Databases</h3>

    <div style="padding: 15px; background: #fff3cd; border-radius: 5px; margin: 15px 0;">
        <strong>🌱 Database Seeding</strong><br>
        <p style="font-size: 14px; margin: 5px 0;">This will populate both local and remote databases with initial data (if seeders exist).</p>
    </div>

    <form method="POST" action="{{ route('installation.seed-databases-execute') }}">
        @csrf
        <button type="submit" class="btn">Seed Both Databases</button>
    </form>

    <form method="GET" action="{{ route('installation.generate-key') }}" style="margin-top: 10px;">
        <button type="submit" class="btn btn-secondary">Skip Seeding</button>
    </form>
</div>
@endsection
