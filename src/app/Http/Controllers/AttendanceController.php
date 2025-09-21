<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\AttendanceApplication;
use App\Models\AttendanceApplicationItem;
use App\Services\AttendanceService;

class AttendanceController extends Controller
{
    public static function todayFormatted(): string
    {
        $day = ['日', '月', '火', '水', '木', '金', '土'];
        $now = AttendanceService::todayJst();
        return $now->format('Y年n月j日') . '(' . $day[$now->dayOfWeek] . ')';
    }

    public function clockIn(Request $request)
    {
        $userId = $request->user()->id;
        $status = AttendanceService::statusCheck($userId)['status'];

        if ($status !== '勤務外') {
            return back();
        }

        Attendance::create([
            'user_id' => $userId,
            'date' => AttendanceService::todayJst(),
            'clock_in' => now('Asia/Tokyo'),
        ]);

        return redirect()->route('attendance.index');
    }

    public function clockOut(Request $request)
    {
        $userId = $request->user()->id;
        $data = AttendanceService::statusCheck($userId);

        if ($data['status'] === '退勤済') {
            return back();
        }

        if ($data['status'] === '休憩中') {
            return back();
        }

        $data['attendance']->update([
            'clock_out' => AttendanceService::todayJst(),
        ]);

        return redirect()->route('attendance.index');
    }

    public function startBreak(Request $request)
    {
        $user = $request->user();
        $statusInfo = AttendanceService::statusCheck($user->id);

        if ($statusInfo['status'] !== '勤務中') {
            return back();
        }

        $attendance = $statusInfo['attendance'];

        $attendance->breakTimes()->create([
            'start_time' => AttendanceService::todayJst(),
            'end_time' => null,
        ]);

        return redirect()
            ->route('attendance.index');
    }

    public function finishBreak(Request $request)
    {
        $user = $request->user();
        $statusInfo = AttendanceService::statusCheck($user->id);

        if ($statusInfo['status'] !== '休憩中') {
            return back();
        }

        $break = $statusInfo['break'];
        $break->end_time = AttendanceService::todayJst();
        $break->save();

        return redirect()
            ->route('attendance.index');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $statusInfo = AttendanceService::statusCheck($user->id);
        $now = AttendanceService::todayJst();

        return view('attendance', [
            'status' => $statusInfo['status'],
            'attendance' => $statusInfo['attendance'],
            'break' => $statusInfo['break'],
            'time' => $now->format('H:i'),
            'date' => $this->todayFormatted(),
        ]);
    }

    private function calculateWorkAndBreakTime(Attendance $attendance): array
    {
        $breakMinutes = $attendance->breakTimes->reduce(function ($total, $break) {
            if ($break->start_time && $break->end_time) {
                return $total + $break->end_time->diffInMinutes($break->start_time);
            }
            return $total;
        }, 0);

        if ($attendance->clock_in && $attendance->clock_out) {
            $workMinutes = $attendance->clock_in->diffInMinutes($attendance->clock_out) - $breakMinutes;
        } else {
            $workMinutes = null;
        }

        return [
            'attendance_id' => $attendance->id,
            'date' => $attendance->date,
            'clock_in' => optional($attendance->clock_in)->format('H:i') ?? '',
            'clock_out' => optional($attendance->clock_out)->format('H:i') ?? '',
            'break_time' => $breakMinutes ? gmdate('H:i', $breakMinutes * 60) : '',
            'work_time' => $workMinutes !== null ? gmdate('H:i', $workMinutes * 60) : '',
        ];
    }

    public function listIndex(Request $request)
    {
        $user = $request->user();

        $currentMonth = AttendanceService::resolveMonth($request);
        $attendanceList = AttendanceService::getMonthlyAttendanceList($request->user()->id, $currentMonth);

        return view('attendance_list', [
            'attendanceList' => $attendanceList,
            'currentMonth' => $currentMonth,
            'prevMonth' => AttendanceService::shiftMonth($currentMonth, 'prev'),
            'nextMonth' => AttendanceService::shiftMonth($currentMonth, 'next'),
        ]);
    }

    private function getAttendanceDetail(Request $request, $key)
    {
        $user = auth()->user();
        $routeName = $request->route()->getName();

        switch ($routeName) {
            case 'attendance.detail':
                $attendanceData = AttendanceService::getAttendanceDetailById($user, $key);
                break;

            case 'attendance.new':
                $attendanceData = AttendanceService::getNewAttendanceDetail($user, $key);
                break;

            case 'attendance.application':
                $attendanceData = AttendanceService::getApplicationDetail($user, $key);
                break;

            default:
                abort(404);
        }

        return $attendanceData;
    }


    public function attendanceDetail(Request $request, $key = null)
    {
        $user = auth()->user();
        $attendanceData = $this->getAttendanceDetail($request, $key);

        if ($attendanceData['user_id'] !== $user->id) {
            abort(403, '他人のデータにはアクセスできません');
        }

        return view('attendance_detail', [
            'attendance' => $attendanceData['data'],
            'status' => $attendanceData['status'],
        ]);
    }
}
