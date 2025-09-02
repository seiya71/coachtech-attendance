<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use app\Models\Attendance;
use app\Models\User;

class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = Attendance::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'date' => now('Asia/Tokyo')->toDateString(),
            'clock_in' => now('Asia/Tokyo')->subHours(1),
            'clock_out' => now('Asia/Tokyo'),
        ];
    }
}
