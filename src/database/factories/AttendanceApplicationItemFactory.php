<?php

namespace Database\Factories;

use App\Models\BreakTime;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use App\Models\AttendanceApplicationItem;
use App\Models\AttendanceApplication;

class AttendanceApplicationItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = AttendanceApplicationItem::class;
    public function definition()
    {
        $start = $this->faker->time('H:i');
        $end = Carbon::createFromFormat('H:i', $start)->addMinutes(rand(15, 90))->format('H:i');
        return [
            'attendance_application_id' => AttendanceApplication::factory(),
            'break_time_id' => BreakTime::factory(),
            'start' => $start,
            'end' => $end,
        ];
    }
}