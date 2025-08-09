<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use function PHPSTORM_META\map;

class AttendanceRequest extends FormRequest
{
    //filtering
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
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'requested_clock_in' => ['bail', 'required', 'date_format:H:i'],
            'requested_clock_out' => ['bail', 'required', 'date_format:H:i', 'after:requested_clock_in'],

            /*
            'breaks.*.start' => [
                'nullable',
                'date_format:H:i',
                'required_with:breaks.*.end',
                'after_or_equal:clock_in',
                'before:clock_out',
            ],
            'breaks.*.end' => [
                'nullable',
                'date_format:H:i',
                'required_with:breaks.*.start',
                'after:breaks.*.start',
                'before_or_equal:clock_out'
            ],
            */

            'note' => ['required', 'string'],
            'breaks' => ['array'],
        ];

        $breaks = $this->input('breaks', []);

        foreach ($breaks as $index => $break) {
            $rules["breaks.$index"] = ['array'];
            //$start = $break['start'] ?? null;
            //$end = $break['end'] ?? null;

            // どちらか一方でも入力がある場合だけバリデーションする
            //if ($start || $end) {
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
            //}
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
            'breaks.*.requested_break_start.required_with' => '休憩終了時間を入力してください',
            'breaks.*.requested_break_start.after_or_equal' => '休憩時間が不適切な値です',
            'breaks.*.requested_break_start.before' => '休憩時間が不適切な値です',

            'breaks.*.requested_break_end.date_format' => '休憩時間は「HH:MM」の形式で入力してください',
            'breaks.*.requested_break_end.required_with' => '休憩開始時間を入力してください',
            'breaks.*.requested_break_end.after' => '休憩時間が不適切な値です',
            'breaks.*.requested_break_end.before_or_equal' => '休憩時間もしくは退勤時間が不適切な値です',

            'note.required' => '備考を入力してください',
            'note.string' => '備考は文字列で入力してください',
        ];
    }
}
