@extends('layouts.app')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user-attendance-detail.css') }}">
@endsection

@section('content')

@include('components.admin_header')

<div class="attendance-detail__container">
    <h1 class="attendance-detail__title">勤怠詳細</h1>
    <form action="{{ route('admin.attendance.updateAttendance', ['id' => $id]) }}" method="POST">
        @csrf
        <input type="hidden" name="user_id" value="{{ $user->id }}">
        <table class="attendance-detail__table">
            <tbody id="break-rows">
                <tr class="attendance-detail__table-row">
                    <th class="detail-label">名前</th>
                    <td class="detail-data data-name">{{ $user->name }} </td>
                </tr>
                <tr class="attendance-detail__table-row">
                    <th class="detail-label">日付</th>
                    <td class="detail-data data-date">
                        <input type="text" class="data__input-date year" value="{{ \Carbon\Carbon::parse($attendance->date)->format('Y年') }}" readonly>
                        <input type="text" class="data__input-date month-date" value="{{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}" readonly>
                        <input type="hidden" name="date" value="{{ \Carbon\Carbon::parse($attendance->date)->format('Y-m-d') }}">
                        @error('date')
                        <div class="attendance-detail__error">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                <tr class="attendance-detail__table-row">
                    <th class="detail-label">出勤・退勤</th>
                    <td class="detail-data">
                        <input type="text" class="data__input-time" name="requested_clock_in" value="{{ $requestedClockIn }}" {{ $isPending ? 'readonly' : '' }}>
                        <span class="tilde">〜</span>
                        <input type="text" class="data__input-time" name="requested_clock_out" value="{{ $requestedClockOut }}" {{ $isPending ? 'readonly' : ''}}>
                        @error('requested_clock_in')
                        <div class="attendance-detail__error">{{ $message }}</div>
                        @enderror
                        @error('requested_clock_out')
                        <div class="attendance-detail__error">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>

                @foreach ($breaks as $break)
                <tr class="attendance-detail__table-row break-row">
                    <th class="detail-label">{{ $loop->index === 0 ? '休憩' : '休憩' . ($loop->index + 1) }}</th>
                    <td class="detail-data">
                        <input type="text" class="data__input-time" name="breaks[{{ $loop->index }}][requested_break_start]" value="{{ old('breaks.'.$loop->index.'.requested_break_start', data_get($break, 'requested_break_start', '')) }}" {{ $isPending ? 'readonly' : '' }} oninput="maybeAddRow({{ $loop->index }})">

                        <span class="tilde">〜</span>

                        <input type="text" class="data__input-time" name="breaks[{{ $loop->index }}][requested_break_end]" value="{{ old('breaks.'.$loop->index.'.requested_break_end', data_get($break, 'requested_break_end', '')) }}" {{ $isPending ? 'readonly' : '' }} oninput="maybeAddRow({{ $loop->index }})">

                        @if ($errors->has('breaks.'.$loop->index.'.requested_break_end'))
                        <div class="attendance-detail__error">
                            {{ $errors->first('breaks.'.$loop->index.'.requested_break_end') }}
                        </div>
                        @elseif ($errors->has('breaks.'.$loop->index.'.requested_break_start'))
                        <div class="attendance-detail__error">
                            {{ $errors->first('breaks.'.$loop->index.'.requested_break_start' )}}
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach
                <tr class="attendance-detail__table-row textarea">
                    <th class="detail-label">備考</th>
                    <td class="detail-data">
                        <textarea name="request_note" id="" class="detail-data__text" cols="15" rows="3" {{ $isPending ? 'readonly' : ''}}>{{ old('request_note',$correction->request_note ?? '') }}</textarea>
                        @error('request_note')
                        <div class="attendance-detail__error">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="attendance-detail__action">
            @if (session('status') === 'updated')
            <p class="pending-message">修正しました</p>
            @elseif (!$isPending)
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
    window.isPending = @json($isPending);
    window.breakIndex = @json(max(1, $breakCount));
</script>
<script src="{{ asset('js/attendance-detail.js') }}"></script>
@endsection