<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUserMonthlyAttendance extends Component
{
    public User $user; // eligible staff
    public Carbon $currentMonth;
    public Collection $daysInMonth; // list of dates fot the month
    public array $attendances = [];

    // initialization
    public function mount(User $user, ?string $month = null): void
    {
        $this->user = $user;
        if ($month) {
            $month = str_replace('/', '-', $month);
            $this->currentMonth = Carbon::parse($month . '-01')->startOfMonth();
        } else {
            $this->currentMonth = now()->startOfMonth();
        }

        $this->loadMonth();
    }

    public function previousMonth(): void
    {
        $this->currentMonth = $this->currentMonth->copy()->subMonth();
        $this->loadMonth();
    }

    public function nextMonth(): void
    {
        $candidate = $this->currentMonth->copy()->addMonth()->startOfMonth();

        if ($candidate->greaterThan(now()->startOfMonth())) {
            return;
        }

        $this->currentMonth = $candidate;
        //$this->currentMonth = $this->currentMonth->copy()->addMonth();
        $this->loadMonth();
    }

    public function switchToMonth(string $ym): void
    {
        $target = \Carbon\Carbon::parse($ym . '-01')->startOfMonth();
        if ($target->greaterThan(now()->startOfMonth())) {
            return;
        }
        $this->currentMonth = $target;
        $this->loadMonth();
    }

    public function loadMonth(): void
    {
        // whole dates of the month
        $start = $this->currentMonth->copy()->startOfMonth();
        $end = $this->currentMonth->copy()->endOfMonth();

        $this->daysInMonth = collect();
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $this->daysInMonth->push($d->copy());
        }

        // get attendance and put 'Y-m-d' as key
        $records = Attendance::where('user_id', $this->user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $this->attendances = [];
        foreach ($records as $a) {
            $key = Carbon::parse($a->date)->toDateString();
            $this->attendances[$key] = $a;
        }
    }

    public function render()
    {
        return view('livewire.admin-user-monthly-attendance');
    }
}
