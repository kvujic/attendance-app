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

        $validator->sometimes(
            'requested_clock_out',
            'after:requested_clock_in',
            fn($input) => $hm($input->requested_clock_in ?? null) && $hm($input->requested_clock_out ?? null)
        );

        foreach (($this->input('breaks', []) ?: []) as $i => $row) {
            $sKey = "breaks.$i.requested_break_start";
            $eKey = "breaks.$i.requested_break_end";

            $validator->sometimes(
                $eKey,
                "after:$sKey",
                fn($input) => $hm(data_get($input, $sKey)) && $hm(data_get($input, $eKey))
            );

            $validator->sometimes(
                $sKey,
                ['after_or_equal:requested_clock_in', 'before:requested_clock_out'],
                fn($input) => $hm(data_get($input, $sKey))
                    && $hm($input->requested_clock_in ?? null)
                    && $hm($input->requested_clock_out ?? null)
            );

            $validator->sometimes(
                $eKey,
                ['before_or_equal:requested_clock_out'],
                fn($input) => $hm(data_get($input, $eKey))
                    && $hm($input->requested_clock_out ?? null)
            );
        }

        $validator->after(function ($v) {
            $errors = $v->errors();
            $failed = $v->failed();

            $addOnce = function (string $key, string $msg) use ($errors) {
                $list = $errors->get($key) ?? [];
                if (!in_array($msg, $list, true)) $errors->add($key, $msg);
            };
            $replaceOrAdd = function (string $key, string $new) use ($errors) {
                if (!$errors->has($key)) {
                    $errors->add($key, $new);
                    return;
                }
                $old = $errors->get($key);
                $errors->forget($key);
                $seen = false;
                $errors->add($key, $new);
                foreach ($old as $m) {
                    if ($m === $new) {
                        if ($seen) continue;
                        $seen = true;
                        continue;
                    }
                    $errors->add($key, $m);
                }
            };
            $forgetAllErrors = function () use ($errors) {
                foreach (array_keys($errors->toArray()) as $k) $errors->forget($k);
            };

            $isHi = fn($s) => is_string($s) && preg_match('/^\d{2}:\d{2}$/', $s) === 1;

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

                $hasOtherErrors = !empty($errors->toArray());

                if ($day->isFuture()) {
                    if (!$hasOtherErrors) {
                        foreach (array_keys($errors->toArray()) as $k) $errors->forget($k);
                        $errors->add('date', '未来日の勤怠は修正できません');
                        return;
                    }
                } elseif ($day->isSameDay(now())) {
                    $mk = fn($hm) => $isHi($hm) ? Carbon::createFromFormat('Y-m-d H:i', "{$targetDate} {$hm}") : null;

                    $now = now();
                    $in  = $mk($this->input('requested_clock_in'));
                    $out = $mk($this->input('requested_clock_out'));

                    $hasFutureTime = false;
                    if ($in && $in->gt($now))  $hasFutureTime = true;
                    if ($out && $out->gt($now)) $hasFutureTime = true;

                    foreach ($this->input('breaks', []) as $b) {
                        $s = !empty($b['requested_break_start']) ? $mk($b['requested_break_start']) : null;
                        $e = !empty($b['requested_break_end'])   ? $mk($b['requested_break_end'])   : null;
                        if (($s && $s->gt($now)) || ($e && $e->gt($now))) {
                            $hasFutureTime = true;
                            break;
                        }
                    }

                    if ($hasFutureTime) {
                        if (!$hasOtherErrors) {
                            foreach (array_keys($errors->toArray()) as $k) $errors->forget($k);
                            $errors->add('requested_clock_in', '未来時刻は指定できません');
                            return;
                        }
                    }
                }
            }

            if ($errors->has('requested_clock_out')) {
                if (!isset($failed['requested_clock_out']['Required'])) {
                    $replaceOrAdd('requested_clock_out', '出勤時間もしくは退勤時間が不適切な値です');
                }
            }

            foreach ($this->input('breaks', []) as $i => $row) {
                $sKey = "breaks.$i.requested_break_start";
                $eKey = "breaks.$i.requested_break_end";
                $sRaw = trim((string)($row['requested_break_start'] ?? ''));
                $eRaw = trim((string)($row['requested_break_end'] ?? ''));

                if (isset($failed[$sKey]['RequiredWith'])) $replaceOrAdd($sKey, '休憩時間が不適切な値です');
                if (isset($failed[$eKey]['RequiredWith'])) $replaceOrAdd($eKey, '休憩時間が不適切な値です');

                if ($eRaw !== '' && $sRaw === '') $addOnce($sKey, '休憩時間が不適切な値です');
                if ($sRaw !== '' && $eRaw === '') $addOnce($eKey, '休憩時間が不適切な値です');
            }

            foreach ($this->input('breaks', []) as $i => $row) {
                $eKey = "breaks.$i.requested_break_end";
                if (isset($failed[$eKey]['After'])) {
                    $replaceOrAdd($eKey, '休憩時間が不適切な値です');
                }
            }

            $fmt = '休憩時間は「HH:MM」の形式で入力してください';
            foreach ($this->input('breaks', []) as $i => $_) {
                $sKey = "breaks.$i.requested_break_start";
                $eKey = "breaks.$i.requested_break_end";
                $sHas = $errors->has($sKey) && in_array($fmt, $errors->get($sKey), true);
                $eHas = $errors->has($eKey) && in_array($fmt, $errors->get($eKey), true);
                if ($sHas && $eHas) {
                    $keep = array_values(array_filter($errors->get($eKey), fn($m) => $m !== $fmt));
                    $errors->forget($eKey);
                    foreach ($keep as $m) $errors->add($eKey, $m);
                }
            }
        });
    }
}
