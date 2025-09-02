<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

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
}
