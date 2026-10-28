@extends('layouts.heartscope')
@section('title', 'My Profile')
@section('content')
<div class="stemi-page-banner"><div><div class="stemi-crumb">My Account <span>›</span> Profile</div><div class="stemi-eyebrow">CLINICAL TEAM</div><h1>My Profile</h1><p>Your profile and clinical account information.</p></div></div>
<section class="stemi-card stemi-form-card"><div class="stemi-card-heading"><span class="stemi-section-icon">♙</span><div><h2>{{ auth()->user()->name }}</h2><p>{{ ucfirst(auth()->user()->role) }} Account</p></div></div><dl class="stemi-profile-details"><div><dt>Name</dt><dd>{{ auth()->user()->name }}</dd></div><div><dt>Email</dt><dd>{{ auth()->user()->email }}</dd></div><div><dt>Role</dt><dd>{{ ucfirst(auth()->user()->role) }}</dd></div><div><dt>Specialization</dt><dd>{{ auth()->user()->specialization ?? 'Not specified' }}</dd></div><div><dt>Status</dt><dd>{{ ucfirst(auth()->user()->status) }}</dd></div></dl></section>
@endsection
