<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Enums\AttendanceState; // ← 定数クラスなら use App\Enums\AttendanceState as S;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TodayAttendanceService
{
    public function __construct(private readonly ?string $tz = null)
    {
    }

    private function now(): Carbon
    {
        return now($this->tz ?? config('app.timezone', 'Asia/Tokyo'));
    }

    private function todayDate(): string
    {
        return $this->now()->toDateString();
    }

    public function fetchToday(int $userId, bool $withBreaks = false, bool $lock = false): ?Attendance
    {
        $query = Attendance::query()
            ->where('user_id', $userId)
            ->whereDate('work_date', $this->todayDate());

        if ($withBreaks)
            $query->with('breaks');
        if ($lock)
            $query->lockForUpdate();

        $attendance = $query->first();

        if (!$attendance) {
            $fallback = Attendance::query()
                ->where('user_id', $userId)
                ->whereDate('clock_in', $this->todayDate())
                ->when($withBreaks, fn($q) => $q->with('breaks'))
                ->when($lock, fn($q) => $q->lockForUpdate())
                ->first();
            $attendance = $fallback ?: null;
        }
        return $attendance;
    }

    public function findOpenBreak(?Attendance $attendance): ?BreakTime
    {
        if (!$attendance)
            return null;

        return $attendance->breaks()
            ->whereNotNull('started_at')
            ->whereNull('ended_at')
            ->orderByDesc('started_at')
            ->first();
    }

    public function resolveState(?Attendance $attendance): array
    {
        if (!$attendance) {
            return ['state' => AttendanceState::OFF_DUTY, 'openBreak' => null];
        }
        if (!is_null($attendance->clock_out)) {
            return ['state' => AttendanceState::CLOCKED_OUT, 'openBreak' => null];
        }
        $open = $this->findOpenBreak($attendance);
        if ($open) {
            return ['state' => AttendanceState::ON_BREAK, 'openBreak' => $open];
        }
        return ['state' => AttendanceState::WORKING, 'openBreak' => null];
    }

    public function clockIn(int $userId): Attendance
    {
        $now = $this->now();
        $today = $this->todayDate();

        return DB::transaction(function () use ($userId, $now, $today) {
            $attendance = $this->fetchToday($userId, withBreaks: true, lock: true);

            if ($attendance && $attendance->clock_in && is_null($attendance->clock_out)) {
                abort(409, 'すでに出勤済です。');
            }

            $attendance ??= new Attendance(['user_id' => $userId, 'work_date' => $today]);
            $attendance->clock_in = $now;
            $attendance->save();

            return $attendance;
        });
    }

    public function clockOut(int $userId): Attendance
    {
        $now = $this->now();

        return DB::transaction(function () use ($userId, $now) {
            $attendance = $this->fetchToday($userId, withBreaks: true, lock: true);

            if (!$attendance || !$attendance->clock_in) {
                abort(409, '本日の出勤記録がありません。');
            }
            if ($attendance->clock_out) {
                abort(409, '本日の業務は終了しています。');
            }
            if ($this->findOpenBreak($attendance)) {
                abort(409, '今は休憩中です。先に休憩戻りを入力してください。');
            }
            if ($attendance->clock_in->gt($now)) {
                abort(422, '退勤時刻が出勤時刻よりも前です。');
            }

            $attendance->clock_out = $now;
            $attendance->save();
            return $attendance;
        });
    }

    public function breakIn(int $userId): Attendance
    {
        $now = $this->now();

        return DB::transaction(function () use ($userId, $now) {
            $attendance = $this->fetchToday($userId, withBreaks: true, lock: true);

            if (!$attendance || !$attendance->clock_in) {
                abort(409, '本日の出勤記録がありません。');
            }
            if ($attendance->clock_out) {
                abort(409, '本日の業務は終了しています。');
            }
            if ($this->findOpenBreak($attendance)) {
                abort(409, 'すでに休憩中です。先に休憩戻りを入力してください。');
            }
            if ($attendance->clock_in->gt($now)) {
                abort(422, '休憩入り時刻が出勤時刻よりも前です。');
            }

            $attendance->breaks()->create(['started_at' => $now, 'ended_at' => null]);
            return $attendance;
        });
    }

    public function breakOut(int $userId): Attendance
    {
        $now = $this->now();

        return DB::transaction(function () use ($userId, $now) {
            $attendance = $this->fetchToday($userId, withBreaks: true, lock: true);

            if (!$attendance || !$attendance->clock_in) {
                abort(409, '本日の出勤記録がありません。');
            }
            if ($attendance->clock_out) {
                abort(409, '本日の業務は終了しています。');
            }
            $openBreak = $this->findOpenBreak($attendance);
            if (!$openBreak) {
                abort(409, 'まだ休憩をしていません。');
            }
            if ($openBreak->started_at->gt($now)) {
                abort(422, '休憩戻り時刻が休憩入りよりも前です。');
            }

            $openBreak->ended_at = $now;
            $openBreak->save();
            return $attendance;
        });
    }
}
