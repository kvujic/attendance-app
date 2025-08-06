<div>
    <div class="month-switcher">
        <button wire:click="previousMonth" class="nav-button">
            <img src="{{ asset('images/arrow_left.svg') }}" alt="←" class="button-arrow">前月
        </button>

        <div class="month-display" id="current-month">
            <img src="{{ asset('images/calendar.png') }}" alt="calendar" class="calendar-icon">
            {{ $currentMonth->format('Y/m') }}
        </div>

        <button wire:click="nextMonth" class="nav-button">
            翌月<img src="{{ asset('images/arrow_right.svg') }}" alt="→" class="button-arrow">
        </button>
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
            @foreach ($daysInMonth as $day)
            @php
            $dateStr = $day->toDateString();
            $attendance = $attendances[$dateStr] ?? null;
            @endphp
            <tr class="attendance-list__table-row row-body">
                <td class="list-data list-data__days">{{$day->locale('ja')->isoFormat('MM/DD(dd)') }}</td>
                <td class="list-data">{{ $attendance?->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</td>
                <td class="list-data">{{ $attendance?->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</td>
                <td class="list-data">{{ $attendance?->total_break_time !== null ? (int)($attendance->total_break_time / 60) . ':' . str_pad($attendance->total_break_time % 60, 2, '0', STR_PAD_LEFT) : '' }}</td>
                <td class="list-data">{{ $attendance?->total_work_time !== null ? (int)($attendance->total_work_time / 60) . ':' . str_pad($attendance->total_work_time % 60, 2, '0', STR_PAD_LEFT) : '' }}</td>
                <td class="list-data list-data__detail">
                    @if ($attendance)
                    <a href="{{ route('attendance.show', ['id' => $attendance->id]) }}" class="list-data__detail-link">詳細</a>
                    @else
                    <a href="{{ route('attendance.show', ['id' => 'new', 'date' => $dateStr]) }}" class="list-data__detail-link">詳細</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>