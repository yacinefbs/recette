@extends('social.layout')
@section('title', 'Sign in')
@section('content')
<div class="card" style="max-width:380px;margin:40px auto">
    <h1 style="margin-top:0;font-size:22px">Sign in</h1>
    <form method="post" action="{{ route('social.login.submit') }}">
        @csrf
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required autocomplete="current-password">
        @error('email')<p class="flash flash--err" style="margin-top:12px">{{ $message }}</p>@enderror
        <p><button class="btn" type="submit">Sign in</button></p>
    </form>
</div>
@endsection
