<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceService
{
    public static function todayJst(): Carbon
    {
        return now('Asia/Tokyo');
    }

    public static function shiftDay(Carbon $base, string $direction): Carbon
    {
        return match ($direction) {
            'prev' => $base->copy()->subDay(),
            'next' => $base->copy()->addDay(),
            default => $base,
        };
    }

    public static function shiftMonth(Carbon $base, string $direction): Carbon
    {
        return match ($direction) {
            'prev' => $base->copy()->subMonth()->startOfMonth(),
            'next' => $base->copy()->addMonth()->startOfMonth(),
            default => $base->copy()->startOfMonth(),
        };
    }

    public static function resolveDate(?string $date, ?string $direction): Carbon
    {
        $base = $date ? Carbon::parse($date) : today('Asia/Tokyo');
        return self::shiftDay($base, $direction ?? 'current');
    }

    public static function resolveMonth(Request $request): Carbon
    {
        $monthParam = $request->query('month');
        $base = self::todayJst();

        if ($monthParam && preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
            $base = Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth();
        }

        return self::shiftMonth($base, $request->query('direction', 'current'));
    }
}