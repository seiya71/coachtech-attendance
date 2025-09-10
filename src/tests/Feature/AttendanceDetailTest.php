<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

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
        $response->assertSee('テスト太郎');
    }
}
