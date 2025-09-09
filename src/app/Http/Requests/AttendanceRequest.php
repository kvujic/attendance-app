<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

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
            'requested_clock_out' => ['bail', 'required', 'date_format:H:i'],

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
                    ];
                $rules["breaks.$index.requested_break_end"] = [
                    'bail',
                    'nullable',
                    'date_format:H:i',
                    'required_with:breaks.' . $index . '.requested_break_start',
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
        $hm = fn($v) => is_string($v) && preg_match('/^\d{2}:\d{2}$/', $v) === 1;

        $validator->after(function ($v) {
            $errors = $v->errors();
            $fmtMsg = '休憩時間は「HH:MM」の形式で入力してください';

            foreach ($this->input('breaks', []) as $i => $_) {
                $s = "breaks.$i.requested_break_start";
                $e = "breaks.$i.requested_break_end";

                $startMsgs = $errors->get($s);
                $endMsgs   = $errors->get($e);

                $hasFmt = (in_array($fmtMsg, $startMsgs ?? [], true)
                    || in_array($fmtMsg, $endMsgs ?? [], true));

                if ($hasFmt) {
                    if ($startMsgs) {
                        $errors->forget($s);
                        foreach ($startMsgs as $m) if ($m !== $fmtMsg) $errors->add($s, $m);
                    }
                    if ($endMsgs) {
                        $errors->forget($e);
                        foreach ($endMsgs as $m) if ($m !== $fmtMsg) $errors->add($e, $m);
                    }
                    $errors->add("breaks.$i", $fmtMsg);
                }
            }
        });

        $validator->sometimes(
            'requested_clock_out',
            'after:requested_clock_in',
            function ($input) use ($hm) {
                return $hm($input->requested_clock_in ?? null)
                    && $hm($input->requested_clock_out ?? null);
            }
        );

        foreach (($this->input('breaks', []) ?: []) as $i => $b) {
            $sKey = "breaks.$i.requested_break_start";
            $eKey = "breaks.$i.requested_break_end";

            $validator->sometimes(
                $eKey,
                "after:$sKey",
                function ($input) use ($hm, $sKey, $eKey) {
                    $s = data_get($input, $sKey);
                    $e = data_get($input, $eKey);
                    return $hm($s) && $hm($e);
                }
            );

            $validator->sometimes(
                $sKey,
                ['after_or_equal:requested_clock_in', 'before:requested_clock_out'],
                function ($input) use ($hm, $sKey) {
                    $s = data_get($input, $sKey);
                    return $hm($s)
                        && $hm($input->requested_clock_in ?? null)
                        && $hm($input->requested_clock_out ?? null);
                }
            );

            $validator->sometimes(
                $eKey,
                ['before_or_equal:requested_clock_out'],
                function ($input) use ($hm, $eKey) {
                    $e = data_get($input, $eKey);
                    return $hm($e)
                        && $hm($input->requested_clock_out ?? null);
                }
            );
        }
    }

    protected function getValidatorInstance()
    {
        $validator = parent::getValidatorInstance();

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

        if ($targetDate) {
            $day = Carbon::parse($targetDate)->startOfDay();
            $now = now();

            if ($day->isFuture()) {
                throw ValidationException::withMessages([
                    'date' => '未来日の勤怠は修正できません'
                ]);
            }

            if ($day->isSameDay(now())) {
                $now = now();

                $isHi = fn($s) => is_string($s) && preg_match('/^\d{2}:\d{2}$/', $s) === 1;

                $mk = fn($hm) => $isHi($hm) ? Carbon::createFromFormat('Y-m-d H:i', "{$targetDate} {$hm}") : null;

                $in  = $mk($this->input('requested_clock_in'));
                $out = $mk($this->input('requested_clock_out'));

                $hasFuture = false;
                if ($in && $in->gt($now))  $hasFuture = true;
                if ($out && $out->gt($now)) $hasFuture = true;

                foreach ($this->input('breaks', []) as $b) {
                    $s = !empty($b['requested_break_start']) ? $mk($b['requested_break_start']) : null;
                    $e = !empty($b['requested_break_end'])   ? $mk($b['requested_break_end'])   : null;
                    if (($s && $s->gt($now)) || ($e && $e->gt($now))) {
                        $hasFuture = true;
                        break;
                    }
                }

                if ($hasFuture) {
                    throw ValidationException::withMessages([
                        'requested_clock_in' => '未来時刻は指定できません'
                    ]);
                }
            }
        }
        return $validator;
    }
}
