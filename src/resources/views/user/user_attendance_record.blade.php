@extends('layouts.app')

@section('title', '勤怠登録')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user-attendance-record.css') }}">
@endsection

@section('content')

@include('components.user_header')

<div class="attendance-container">
    <div class="status-display">
        <p class="current-status">
            @switch($attendanceStatus)
            @case('outside_work') 勤務外 @break
            @case('working') 勤務中 @break
            @case('on_break') 休憩中 @break
            @case('after_work') 退勤済 @break
            @endswitch
        </p>
    </div>

    @php
    use Carbon\Carbon;
    $now = Carbon::now();
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    @endphp
    <div class="datetime">
        <p class="date" id="current-date">{{ $now->format("Y年n月j日") }}({{ $weekdays[$now->dayOfWeek] }})</p>
        <p class="time" id="current-time">{{ $now->format("H:i") }}</p>
    </div>

    <div class="punch-buttons">
        <form action="{{ route('attendance.create') }}" method="POST">
            @csrf
            @if ($attendanceStatus === 'outside_work')
            <button class="clock-in" type="submit" name="action" value="clock_in">出勤</button>
            @elseif ($attendanceStatus === 'working')
            <button class="clock-out" type="submit" name="action" value="clock_out">退勤</button>
            <button class="break-start" type="submit" name="action" value="break_start">休憩入</button>
            @elseif ($attendanceStatus === 'on_break')
            <button class="break-end" type="submit" name="action" value="break_end">休憩戻</button>
            @elseif ($attendanceStatus === 'after_work')
            <p class="clock-out__message">お疲れ様でした。</p>
            @endif
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const weekdays = ['日', '月', '火', '水', '木', '金', '土'];

    function updateDate() {
        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1);
        const d = String(now.getDate());
        const day = weekdays[now.getDay()];
        document.getElementById('current-date').textContent = `${y}年${m}月${d}日(${day})`;
    }

    function updateTime() {
        const now = new Date();
        const time = now.toLocaleTimeString('ja-JP', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });
        document.getElementById('current-time').textContent = time;
    }

    updateDate();
    updateTime();
    setInterval(updateTime, 1000);
</script>
@endsection