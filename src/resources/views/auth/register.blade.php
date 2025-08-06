@extends('layouts.app')

@section('title', '会員登録')

@section('css')
<link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endsection

@section('content')

@include('components.user_header')
<div class="register-form">
    <h1 class="register-form__title">会員登録</h1>
    <form action="{{ route('register') }}" method="POST" class="register-form__item" novalidate>
        @csrf
        <div class="register-form__group">
            <label for="name" class="register-form__label">名前</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" class="register-form__input">
            @error('name')
            <div class="register-form__error">{{ $message }}</div>
            @enderror
        </div>
        <div class="register-form__group">
            <label for="email" class="register-form__label">メールアドレス</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" class="register-form__input">
            @error('email')
            <div class="register-form__error">{{ $message }}</div>
            @enderror
        </div>
        <div class="register-form__group">
            <label for="password" class="register-form__label">パスワード</label>
            <input type="password" id="password" name="password" autocomplete="new-password" class="register-form__input">
            @error('password')
            <div class="register-form__error">{{ $message }}</div>
            @enderror
        </div>
        <div class="register-form__group">
            <label for="password_confirmation" class="register-form__label">パスワード確認</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" class="register-form__input">
            @error('password_confirmation')
            <div class="register-form__error">{{ $message }}</div>
            @enderror
        </div>
        <div class="register-form__button">
            <button class="register-form__button-submit" type="submit">登録する</button>
        </div>
        <div class="register-form__link">
            <a href="{{ route('login') }}" class="register-form__link-login">ログインはこちら</a>
        </div>
    </form>
</div>
@endsection
