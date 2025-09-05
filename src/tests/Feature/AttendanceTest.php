<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;

class AttendanceTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;

    /** @test */
    public function 現在の日時情報がUIと同じ形式で出力されている()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $now = now('Asia/Tokyo');
        $expectedDate = $now->format('Y年n月j日');
        $weekday = ['日', '月', '火', '水', '木', '金', '土'][$now->dayOfWeek];
        $expectedDateFormatted = $expectedDate . "($weekday)";
        $expectedTime = $now->format('H:i');

        $response = $this->actingAs($user)
            ->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSeeText($expectedDateFormatted);
        $response->assertSeeText($expectedTime);
    }

    /** @test */
    public function 勤務外の場合、勤怠ステータスが正しく表示される(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.index'));

        $response->assertStatus(200)
            ->assertSee('勤務外');
    }

    /** @test */
    public function 出勤中の場合、勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now('Asia/Tokyo')->toDateString(),
            'clock_in' => now('Asia/Tokyo')->subHours(2),
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertSee('勤務中');
    }

    /** @test */
    public function 休憩中の場合、勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->subHours(2),
            'clock_out' => null,
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time' => now()->subMinutes(30),
            'end_time' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertSee('休憩中');
    }

    /** @test */
    public function 退勤済の場合、勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->subHours(2),
            'clock_out' => now(),
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time' => now()->subMinutes(30),
            'end_time' => now()->subMinutes(15),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertSee('退勤済');
    }

    /** @test */
    public function 出勤ボタンが正しく機能する()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertSee('出勤');

        $this->actingAs($user)->post(route('me.attendance.clock_in'));

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertSee('勤務中');
    }

    /** @test */
    public function 出勤は一日一回のみできる()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertDontSee('出勤');
    }

    /** @test */
    public function 休憩ボタンが正しく機能する()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now('Asia/Tokyo')->toDateString(),
            'clock_in' => now('Asia/Tokyo')->subHours(2),
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertSee('休憩入');

        $this->actingAs($user)->post(route('me.attendance.break_in'));

        $responseAfterBreakIn = $this->actingAs($user)->get(route('attendance.index'));

        $responseAfterBreakIn->assertSee('休憩中');
    }

}
