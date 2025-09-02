<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public static function todayJst(): Carbon
    {
        return now('Asia/Tokyo');
    }

    public static function todayFormatted(): string
    {
        $day = ['日', '月', '火', '水', '木', '金', '土'];
        $now = self::todayJst();
        return $now->format('Y年n月j日') . '(' . $day[$now->dayOfWeek] . ')';
    }
    private function statusCheck($userId): array
    {
        $today = $this->todayJst();

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

    public function clockIn(Request $request)
    {
        $userId = $request->user()->id;
        $status = $this->statusCheck($userId)['status'];

        if ($status !== '勤務外') {
            return back();
        }

        Attendance::create([
            'user_id' => $userId,
            'date' => $this->todayJst(),
            'clock_in' => now('Asia/Tokyo'),
        ]);

        return redirect()->route('attendance.index');
    }

    public function clockOut(Request $request)
    {
        $userId = $request->user()->id;
        $data = $this->statusCheck($userId);

        if ($data['status'] === '退勤済') {
            return back();
        }

        if ($data['status'] === '休憩中') {
            return back();
        }

        $data['attendance']->update([
            'clock_out' => $this->todayJst(),
        ]);

        return redirect()->route('attendance.index');
    }

    public function startBreak(Request $request)
    {
        $user = $request->user();
        $statusInfo = $this->statusCheck($user->id);

        if ($statusInfo['status'] !== '勤務中') {
            return back();
        }

        $attendance = $statusInfo['attendance'];

        $attendance->breakTimes()->create([
            'start_time' => $this->todayJst(),
            'end_time' => null,
        ]);

        return redirect()
            ->route('attendance.index');
    }

    public function finishBreak(Request $request)
    {
        $user = $request->user();
        $statusInfo = $this->statusCheck($user->id);

        if ($statusInfo['status'] !== '休憩中') {
            return back();
        }

        $break = $statusInfo['break'];
        $break->end_time = $this->todayJst();
        $break->save();

        return redirect()
            ->route('attendance.index');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $statusInfo = $this->statusCheck($user->id);
        $now = $this->todayJst();

        return view('attendance', [
            'status' => $statusInfo['status'],
            'attendance' => $statusInfo['attendance'],
            'break' => $statusInfo['break'],
            'time' => $now->format('H:i'),
            'date' => $this->todayFormatted(),
        ]);
    }
}
