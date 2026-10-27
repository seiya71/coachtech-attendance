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
        $attendanceData = AttendanceService::getAdminAttendanceDetailById($userId, $attendanceId);

        return view('admin.attendance_detail', [
            'attendance' => $attendanceData['attendance'],
            'status' => $attendanceData['status'],
        ]);
    }

    public function attendanceDetailNew(Request $request, $userId, $date)
    {
        $attendanceData = AttendanceService::getAdminNewAttendanceDetail($userId, $date);

        return view('admin.attendance_detail', [
            'attendance' => $attendanceData['attendance'],
            'status' => $attendanceData['status'],
        ]);
    }

    public function staffList()
    {
        $staffs = User::all(['id', 'name', 'email', 'role']);

        return view('admin.staff_list', compact('staffs'));
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
