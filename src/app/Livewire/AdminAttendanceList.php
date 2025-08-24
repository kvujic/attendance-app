<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\Attendance;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;


class AdminAttendanceList extends Component
{
    public Carbon $currentDate; //Carbon
    public $attendances; //correction

    public function mount(?string $date = null): void
    {
        //abort_unless(Gate::allows('admin'), 403);
        $admin = Auth::guard('admin')->user();
        abort_unless($admin && ($admin->role === 1), 403);

        $this->currentDate = $date
            ? Carbon::parse($date)->startOfDay()
            : now()->startOfDay();

        $this->loadAttendances();
    }

    public function previousDay(): void
    {
        $this->currentDate = $this->currentDate->copy()->subDay();
        $this->loadAttendances();
    }

    public function nextDay():void
    {
        $this->currentDate = $this->currentDate->copy()->addDay();
        $this->loadAttendances();
    }

    public function loadAttendances(): void
    {
        $this->attendances = Attendance::with('user')
            ->whereDate('clock_in', $this->currentDate)
            ->orderBy('user_id')
            ->get();

    }


    public function render()
    {
        return view('livewire.admin-attendance-list');
    }
}
