@extends('layouts.app')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user-attendance-detail.css') }}">
@endsection

@section('content')

@include('components.user_header')

<div class="attendance-detail__container">
    <h1 class="attendance-detail__title">勤怠詳細</h1>
    <form action="{{ route('attendance.update', $attendance->id) }}" method="POST">
        @method('PUT')
        @csrf
        <table class="attendance-detail__table">
            <tbody>
                <tr class="attendance-detail__table-row">
                    <th class="detail-label">名前</th>
                    <td class="detail-data data-name">{{ $user->name }} </td>
                </tr>
                <tr class="attendance-detail__table-row">
                    <th class="detail-label">日付</th>
                    <td class="detail-data">
                        <span class="data__year">{{ \Carbon\Carbon::parse($attendance->date)->format('Y年') }}</span>
                        <span class="data__month-date">{{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}</span>
                    </td>
                </tr>
                <tr class="attendance-detail__table-row">
                    <th class="detail-label">出勤・退勤</th>
                    <td class="detail-data">
                        <input type="text" class="data__input-time" name="clock_in" value="{{ \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') }}" {{ $isPending ? 'disabled' : '' }}>
                        <span class="tilde">〜</span>
                        <input type="text" class="data__input-time" name="clock_out" value="{{ \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') }}" {{ $isPending ? 'disabled' : ''}}>
                        @error('clock_in')
                        <div class="attendance-detail__error">{{ $message }}</div>
                        @enderror
                        @error('clock_out')
                        <div class="attendance-detail__error">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>

                @foreach ($breaks as $i => $break)
                <tr class="attendance-detail__table-row break-row">
                    <th class="detail-label">{{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}</th>
                    <td class="detail-data">
                        <input type="text" class="data__input-time" name="breaks[{{ $i }}][start]" value="{{ old("breaks.{$i}.start", $break['start']) }}" {{ $isPending ? 'disabled' : ''}} oninput="maybeAddRow({{ $i }})">
                        <span class="tilde">〜</span>
                        <input type="text" class="data__input-time" name="breaks[{{ $i }}][end]" value="{{ old("breaks.{$i}.end", $break['end']) }}" {{ $isPending ? 'disabled' : ''}} oninput="maybeAddRow({{ $i }})">
                        @error('breaks.*.start')
                        <div class="attendance-detail__error">{{ $message }}</div>
                        @enderror
                        @error('breaks.*.end')
                        <div class="attendance-detail__error">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                @endforeach
                <tr class="attendance-detail__table-row textarea">
                    <th class="detail-label">備考</th>
                    <td class="detail-data">
                        <textarea name="note" id="" class="detail-data__text" cols="15" rows="3" {{ $isPending ? 'disabled' : ''}}>{{ old('note',$attendance->note) }}</textarea>
                        @error('note')
                        <div class="attendance-detail__error">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="attendance-detail__action">
            @if (!$isPending)
            <button class="action-button" type="submit">修正</button>
            @else
            <p class="pending-message">*承認待ちのため修正はできません。</p>
            @endif
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    window.breakIndex = {
        {
            $breakCount
        }
    };
</script>
<script src="{{ asset('js/attendance-detail.js') }}"></script>
@endsection