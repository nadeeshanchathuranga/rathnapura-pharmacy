@extends('layouts.installation')

@section('title', 'Build Assets')

@section('content')
<div class="step active">
    <h3>3. Build Assets</h3>
    <p>Building CSS and JavaScript assets for production...</p>

    <div class="info-box">
        <strong>ℹ️ What's happening?</strong>
        This step will compile and optimize your application's frontend assets using Vite.
    </div>

    <form method="POST" action="{{ route('installation.npm-build-execute') }}">
        @csrf
        <button type="submit" class="btn">Build Assets</button>
    </form>
</div>
@endsection
