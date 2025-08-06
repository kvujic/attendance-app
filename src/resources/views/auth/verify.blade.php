@extends('layouts.app')

@section('title', 'メール認証')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verify.css') }}">
@endsection

@section('content')

@include('components.user_header')

<div class="verify-email">
    <h2 class="verify-email__text">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </h2>
    <div class="verify-email__link">
        <a href="http://localhost:8025" class="verify-email__link-button">認証はこちらから</a>
        <form action="{{ route('verification.send') }}" class="verify-email__form" method="POST">
            @csrf
            <div class="verify-email__resend">
                <button class="verify-email__resend-button" type="submit">認証メールを再送する</button>
            </div>
        </form>
    </div>
</div>
@endsection