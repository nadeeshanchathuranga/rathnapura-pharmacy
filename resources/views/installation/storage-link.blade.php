@extends('layouts.installation')

@section('title', 'Create Storage Link')

@section('content')
<div class="step active">
    <h3>10. Create Storage Link</h3>

    <div class="info-box">
        <strong>ℹ️ What's happening?</strong>
        This step creates a symbolic link from public/storage to storage/app/public,
        allowing files stored in the storage directory to be publicly accessible.
    </div>

    <form method="POST" action="{{ route('installation.storage-link-execute') }}">
        @csrf
        <button type="submit" class="btn">Create Storage Link</button>
    </form>
</div>
@endsection
