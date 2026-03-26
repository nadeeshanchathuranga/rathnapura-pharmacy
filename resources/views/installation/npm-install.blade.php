@extends('layouts.installation')

@section('title', 'NPM Installation')

@section('content')
<div class="step active">
    <h3>2. Install NPM Dependencies</h3>
    <p>Installing Node.js packages required for asset building...</p>

    <div class="info-box">
        <strong>ℹ️ What's happening?</strong>
        This step will download and install all JavaScript dependencies defined in your package.json file.
        This may take several minutes on first installation.
    </div>

    <form method="POST" action="{{ route('installation.npm-install-execute') }}">
        @csrf
        <button type="submit" class="btn">Install NPM Packages</button>
    </form>
</div>
@endsection
