@extends('layouts.app')

@section('title', '申請一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/correction-list.css') }}">
@endsection

@section('content')

@include('components.user_header')

<div class="correction-list__container">
    <h1 class="correction-list__title">申請一覧</h1>

    <div class="border">
        <ul class="tab-nav">
            <li class="tab-menu">
                <a href="{{ route('stamp_correction_request.index', ['tab'=>'pending']) }}" class="tab-link {{ $tab === 'pending' ? 'active' : '' }}">承認待ち</a>
            </li>
            <li class="tab-menu">
                <a href="{{ route('stamp_correction_request.index', ['tab'=>'approved']) }}" class="tab-link {{ $tab === 'approved' ? 'active' : '' }}">承認済み</a>
            </li>
        </ul>
    </div>

    <table class="correction-list__table">
        <thead>
            <tr class="correction-list__table-row header-row">
                <th class="correction-list__label">状態</th>
                <th class="correction-list__label">名前</th>
                <th class="correction-list__label">対象日時</th>
                <th class="correction-list__label">申請理由</th>
                <th class="correction-list__label">申請日時</th>
                <th class="correction-list__label">詳細</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($corrections as $correction)
            <tr class="correction-list__table-row body-row">
                <td class="correction-list__data data-status">{{ $correction->status_label }}</td>
                <td class="correction-list__data">{{ $correction->user->name }}</td>
                <td class="correction-list__data">{{ optional($correction->attendance)->date ? \Carbon\Carbon::parse($correction->attendance->date)->format('Y/m/d') : '' }}</td>
                <td class="correction-list__data">{{ $correction->request_note }}</td>
                <td class="correction-list__data">{{ $correction->created_at->format('Y/m/d') }}</td>
                <td class="correction-list__data data-detail">
                    @if ($correction->attendance_id)
                    <a href="{{ route('attendance.show', ['id' => $correction->attendance_id]) }}" class="detail-link">詳細</a>
                    @else
                    -
                    @endif
                </td>
            </tr>
            @empty
            <tr class="correction-list__table-row">
                <td class="correction-list__data list-message" colspan="6">申請はありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection