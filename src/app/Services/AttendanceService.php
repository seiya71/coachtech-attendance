<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Attendance;

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

    public static function statusCheck(int $userId): array
    {
        $today = self::todayJst();

        $attendance = Attendance::with('breakTimes')
            ->where('user_id', $userId)
            ->whereDate('date', $today)
            ->latest('id')
            ->first();

        if (!$attendance) {
            return ['status' => '勤務外', 'attendance' => null, 'break' => null];
        }

        if ($attendance->clock_out !== null) {
            return ['status' => '退勤済', 'attendance' => $attendance, 'break' => null];
        }

        $openBreak = $attendance->breakTimes()
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        if ($openBreak) {
            return ['status' => '休憩中', 'attendance' => $attendance, 'break' => $openBreak];
        }

        return ['status' => '勤務中', 'attendance' => $attendance, 'break' => null];
    }

    public static function getMonthlyAttendanceList(int $userId, Carbon $month): array
    {
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $userId)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get()
            ->keyBy(fn($attendance) => $attendance->date->format('Y-m-d'));

        $results = [];

        for ($day = 1; $day <= $month->daysInMonth; $day++) {
            $date = $month->copy()->day($day);
            $attendance = $attendances->get($date->format('Y-m-d'));

            if (!$attendance) {
                $results[] = [
                    'date' => $date,
                    'attendance_id' => null,
                    'clock_in' => '',
                    'clock_out' => '',
                    'break_time' => '',
                    'work_time' => '',
                ];
                continue;
            }

            $breakMinutes = $attendance->breakTimes->reduce(function ($carry, $break) {
                if ($break->start_time && $break->end_time) {
                    return $carry + $break->end_time->diffInMinutes($break->start_time);
                }
                return $carry;
            }, 0);

            $workMinutes = ($attendance->clock_in && $attendance->clock_out)
                ? $attendance->clock_in->diffInMinutes($attendance->clock_out) - $breakMinutes
                : null;

            $results[] = [
                'date' => $date,
                'attendance_id' => $attendance->id,
                'clock_in' => optional($attendance->clock_in)->format('H:i'),
                'clock_out' => optional($attendance->clock_out)->format('H:i'),
                'break_time' => $breakMinutes ? gmdate('H:i', $breakMinutes * 60) : '',
                'work_time' => $workMinutes !== null ? gmdate('H:i', $workMinutes * 60) : '',
            ];
        }

        return $results;
    }

}