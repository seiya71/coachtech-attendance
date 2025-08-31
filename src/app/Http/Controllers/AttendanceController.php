<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    private function statusCheck($userId): array
    {
        $today = now('Asia/Tokyo')->toDateString();

        $attendance = Attendance::with('breakTimes')
            ->where('user_id', $userId)
            ->whereDate('clock_in', $today)
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

    public function clockIn(Request $request)
    {
        $userId = $request->user()->id;
        $status = $this->statusCheck($userId)['status'];

        if ($status !== '勤務外') {
            return back()->withErrors(['clock_in' => 'すでに出勤済です。']);
        }

        Attendance::create([
            'user_id' => $userId,
            'date' => now('Asia/Tokyo')->toDateString(),
            'clock_in' => now('Asia/Tokyo'),
        ]);

        return redirect()->route('me.attendance.show')->with('ok', '出勤を記録しました。');
    }

    public function clockOut(Request $request)
    {
        $userId = $request->user()->id;
        $data = $this->statusCheck($userId);

        if ($data['status'] === '退勤済') {
            return back()->withErrors(['clock_out' => '本日の業務は終了しています。']);
        }

        if ($data['status'] === '休憩中') {
            return back()->withErrors(['clock_out' => '今は休憩中です。']);
        }

        $data['attendance']->update([
            'clock_out' => now('Asia/Tokyo'),
        ]);

        return redirect()->route('me.attendance.show')->with('ok', '退勤を記録しました。');
    }

    public function startBreak(Request $request)
    {
        $user = $request->user();
        $statusInfo = $this->statusCheck($user->id);

        if ($statusInfo['status'] !== '勤務中') {
            return back()->withErrors(['break_in' => 'ただいま休憩はできません。']);
        }

        $attendance = $statusInfo['attendance'];

        $attendance->breakTimes()->create([
            'start_time' => now('Asia/Tokyo'),
            'end_time' => null,
        ]);

        return redirect()
            ->route('me.attendance.show')
            ->with('ok', '休憩入りを記録しました。');
    }

    public function finishBreak(Request $request)
    {
        $user = $request->user();
        $statusInfo = $this->statusCheck($user->id);

        if ($statusInfo['status'] !== '休憩中') {
            return back()->withErrors(['break_out' => 'まだ休憩をしていません。']);
        }

        $break = $statusInfo['break'];
        $break->end_time = now('Asia/Tokyo');
        $break->save();

        return redirect()
            ->route('me.attendance.show')
            ->with('ok', '休憩戻りを記録しました。');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $statusInfo = $this->statusCheck($user->id);
        $now = now('Asia/Tokyo');

        return view('attendance', [
            'status' => $statusInfo['status'],
            'attendance' => $statusInfo['attendance'],
            'break' => $statusInfo['break'],
            'today' => $now->toDateString(),
            'time' => $now->format('H:i:s'),
        ]);
    }
}
