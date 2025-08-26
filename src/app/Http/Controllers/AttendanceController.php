<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\BreakTime;

class AttendanceController extends Controller
{
    public function statusToday(Request $request)
    {
        $result = $this->resolveTodayStatus(Auth::id());

        return response()->json([
            'status' => $result['status'],
            'attendance_id' => $result['attendance_id'],
            'break_time_id' => $result['break_time_id'],
        ]);
    }

    private function resolveTodayStatus(int $userId): array
    {
        // アプリのTZに合わせる（config/app.php の timezone を使用）
        $today = Carbon::now(config('app.timezone', 'Asia/Tokyo'))->toDateString();

        // 1) 今日の勤怠レコードを取得（work_date がある前提）
        $attendance = Attendance::query()
            ->where('user_id', $userId)
            ->whereDate('work_date', $today)
            ->latest('id')
            ->first();

        // work_date を持たない設計なら clock_in の日付でフォールバック
        if (!$attendance) {
            $attendance = Attendance::query()
                ->where('user_id', $userId)
                ->whereDate('clock_in', $today)
                ->latest('id')
                ->first();
        }

        // 勤怠なし → 勤務外
        if (!$attendance) {
            return [
                'status' => '勤務外',
                'attendance_id' => null,
                'break_id' => null,
            ];
        }

        // clock_out が入っていれば → 退勤済
        if (!is_null($attendance->clock_out)) {
            return [
                'status' => '退勤済',
                'attendance_id' => $attendance->id,
                'break_id' => null,
            ];
        }

        // 休憩中判定：最新の休憩の end_time が null なら休憩中
        $latestBreak = BreakModel::query()
            ->where('attendance_id', $attendance->id)
            ->orderByDesc('start_time')
            ->first();

        if ($latestBreak && is_null($latestBreak->end_time)) {
            return [
                'status' => '休憩中',
                'attendance_id' => $attendance->id,
                'break_id' => $latestBreak->id,
            ];
        }

        // それ以外 → 勤務中
        return [
            'status' => '勤務中',
            'attendance_id' => $attendance->id,
            'break_id' => $latestBreak?->id,
        ];
    }

    public function index()
    {
        return view('attendance');
    }
}
