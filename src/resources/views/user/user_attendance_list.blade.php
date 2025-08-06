@extends('layouts.app')

@section('title', '勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

@section('content')

@include('components.user_header')

<div class="attendance-list__container">
    <h1 class="attendance-list__title">勤怠一覧</h1>

@livewire('attendance-list')
</div>
@endsection