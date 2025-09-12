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
            'clock_in' => [
                'required',
                'date_format:H:i',
            ],
            'clock_out' => [
                'required',
                'date_format:H:i',
                'after:clock_in',
            ],

            'breaks.*.start' => [
                'nullable',
                'date_format:H:i',
                'after_or_equal:clock_in',
                'before_or_equal:clock_out',
            ],

            'breaks.*.end' => [
                'nullable',
                'date_format:H:i',
                'before_or_equal:clock_out',
            ],

            'reason' => [
                'required',
                'string',
                'max:255',
            ],
        ];


    }

    public function messages()
    {
        return [
            'clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'breaks.*.start.after_or_equal' => '休憩時間が不適切な値です',
            'breaks.*.start.before_or_equal' => '休憩時間が不適切な値です',
            'breaks.*.end.before_or_equal' => '休憩時間もしくは退勤時間が不適切な値です',
            'reason.required' => '備考を記入してください',
        ];
    }
}
