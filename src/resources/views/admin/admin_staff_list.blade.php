@extends('layouts.app')

@section('title', 'スタッフ一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-staff-list.css') }}">
@endsection

@section('content')

@include('components.admin_header')

<div class="staff-list__container">
    <h1 class="staff-list__title">スタッフ一覧</h1>

    <table class="staff-list__table">
        <thead class="staff-list__table-header">
            <tr class="staff-list__table-row">
                <th class="staff-list__label">名前</th>
                <th class="staff-list__label">メールアドレス</th>
                <th class="staff-list__label">月次勤怠</th>
            </tr>
        </thead>
        <tbody class="staff-list__table-body">
            @foreach ($users as $user)
            <tr class="staff-list__table-row">
                <td class="staff-list__data">{{ $user->name }}</td>
                <td class="staff-list__data">{{ $user->email }}</td>
                <td class="staff-list__data data-detail">
                    <a href="{{ route('admin.attendance.staff', ['user' => $user->id, 'month' => now()->format('Y/m')]) }}" class="staff-list__link">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>