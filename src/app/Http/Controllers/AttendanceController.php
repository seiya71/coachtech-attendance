<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Facades\DB;

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

    private const TZ = 'Asia/Tokyo';

    public function clockIn(Request $request)
    {
        $authenticatedUser = $request->user();
        $nowJst = now(self::TZ);
        $workDate = $nowJst->toDateString();

        try {
            $attendanceForToday = DB::transaction(function () use ($authenticatedUser, $workDate, $nowJst) {
                // 当日の勤怠をロックして取得（連打・多重リクエスト対策）
                $existingAttendance = Attendance::where('user_id', $authenticatedUser->id)
                    ->whereDate('work_date', $workDate)
                    ->lockForUpdate()
                    ->first();

                $isCurrentlyWorking = $existingAttendance
                    && $existingAttendance->clock_in !== null
                    && $existingAttendance->clock_out === null;

                if ($isCurrentlyWorking) {
                    abort(409, 'すでに出勤済です。');
                }

                $attendance = $existingAttendance ?? new Attendance([
                    'user_id' => $authenticatedUser->id,
                    'work_date' => $workDate,
                ]);

                $attendance->clock_in = $nowJst;
                $attendance->save();

                return $attendance;
            });

        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $httpException) {
            if ($httpException->getStatusCode() === 409) {
                return back()->withErrors(['clock_in' => $httpException->getMessage()]);
            }
            throw $httpException;
        }

        return redirect()
            ->route('me.attendance.show', ['attendance' => $attendanceForToday->id])
            ->with('ok', '出勤を記録しました。');
    }

    public function clockOut(Request $request)
    {
        $authenticatedUser = $request->user();
        $nowJst = now(self::TZ);
        $workDate = $nowJst->toDateString();

        try {
            $attendanceForToday = DB::transaction(function () use ($authenticatedUser, $workDate, $nowJst) {
                // 当日の勤怠をロックして取得（休憩も一緒に）
                $existingAttendance = Attendance::with('breaks')
                    ->where('user_id', $authenticatedUser->id)
                    ->whereDate('work_date', $workDate)
                    ->lockForUpdate()
                    ->first();

                // 出勤記録が無い or 出勤していない
                if (!$existingAttendance || $existingAttendance->clock_in === null) {
                    abort(409, '本日の出勤記録がありません。');
                }

                // 退勤済み？
                $isAlreadyClockedOut = $existingAttendance->clock_out !== null;
                if ($isAlreadyClockedOut) {
                    abort(409, '本日の業務は終了しています。');
                }

                // 休憩中（戻りが未入力の休憩がある）
                $isOnBreakNow = $existingAttendance->breaks->contains(
                    fn($breakRecord) => $breakRecord->started_at !== null && $breakRecord->ended_at === null
                );
                if ($isOnBreakNow) {
                    abort(409, '今は休憩中です。先に休憩戻りを入力してください。');
                }

                // 退勤打刻（順序の最終チェックも一応）
                if ($existingAttendance->clock_in->gt($nowJst)) {
                    abort(422, '退勤時刻が出勤時刻よりも前です。');
                }

                $existingAttendance->clock_out = $nowJst;
                $existingAttendance->save();

                return $existingAttendance;
            });

        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $httpException) {
            // 409/422 はフラッシュで画面に返す
            $status = $httpException->getStatusCode();
            if (in_array($status, [409, 422], true)) {
                return back()->withErrors(['clock_out' => $httpException->getMessage()]);
            }
            throw $httpException;
        }

        return redirect()
            ->route('me.attendance.show', ['attendance' => $attendanceForToday->id])
            ->with('ok', '退勤を記録しました。お疲れさまでした！');
    }

    public function startBreak(Request $request)
    {
        $authenticatedUser = $request->user();
        $nowJst = now(self::TZ);
        $workDate = $nowJst->toDateString();

        try {
            $attendanceForToday = DB::transaction(function () use ($authenticatedUser, $workDate, $nowJst) {
                // 当日の勤怠をロックして取得（休憩も一緒に）
                $attendance = Attendance::with('breaks')
                    ->where('user_id', $authenticatedUser->id)
                    ->whereDate('work_date', $workDate)
                    ->lockForUpdate()
                    ->first();

                // 勤務中判定
                if (!$attendance || $attendance->clock_in === null) {
                    abort(409, '本日の出勤記録がありません。');
                }
                if ($attendance->clock_out !== null) {
                    abort(409, '本日の業務は終了しています。');
                }

                // すでに休憩中か？
                $isOnBreakNow = $attendance->breaks->contains(
                    fn($breakRecord) => $breakRecord->started_at !== null && $breakRecord->ended_at === null
                );
                if ($isOnBreakNow) {
                    abort(409, 'すでに休憩中です。先に休憩戻りを入力してください。');
                }

                // 出勤より前の時刻になるのを防止（通常は now() なので問題なしだが念のため）
                if ($attendance->clock_in->gt($nowJst)) {
                    abort(422, '休憩入り時刻が出勤時刻よりも前です。');
                }

                // 休憩レコード作成
                $attendance->breaks()->create([
                    'started_at' => $nowJst,
                    'ended_at' => null,
                ]);

                return $attendance;
            });

        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $httpException) {
            $status = $httpException->getStatusCode();
            if (in_array($status, [409, 422], true)) {
                return back()->withErrors(['break_in' => $httpException->getMessage()]);
            }
            throw $httpException;
        }

        return redirect()
            ->route('me.attendance.show', ['attendance' => $attendanceForToday->id])
            ->with('ok', '休憩入りを記録しました。');
    }
}
