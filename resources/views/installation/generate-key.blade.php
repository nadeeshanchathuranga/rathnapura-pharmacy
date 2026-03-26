@extends('layouts.installation')

@section('title', 'Generate Application Key')

@section('content')
<div class="step active">
    <h3>9. Generate Application Key</h3>

    <div class="info-box">
        <strong>ℹ️ What's happening?</strong>
        This step will generate a unique application key used for encrypting data and securing your application.
    </div>

    <form method="POST" action="{{ route('installation.generate-key-execute') }}">
        @csrf
        <button type="submit" class="btn">Generate Key</button>
    </form>
</div>
@endsection
