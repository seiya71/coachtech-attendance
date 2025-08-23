<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
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

    /** @test */
    public function メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する()
    {
        config()->set('webmail.override', 'https://mailtrap.io/');

        $user = \App\Models\User::factory()->unverified()->create([
            'email' => 'foo@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('https://mailtrap.io/');
    }

    /** @test */
    public function メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する()
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(Config::get('auth.verification.expire', 60)),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($url);

        $response->assertRedirect(route('home'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
