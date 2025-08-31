@extends('layouts.app')

@section('title', 'スタッフ別勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

@section('content')

@include('components.admin_header')

<div class="attendance-list__container">
    <h1 class="attendance-list__title">{{ $user->name }}さんの勤怠</h1>

    <div class="month-switcher">
        <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $prevMonthStr]) }}" class="nav-button">
            <img src="{{ asset('images/arrow_left.svg') }}" alt="←" class="button-arrow">前月
        </a>

        <div class="month-display" id="current-month">
            <img src="{{ asset('images/calendar.png') }}" alt="calendar" class="calendar-icon">
            {{ $currentMonth->format('Y/m') }}
        </div>

        @if ($nextMonthStr)
        <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $nextMonthStr]) }} " class="nav-button">
            翌月<img src="{{ asset('images/arrow_right.svg') }}" alt="→" class="button-arrow">
        </a>
        @else
        <button class="nav-button" type="button" disabled>
            翌月<img src="{{ asset('images/arrow_right.svg') }}" alt="→" class="button-arrow">
        </button>
        @endif
    </div>

    <table class="attendance-list__table">
        <thead class="attendance-list__table-header">
            <tr class="attendance-list__table-row">
                <th class="list-title">日付</th>
                <th class="list-title">出勤</th>
                <th class="list-title">退勤</th>
                <th class="list-title">休憩</th>
                <th class="list-title">合計</th>
                <th class="list-title">詳細</th>
            </tr>
        </thead>
        <tbody class="attendance-list__table-body">
            @foreach ($rows as $row)
            <tr class="attendance-list__table-row row-body">
                <td class="list-data list-data__days">{{ $row['label'] }}</td>
                <td class="list-data">
                    {{ $row['clock_in'] }}
                </td>
                <td class="list-data">
                    {{ $row['clock_out'] }}
                </td>
                <td class="list-data">
                    {{ $row['break_total'] }}
                </td>
                <td class="list-data">
                    {{ $row['work_total'] }}
                </td>
                <td class="list-data list-data__detail">
                    @if (!empty($row['attendance']))
                    <a href="{{ route('admin.attendance.showDetail', ['id' => $row['attendance']->id]) }}" class="list-data__detail-link">詳細</a>
                    @else
                    <a href="{{ route('admin.attendance.showDetail', ['user_id' => $user->id, 'id' => 'new', 'date' => $row['dateStr']]) }}" class="list-data__detail-link">詳細</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{-- CSV --}}
    <div class="csv-button__wrapper">
        <a class="csv-button" href="{{ route('admin.attendance.csv', [ 'id' => $user->id, 'month' => $currentMonth->format('Y-m'),]) }}">CSV出力</a>
    </div>

</div>
@endsection