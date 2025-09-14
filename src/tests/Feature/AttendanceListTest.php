<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use App\Models\AttendanceApplication;

class AttendanceListTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;

    /** @test */
    public function 自分の勤怠情報が一覧にすべて表示されている()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $startDate = Carbon::today();

        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->copy()->addDays($i);
            Attendance::factory()->create([
                'user_id' => $user->id,
                'date' => $date->toDateString(),
                'clock_in' => $date->copy()->setTime(9, 0),
                'clock_out' => $date->copy()->setTime(18, 0),
            ]);
        }

        $response = $this->actingAs($user)->get('/attendance/list');

        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->copy()->addDays($i);
            $clockIn = $date->copy()->setTime(9, 0)->format('H:i');

            $response->assertSee($clockIn);
        }
    }

    /** @test */
    public function 勤怠一覧画面に遷移した際に現在の月が表示される()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $currentMonth = Carbon::today()->format('Y/m');

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertSee($currentMonth);
    }

    /** @test */
    public function 「前月」を押下した時に表示月の前月の情報が表示される()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $previousMonth = Carbon::today()->subMonth();
        $date = $previousMonth->copy()->startOfMonth();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $date->toDateString(),
            'clock_in' => $date->copy()->setTime(9, 0),
            'clock_out' => $date->copy()->setTime(18, 0),
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=' . $previousMonth->format('Y-m'));

        $formattedMonth = $previousMonth->format('Y/m');
        $response->assertSee($formattedMonth);

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /** @test */
    public function 「翌月」を押下した時に表示月の前月の情報が表示される()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $previousMonth = Carbon::today()->addMonth();
        $date = $previousMonth->copy()->startOfMonth();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $date->toDateString(),
            'clock_in' => $date->copy()->setTime(9, 0),
            'clock_out' => $date->copy()->setTime(18, 0),
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=' . $previousMonth->format('Y-m'));

        $formattedMonth = $previousMonth->format('Y/m');
        $response->assertSee($formattedMonth);

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /** @test */
    public function 「詳細」を押下すると、その日の勤怠詳細画面に遷移する()
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

    /** @test */
    public function 承認済みタブに承認済みの申請が表示される()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);


        $application = AttendanceApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'reason' => '打刻漏れ',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('applications.list.approved'));

        $response->assertStatus(200);

        $response->assertSee('承認済み');
        $response->assertSee($application->reason);
        $response->assertSee($application->date->format('Y/m/d'));
    }
}
