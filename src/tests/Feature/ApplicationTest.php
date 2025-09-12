<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceApplication;

class ApplicationTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;

    /** @test */
    public function 「承認待ち」にログインユーザーが行った申請が全て表示されていること()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('applications.submit', $attendance->id), [
            'date' => $attendance->date->toDateString(),
            'clock_in' => '08:30',
            'clock_out' => '18:00',
            'reason' => '打刻漏れ',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('attendance_applications', [
            'user_id' => $user->id,
            'status' => 'pending',
            'reason' => '打刻漏れ',
        ]);

        $response = $this->get(route('applications.list'));

        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $response->assertSee('打刻漏れ');
        $response->assertSee($user->name);
    }

    /** @test */
    public function 「承認済み」に管理者が承認した修正申請が全て表示されている()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $this->actingAs($user);

        $this->post(route('applications.submit', $attendance->id), [
            'date' => $attendance->date->toDateString(),
            'clock_in' => '08:30',
            'clock_out' => '18:00',
            'reason' => '打刻漏れ',
        ]);

        $application = AttendanceApplication::first();
        $this->assertEquals('pending', $application->status);

        $application->update(['status' => 'approved']);

        $response = $this->get(route('applications.list.approved'));

        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee('打刻漏れ');
        $response->assertSee($user->name);
    }

    /** @test */
    public function 各申請の「詳細」を押下すると勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $this->actingAs($user);

        $this->post(route('applications.submit', $attendance->id), [
            'date' => $attendance->date->toDateString(),
            'clock_in' => '08:30',
            'clock_out' => '18:00',
            'reason' => '打刻漏れ',
        ]);

        $application = AttendanceApplication::first();

        $response = $this->get(route('applications.list'));
        $response->assertStatus(200);
        $response->assertSee('詳細');

        $detailResponse = $this->get(route('attendance.application', $application->id));

        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('勤怠詳細');
        $detailResponse->assertSee($attendance->date->format('Y年'));
        $detailResponse->assertSee($attendance->date->format('n月j日'));
    }
}
