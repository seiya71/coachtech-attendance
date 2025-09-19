<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Features;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::registerView(function () {
            return view('auth.register');
        });

        Fortify::loginView(function () {
            return view('auth.login');
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        config([
            'fortify.features' => array_unique(array_filter([
                Features::emailVerification(),
            ]))
        ]);
        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject('【' . config('app.name') . '】ユーザー登録を完了してください')
                ->greeting($notifiable->name . '様')
                ->line('勤怠管理アプリへのご登録、誠にありがとうございます。')
                ->line('下記のボタンをクリックし、メールアドレスの認証をお願いいたします。')
                ->action('メールアドレスを認証する', $url)
                ->line('この認証が完了すると、システムの全機能をご利用いただけます。')
                ->line('※リンクの有効期限は60分間です。')
                ->line('※本メールにお心当たりがない場合は、破棄していただければ結構です。');
        });
    }
}
