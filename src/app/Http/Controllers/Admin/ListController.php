<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceService;

class ListController extends Controller
{
    private function getAttendancesByDate(string $date)
    {
        return Attendance::with('breakTimes', 'user')
            ->whereDate('date', $date)
            ->get();
    }

    public function index(Request $request)
    {
        $currentDate = AttendanceService::resolveDate(
            $request->input('date'),
            $request->input('direction')
        );

        $attendances = $this->getAttendancesByDate($currentDate);

        return view('admin.attendance_list', compact('attendances', 'currentDate'));
    }

    public function attendanceDetail(Request $request, $userId, $attendanceId)
    {
        $attendance = Attendance::with('breakTimes')
            ->where('user_id', $userId)
            ->findOrFail($attendanceId);

        $attendance->breaks = $attendance->breakTimes->map(fn($item) => (object) [
            'start' => $item->start_time,
            'end' => $item->end_time,
        ]);

        $attendance->is_editable = true;

        return view('admin.attendance_detail', [
            'attendance' => $attendance,
            'status' => 'attendance',
        ]);
    }

    public function attendanceDetailNew(Request $request, $userId, $date)
    {
        $targetDate = Carbon::createFromFormat('Y-m-d', $date)->toDateString();
        $user = User::findOrFail($userId);

        $data = (object) [
            'id' => null,
            'user_id' => $userId,
            'user' => $user,
            'date' => Carbon::parse($targetDate),
            'clock_in' => null,
            'clock_out' => null,
            'breaks' => collect([]),
            'reason' => '',
            'is_editable' => true,
        ];

        return view('admin.attendance_detail', [
            'attendance' => $data,
            'status' => 'new_entry',
        ]);
    }

    public function staffList()
    {
        $staffs = User::all(['id', 'name', 'email', 'role']);

        return view('admin.staff_list', compact('staffs'));
    }
    private function normalizeTime($value): ?Carbon
    {
        if (!$value)
            return null;
        if ($value instanceof Carbon)
            return $value;

        if (is_string($value)) {
            foreach (['H:i:s', 'H:i'] as $fmt) {
                try {
                    return Carbon::createFromFormat($fmt, $value);
                } catch (\Exception $e) {
                }
            }
        }
        return null;
    }

    public function staffAttendanceList(Request $request, int $userId)
    {
        $user = User::findOrFail($userId);

        $currentMonth = AttendanceService::resolveMonth($request);
        $attendanceList = AttendanceService::getMonthlyAttendanceList($user->id, $currentMonth);


        return view('admin.staff_attendance_list', [
            'user' => $user,
            'attendanceList' => $attendanceList,
            'currentMonth' => $currentMonth,
            'prevMonth' => AttendanceService::shiftMonth($currentMonth, 'prev'),
            'nextMonth' => AttendanceService::shiftMonth($currentMonth, 'next'),
        ]);
    }
}
