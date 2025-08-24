<div>
    <div class="attendance-list__container attendance-list__admin">
        <h1 class="attendance-list__title">{{ $currentDate->format('Y年m月d日') }}の勤怠</h1>

        <div class="month-switcher">
            <button wire:click="previousDay" type="button" class="nav-button">
                <img src="{{ asset('images/arrow_left.svg') }}" alt="←" class="button-arrow">前日
            </button>
            <div class="month-display" id="current-day">
                <img src="{{ asset('images/calendar.png') }}" alt="calendar" class="calendar-icon">
                {{ $currentDate->format('Y/m/d') }}
            </div>
            <button wire:click="nextDay" class="nav-button">
                翌日<img src="{{ asset('images/arrow_right.svg') }}" alt="→" class="button-arrow">
            </button>
        </div>

        <table class="attendance-list__table">
            <thead class="attendance-list__table-header">
                <tr class="attendance-list__table-row">
                    <th class="list-title">名前</th>
                    <th class="list-title">出勤</th>
                    <th class="list-title">退勤</th>
                    <th class="list-title">休憩</th>
                    <th class="list-title">合計</th>
                    <th class="list-title">詳細</th>
                </tr>
            </thead>
            <tbody class="attendance-list__table-body">
                @forelse ($attendances as $attendance)
                <tr class="attendance-list__table-row row-body">
                    <td class="list-data list-data__name">{{ $attendance->user->name }}</td>
                    <td class="list-data">{{ $attendance?->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</td>
                    <td class="list-data">{{ $attendance?->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</td>
                    <td class="list-data">{{ $attendance?->total_break_time !== null ? (int)($attendance->total_break_time / 60) . ':' . str_pad($attendance->total_break_time % 60, 2, '0', STR_PAD_LEFT) : '' }}</td>
                    <td class="list-data">{{ $attendance?->total_work_time !== null ? (int)($attendance->total_work_time / 60) . ':' . str_pad($attendance->total_work_time % 60, 2, '0', STR_PAD_LEFT) : '' }}</td>
                    <td class="list-data list-data__detail">
                        @if ($attendance)
                        <a href="{{ route('admin.attendance.showDetail', ['id' => $attendance->id]) }}" class="list-data__detail-link">詳細</a>
                        @else
                        <a href="{{ route('admin.attendance.showDetail', ['user_id' => $user->id, 'id' => 'new', 'date' => $dateStr]) }}" class="list-data__detail-link">詳細</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td class="list-data" colspan="6">データがありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>