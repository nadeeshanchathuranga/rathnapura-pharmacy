@extends('layouts.installation')

@section('title', 'Environment Setup')

@section('content')
<div class="step active">
    <h3>4. Create Environment File</h3>
    <p>Create .env file from .env.example</p>

    <div class="info-box">
        <strong>ℹ️ What's happening?</strong>
        The .env file contains all your application's configuration settings including database credentials,
        application keys, and other environment-specific values.
    </div>

    <form method="POST" action="{{ route('installation.create-env') }}">
        @csrf
        <button type="submit" class="btn">Create .env File</button>
    </form>
</div>
@endsection
