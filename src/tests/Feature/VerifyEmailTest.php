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
        Notification::fake(); // 実送信せず検証だけ

        $payload = [
            'name' => 'テストユーザー',
            'email' => 'verify_target@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // あなたの登録ルート名/URLに合わせて
        $res = $this->post(route('register.perform'), $payload);

        // 誘導画面へ（任意で確認）
        $res->assertRedirect(route('verification.notice'));

        // 登録ユーザー取得
        $user = User::whereEmail('verify_target@example.com')->firstOrFail();

        // 認証メール（VerifyEmail）が送られていること
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
