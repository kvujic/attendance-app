<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;

class AttendanceList extends Component
{
    public $daysInMonth = [];
    public $currentMonth;
    public $attendances = [];

    public function mount($month = null)
    {
        $this->currentMonth = Carbon::now()->startOfMonth();
        $this->loadAttendances();
    }

    public function previousMonth()
    {
        $this->currentMonth = $this->currentMonth->copy()->subMonth();
        $this->loadAttendances();
    }

    public function nextMonth()
    {
        $this->currentMonth = $this->currentMonth->copy()->addMonth();
        $this->loadAttendances();
    }

    public function loadAttendances()
    {
        $user = Auth::user();

        $startOfMonth = $this->currentMonth->copy()->startOfMonth();
        $endOfMonth = $this->currentMonth->copy()->endOfMonth();

        $this->daysInMonth = [];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $this->daysInMonth[] = $date->copy();
        }

        $this->attendances = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereYear('date', $this->currentMonth->year)
            ->whereMonth('date', $this->currentMonth->month)
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function ($item) {
                return [Carbon::parse($item->date)->toDateString() => $item];
            });
    }


    public function render()
    {
        return view('livewire.attendance-list');
    }
}
