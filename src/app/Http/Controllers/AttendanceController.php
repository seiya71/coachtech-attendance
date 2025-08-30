<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TodayAttendanceService;

class AttendanceController extends Controller
{
    public function __construct(private TodayAttendanceService $today)
    {
    }

    public function statusToday(Request $request)
    {
        $userId = $request->user()->id;
        $attendance = $this->today->fetchToday($userId, withBreaks: true);
        ['state' => $state, 'openBreak' => $openBreak] = $this->today->resolveState($attendance);

        return response()->json([
            'status' => $state,
            'attendance_id' => $attendance?->id,
            'break_time_id' => $openBreak?->id,   // ← キー名を統一
        ]);
    }

    public function clockIn(Request $request)
    {
        try {
            $attendance = $this->today->clockIn($request->user()->id);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if (in_array($e->getStatusCode(), [409, 422], true)) {
                return back()->withErrors(['clock_in' => $e->getMessage()]);
            }
            throw $e;
        }
        return redirect()->route('me.attendance.show', ['attendance' => $attendance->id])
            ->with('ok', '出勤を記録しました。');
    }

    public function clockOut(Request $request)
    {
        try {
            $attendance = $this->today->clockOut($request->user()->id);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if (in_array($e->getStatusCode(), [409, 422], true)) {
                return back()->withErrors(['clock_out' => $e->getMessage()]);
            }
            throw $e;
        }
        return redirect()->route('me.attendance.show', ['attendance' => $attendance->id])
            ->with('ok', '退勤を記録しました。お疲れさまでした！');
    }

    public function startBreak(Request $request)
    {
        try {
            $attendance = $this->today->breakIn($request->user()->id);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if (in_array($e->getStatusCode(), [409, 422], true)) {
                return back()->withErrors(['break_in' => $e->getMessage()]);
            }
            throw $e;
        }
        return redirect()->route('me.attendance.show', ['attendance' => $attendance->id])
            ->with('ok', '休憩入りを記録しました。');
    }

    public function finishBreak(Request $request)
    {
        try {
            $attendance = $this->today->breakOut($request->user()->id);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if (in_array($e->getStatusCode(), [409, 422], true)) {
                return back()->withErrors(['break_out' => $e->getMessage()]);
            }
            throw $e;
        }
        return redirect()->route('me.attendance.show', ['attendance' => $attendance->id])
            ->with('ok', '休憩戻りを記録しました。');
    }
}
