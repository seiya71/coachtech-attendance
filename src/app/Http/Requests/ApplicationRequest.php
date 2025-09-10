<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */

    
    public function rules()
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i'],

            'reason' => ['required', 'string', 'max:255'],

            'breaks' => ['nullable', 'array'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $in = $this->input('clock_in');
            $out = $this->input('clock_out');
            $date = $this->input('date');
            $breaks = $this->input('breaks', []);

            if ($in && $out && $in >= $out) {
                $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
            }

            foreach ($breaks as $breakIndex => $break) {
                $start = $break['start'] ?? null;
                $end = $break['end'] ?? null;

                if ($start && $end) {
                    if ($start >= $end) {
                        $validator->errors()->add("breaks.$breakIndex.start", '休憩時間が不適切な値です');
                    }

                    if ($end >= $out) {
                        $validator->errors()->add("breaks.$breakIndex.end", '休憩時間もしくは退勤時間が不適切な値です');
                    }
                }
            }
        });
    }


    public function messages()
    {
        return [
            'clock_in.required' => '出勤時刻は必須です。',
            'clock_out.required' => '退勤時刻は必須です。',
            'clock_in.date_format' => '出勤時刻の形式が不正です。',
            'clock_out.date_format' => '退勤時刻の形式が不正です。',
            'breaks.*.start.date_format' => '休憩開始は HH:MM 形式で入力してください。',
            'breaks.*.end.date_format' => '休憩終了は HH:MM 形式で入力してください。',
            'reason.required' => '備考を記入してください',
        ];
    }
}
