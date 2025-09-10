<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use App\Models\Attendance;
use App\Models\AttendanceApplication;

class AttendanceApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = AttendanceApplication::class;
    public function definition()
    {
        $clock_in = $this->faker->time('H:i');
        $clock_out = Carbon::createFromFormat('H:i', $clock_in)->addMinutes(rand(15, 90))->format('H:i');
        return [
            'user_id' => Attendance::factory(),
            'attendance_id' => Attendance::factory(),
            'status' => 'pending',
            'reason' => $this->faker->text(50),
            'date' => $this->faker->date($format = 'Y-m-d'),
            'clock_in' => $clock_in,
            'clock_out' => $clock_out,
        ];
    }
}