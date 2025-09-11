<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceDetailTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    /** @test */
    public function 勤怠詳細画面の「名前」がログインユーザーの氏名になっている()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'name' => 'テスト太郎',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now('Asia/Tokyo')->toDateString(),
            'clock_in' => now('Asia/Tokyo')->subHours(4),
            'clock_out' => now('Asia/Tokyo'),
        ]);

        $this->actingAs($user)->get('/attendance/list');

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('テスト太郎');
    }

    /** @test */
    public function 勤怠詳細画面の「日付」が選択した日付になっている()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $date = now('Asia/Tokyo')->toDateString();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $date,
            'clock_in' => now('Asia/Tokyo')->subHours(4),
            'clock_out' => now('Asia/Tokyo'),
        ]);

        $this->actingAs($user)->get('/attendance/list');

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee($date);
    }

    /** @test */
    public function 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $start = now('Asia/Tokyo')->subHours(4);

        $end = now('Asia/Tokyo');

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now('Asia/Tokyo')->toDateString(),
            'clock_in' => $start,
            'clock_out' => $end,
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee($start->format('H:i'));
        $response->assertSee($end->format('H:i'));
    }

    /** @test */
    public function 「休憩」にて記されている時間がログインユーザーの打刻と一致している()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $start = now('Asia/Tokyo')->subHours(2);

        $end = now('Asia/Tokyo')->subHours(1);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now('Asia/Tokyo')->toDateString(),
            'clock_in' => now('Asia/Tokyo')->subHours(4),
            'clock_out' => now('Asia/Tokyo'),
        ]);

        $break = BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time' => $start,
            'end_time' => $end,
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee($start->format('H:i'));
        $response->assertSee($end->format('H:i'));
    }

    /** @test */
    public function 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => today()->toDateString(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $this->actingAs($user);

        $response = $this->post('/attendance/application', [
            'date' => now()->toDateString(),
            'clock_in' => '18:00',
            'clock_out' => '09:00',
            'reason' => 'テスト申請',
        ]);

        $response->assertSessionHasErrors([
            'clock_out' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }


    /** @test */
    public function 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $response = $this->post("/attendance/application", [
            'date' => $attendance->date,
            'clock_in' => $attendance->clock_in->format('H:i'),
            'clock_out' => $attendance->clock_out->format('H:i'),
            'breaks' => [
                [
                    'start' => '18:30',
                    'end' => '19:00',
                ]
            ],
            'reason' => 'テスト休憩申請',
        ]);


        $response->assertSessionHasErrors(['breaks.0.start']);

        $errorMessage = session('errors')->first('breaks.0.start');
        $this->assertSame('休憩時間が不適切な値です', $errorMessage);
    }

    /** @test */
    public function 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $response = $this->post("/attendance/application", [
            'date' => $attendance->date,
            'clock_in' => $attendance->clock_in->format('H:i'),
            'clock_out' => $attendance->clock_out->format('H:i'),
            'breaks' => [
                [
                    'start' => '17:30',
                    'end' => '18:30',
                ]
            ],
            'reason' => 'テスト休憩終了申請',
        ]);

        $response->assertSessionHasErrors(['breaks.0.end']);

        $errorMessage = session('errors')->first('breaks.0.end');
        $this->assertSame('休憩時間もしくは退勤時間が不適切な値です', $errorMessage);
    }

}
