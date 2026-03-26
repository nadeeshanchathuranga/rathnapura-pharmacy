@extends('layouts.installation')

@section('title', 'Composer Installation')

@section('content')
<div class="step active">
    <h3>1. Install Composer Dependencies</h3>
    <p>Installing Composer packages required for Laravel...</p>

    <div class="info-box">
        <strong>ℹ️ What's happening?</strong>
        This step will download and install all PHP dependencies defined in your composer.json file.
        This may take several minutes on first installation.
    </div>

    <form method="POST" action="{{ route('installation.composer-install') }}">
        @csrf
        <button type="submit" class="btn">Install Composer Packages</button>
    </form>
</div>
@endsection
