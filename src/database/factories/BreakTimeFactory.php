<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use App\Models\Attendance;
use App\Models\BreakTime;

class BreakTimeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = BreakTime::class;

    public function definition()
    {
        return [
            'Attendance_id' => Attendance::factory(),
            'start_time' => Carbon::now()->subMinutes(30),
            'end_time' => Carbon::now()->subMinutes(15),
        ];
    }
}
