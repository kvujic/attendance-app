<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        $filtered = collect($this->input('breaks', []))
            ->filter(fn($b) => !empty($b['requested_break_start']) || !empty($b['requested_break_end']))
            ->values()
            ->toArray();

        $this->merge([
            'breaks' => $filtered
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'requested_clock_in' => ['bail', 'required', 'date_format:H:i'],
            'requested_clock_out' => ['bail', 'required', 'date_format:H:i', 'after:requested_clock_in'],

            'request_note' => ['required', 'string'],
            'breaks' => ['array'],
        ];

        $breaks = $this->input('breaks', []);

        foreach ($breaks as $index => $break) {
            $rules["breaks.$index"] = ['array'];

                $rules["breaks.$index.requested_break_start"] = [
                    'bail',
                    'nullable',
                    'date_format:H:i',
                    'required_with:breaks.' . $index . '.requested_break_end',
                    'after_or_equal:requested_clock_in',
                    'before:requested_clock_out',
                    ];
                $rules["breaks.$index.requested_break_end"] = [
                    'bail',
                    'nullable',
                    'date_format:H:i',
                    'required_with:breaks.' . $index . '.requested_break_start',
                    'after:breaks.' . $index . '.requested_break_start',
                    'before_or_equal:requested_clock_out',
                ];
        }
        return $rules;
    }

    public function messages(): array
    {
        return [
            'requested_clock_in.required' => '出勤時間を入力してください',
            'requested_clock_in.date_format' => '出勤時間は「HH:MM」の形式で入力してください',

            'requested_clock_out.required' => '退勤時間を入力してください',
            'requested_clock_out.date_format' => '退勤時間は「HH:MM」の形式で入力してください',
            'requested_clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',

            'breaks.*.requested_break_start.date_format' => '休憩時間は「HH:MM」の形式で入力してください',
            'breaks.*.requested_break_start.required_with' => '休憩開始時間を入力してください',
            'breaks.*.requested_break_start.after_or_equal' => '休憩時間が不適切な値です',
            'breaks.*.requested_break_start.before' => '休憩時間が不適切な値です',

            'breaks.*.requested_break_end.date_format' => '休憩時間は「HH:MM」の形式で入力してください',
            'breaks.*.requested_break_end.required_with' => '休憩終了時間を入力してください',
            'breaks.*.requested_break_end.after' => '休憩時間が不適切な値です',
            'breaks.*.requested_break_end.before_or_equal' => '休憩時間もしくは退勤時間が不適切な値です',

            'request_note.required' => '備考を記入してください',
            'request_note.string' => '備考は文字列で入力してください',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {

            $routeId = $this->route('id');
            $targetDate = null;

            if ($routeId !== 'new') {
                $att = Attendance::find($routeId);
                if ($att && $att->date) {
                    $targetDate = Carbon::parse($att->date)->toDateString();
                }
            } else {
                $d = $this->input('date');
                if ($d) {
                    $targetDate = Carbon::parse($d)->toDateString();
                }
            }

            if (!$targetDate) {
                return;
            }

            $day = Carbon::parse($targetDate)->startOfDay();

            if ($day->isFuture()) {
                $v->errors()->add('date', '未来日の勤怠は修正できません');
                return;
            }

            if ($day->isSameDay(now())) {
                $now = now();
                $mk = fn($hm) => $hm ? Carbon::createFromFormat('Y-m-d H:i', "{$targetDate} {$hm}") : null;

                $in  = $mk($this->input('requested_clock_in'));
                $out = $mk($this->input('requested_clock_out'));

                $futureError = false;

                if ($in && $in->gt($now))  $futureError = true;
                if ($out && $out->gt($now)) $futureError = true;

                foreach ($this->input('breaks', []) as $i => $b) {
                    $s = !empty($b['requested_break_start']) ? $mk($b['requested_break_start']) : null;
                    $e = !empty($b['requested_break_end'])   ? $mk($b['requested_break_end'])   : null;
                    if (($s && $s->gt($now)) || ($e && $e->gt($now))) {
                        $futureError = true;
                    }
                }

                if ($futureError) {
                    $v->errors()->add('requested_clock_in', '未来時刻は指定できません');
                }
            }
        });
    }
}
