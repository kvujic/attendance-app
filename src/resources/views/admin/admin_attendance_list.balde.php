@extends('layouts.app')

@section('title', '管理者勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

@section('content')

@include('components.admin_header')

<h1>管理者用勤怠一覧画面</h1>