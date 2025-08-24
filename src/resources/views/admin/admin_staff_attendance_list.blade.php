@extends('layouts.app')

@section('title', 'スタッフ別勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

@section('content')

@include('components.admin_header')

<div class="attendance-list__container">
    <h1 class="attendance-list__title">{{ $user->name }}さんの勤怠</h1>

    @livewire('admin-user-monthly-attendance', ['user' => $user, 'month' => request('month')])

</div>
    @endsection