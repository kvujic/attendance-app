@extends('layouts.app')

@section('title', '管理者ログイン')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')

@include('components.admin_header')

<div class="login-form">
    <h1 class="login-form__title">管理者ログイン</h1>
    <form action="{{ route('admin.login') }}" method="POST" class="login-form__item" novalidate>
        @csrf
        <div class="login-form__group">
            <label for="email" class="login-form__label">メールアドレス</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" class="login-form__input">
            @error('email')
            <div class="login-form__error">{{ $message }}</div>
            @enderror
        </div>
        <div class="login-form__group">
            <label for="password" class="login-form__label">パスワード</label>
            <input type="password" id="password" name="password" autocomplete="new-password" class="login-form__input">
            @error('password')
            <div class="login-form__error">{{ $message }}</div>
            @enderror
        </div>
        <div class="login-form__button">
            <button class="login-form__button-submit" type="submit">管理者ログインする</button>
        </div>
    </form>
</div>
@endsection