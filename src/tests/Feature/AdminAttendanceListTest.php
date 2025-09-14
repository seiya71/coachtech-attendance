<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AdminAttendanceListTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;

    /** @test */
    public function 管理者はその日の全ユーザーの勤怠一覧を確認できる()
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $user1 = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $user2 = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $today = Carbon::today();

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user1->id,
            'date' => $today,
            'clock_in' => $today->copy()->setTime(9, 0),
            'clock_out' => $today->copy()->setTime(18, 0),
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance1->id,
            'start_time' => $today->copy()->setTime(12, 0),
            'end_time' => $today->copy()->setTime(13, 0),
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
            'date' => $today,
            'clock_in' => $today->copy()->setTime(10, 0),
            'clock_out' => $today->copy()->setTime(19, 0),
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance2->id,
            'start_time' => $today->copy()->setTime(15, 0),
            'end_time' => $today->copy()->setTime(15, 30),
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.attendance_list', [
            'date' => $today->toDateString(),
        ]));

        $response->assertStatus(200);

        $response->assertSee($user1->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('01:00');
        $response->assertSee('08:00');


        $response->assertSee($user2->name);
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('00:30');
        $response->assertSee('08:30');
    }
}
