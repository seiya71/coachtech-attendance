<?php

namespace App\Services;

use Carbon\Carbon;

class AttendanceService
{
    public static function todayJst(): Carbon
    {
        return now('Asia/Tokyo');
    }

    private function resolveDate(?string $date, ?string $direction): string
    {
        $currentDate = $date ?? today()->toDateString();
        $carbon = Carbon::parse($currentDate);

        if ($direction === 'next') {
            return $carbon->addDay()->toDateString();
        } elseif ($direction === 'prev') {
            return $carbon->subDay()->toDateString();
        }

        return $carbon->toDateString();
    }

    private function resolveMonth(Request $request): Carbon
    {
        $monthParam = $request->query('month');

        if ($monthParam && preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
            return Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth();
        }

        return now('Asia/Tokyo')->startOfMonth();
    }

}