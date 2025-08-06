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
            ->filter(fn($b) => !empty($b['start']) || !empty($b['end']))
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
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i', 'after:clock_in'],

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
        ];

        $breaks = $this->input('breaks', []);

        foreach ($breaks as $index => $break) {
            //$start = $break['start'] ?? null;
            //$end = $break['end'] ?? null;

            // どちらか一方でも入力がある場合だけバリデーションする
            //if ($start || $end) {
                $rules["breaks.$index.start"] = [
                    'nullable',
                    'date_format:H:i',
                    'required_with:breaks.' . $index . 'end',
                    'before:breaks.' . $index . '.end',
                    'after_or_equal:clock_in',
                    'before:clock_out',
                    ];
                $rules["breaks.$index.end"] = [
                    'nullable',
                    'date_format:H:i',
                    'required_with:breaks.' . $index . 'start',
                    'after:breaks.' . $index . '.start',
                    'before_or_equal:clock_out',
                ];
            //}
        }
        return $rules;
    }

    public function messages(): array
    {
        return [
            'clock_in.required' => '出勤時間を入力してください',
            'clock_in.date_format' => '出勤時間は「HH:MM」の形式で入力してください',

            'clock_out.required' => '退勤時間を入力してください',
            'clock_out.date_format' => '退勤時間は「HH:MM」の形式で入力してください',
            'clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',

            'breaks.*.start.date_format' => '休憩時間は「HH:MM」の形式で入力してください',
            'breaks.*.start.required_with' => '休憩終了時間を入力してください',
            'breaks.*.start.after_or_equal' => '休憩時間が不適切な値です',
            'breaks.*.start.before' => '休憩時間もしくは退勤時間が不適切な値です',

            'breaks.*.end.date_format' => '休憩時間は「HH:MM」の形式で入力してください',
            'breaks.*.end.required_with' => '休憩開始時間を入力してください',
            'breaks.*.end.after' => '休憩時間が不適切な値です',
            'breaks.*.end.before_or_equal' => '休憩時間もしくは退勤時間が不適切な値です',

            'note.required' => '備考を入力してください',
            'note.string' => '備考は文字列で入力してください',
        ];
    }
}
