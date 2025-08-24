@extends('layouts.app')

@section('title', '申請一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user-attendance-detail.css') }}">
@endsection

@section('content')

@include('components.admin_header')

<div class="attendance-detail__container">
    <h1 class="attendance-detail__title">勤怠詳細</h1>

    <table class="attendance-detail__table">
        <tbody id="break-rows">
            <tr class="attendance-detail__table-row">
                <th class="detail-label">名前</th>
                <td class="detail-data data-name">{{ $correction->user->name }}</td>
            </tr>
            <tr class="attendance-detail__table-row">
                <th class="detail-label">日付</th>
                <td class="detail-data">
                    <span class="data__input-date year">{{ \Carbon\Carbon::parse($correction->attendance->date)->format('Y年') }}</span>
                    <span class="data__input-date month-date">{{ \Carbon\Carbon::parse($correction->attendance->date)->format('n月j日') }}</span>
                </td>
            </tr>
            <tr class="attendance-detail__table-row">
                <th class="detail-label">出勤・退勤</th>
                <td class="detail-data">
                    <span class="data__input-time data-readonly">{{ \Carbon\Carbon::parse($correction->requested_clock_in)->format('H:i') }}</span>
                    <span class="tilde">〜</span>
                    <span class="data__input-time data-readonly">{{ \Carbon\Carbon::parse($correction->requested_clock_out)->format('H:i') }}</span>
                </td>
            </tr>
            @foreach ($correction->correctionBreaks as $i => $b)
            <tr class="attendance-detail__table-row">
                <th class="detail-label">休憩</th>
                <td class="detail-data">
                    <span class="data__input-time data-readonly">{{ \Carbon\Carbon::parse($b->requested_break_start)->format('H:i') }}</span>
                    <span class="tilde">〜</span>
                    <span class="data__input-time data-readonly">{{ \Carbon\Carbon::parse($b->requested_break_end)->format('H:i') }}</span>
                </td>
            </tr>
            @endforeach
            <tr class="attendance-detail__table-row textarea">
                <th class="detail-label">備考</th>
                <td class="detail-data">
                    <p class="detail-data__text data-readonly">{{ $correction->request_note }}</p>
                </td>
            </tr>
        </tbody>
    </table>
    @if ($correction->status === 'pending')
    <form action="{{ route('stamp_correction_request.approve', $correction) }}" class="actions" id="approve-form" method="POST">
        @method('PATCH')
        @csrf
    </form>
    <div class="attendance-detail__action">
        <button class="action-button button-approve" type="submit" form="approve-form">承認</button>
    </div>
    @else
    <div class="attendance-detail__action">
        <span class="badge-approved">承認済み</span>
    </div>
    @endif
</div>
@endsection