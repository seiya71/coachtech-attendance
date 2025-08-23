<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;

    /** @test */
    public function 会員登録すると認証メールが送信される()
    {
        Notification::fake();

        $payload = [
            'name' => 'テストユーザー',
            'email' => 'verify_target@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $res = $this->post(route('register.perform'), $payload);

        $res->assertRedirect(route('verification.notice'));

        $user = User::whereEmail('verify_target@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
