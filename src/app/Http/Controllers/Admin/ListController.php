<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\User;

class ListController extends Controller
{
    private function getAttendancesByDate(string $date)
    {
        return Attendance::with('breakTimes', 'user')
            ->whereDate('date', $date)
            ->get();
    }

    private function resolveDate(?string $date, ?string $direction): string
    {
        $currentDate = $date ?? today()->toDateString();
        $carbon = Carbon::parse($currentDate);

        if ($direction === 'next') {
            return $carbon->addDay()->toDateString();
        } elseif ($direction === 'prev') {
            return $carbon->subDay()->toDateString();
        }

        return $carbon->toDateString();
    }

    public function index(Request $request)
    {
        $currentDate = $this->resolveDate(
            $request->input('date'),
            $request->input('direction')
        );

        $attendances = $this->getAttendancesByDate($currentDate);

        return view('admin.attendance_list', compact('attendances', 'currentDate'));
    }

    public function attendanceDetail(Request $request, $userId, $date)
    {
        $targetDate = Carbon::parse($date)->toDateString();

        $attendance = Attendance::with('breakTimes')
            ->where('user_id', $userId)
            ->whereDate('date', $targetDate)
            ->first();

        if ($attendance) {
            $data = $attendance->setAttribute('breaks', $attendance->breakTimes->map(fn($item) => (object) [
                'start' => $item->start_time,
                'end' => $item->end_time,
            ]));

            $data->date = Carbon::parse($data->date);
            $data->setAttribute('is_editable', true);

            $status = 'attendance';
        } else {
            $data = (object) [
                'id' => null,
                'user_id' => $userId,
                'date' => $targetDate,
                'clock_in' => null,
                'clock_out' => null,
                'breaks' => collect([]),
                'reason' => '',
                'is_editable' => true,
            ];

            $status = 'new_entry';
        }

        return view('admin.attendance_detail', [
            'attendance' => $data,
            'status' => $status,
        ]);
    }

    public function staffList()
    {
        $staffs = User::all(['id', 'name', 'email', 'role']);

        return view('admin.staff_list', compact('staffs'));
    }

    private function getMonthlyAttendanceList(int $userId, Carbon $month): array
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

            $breakTotalMin = $attendance->breakTimes->reduce(function ($carry, $break) {
                return $carry + ($break->start_time && $break->end_time
                    ? $break->end_time->diffInMinutes($break->start_time)
                    : 0);
            }, 0);
            $workMinutes = $attendance->clock_out
                ? $attendance->clock_in->diffInMinutes($attendance->clock_out) - $breakTotalMin
                : null;

            $results[] = [
                'date' => $date,
                'attendance_id' => $attendance->id,
                'clock_in' => optional($attendance->clock_in)->format('H:i'),
                'clock_out' => optional($attendance->clock_out)->format('H:i'),
                'break_time' => $breakTotalMin ? gmdate('H:i', $breakTotalMin * 60) : '',
                'work_time' => $workMinutes !== null ? gmdate('H:i', $workMinutes * 60) : '',
            ];
        }

        return $results;
    }

    private function resolveMonth(Request $request): Carbon
    {
        if ($request->has('month')) {
            try {
                return Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth();
            } catch (\Exception $e) {
            }
        }

        return now('Asia/Tokyo')->startOfMonth();
    }

    public function staffAttendanceList(Request $request, int $userId)
    {
        $user = User::findOrFail($userId);

        $currentMonth = $this->resolveMonth($request);

        $attendanceList = $this->getMonthlyAttendanceList($user->id, $currentMonth);

        return view('admin.staff_attendance_list', [
            'user' => $user,
            'attendanceList' => $attendanceList,
            'currentMonth' => $currentMonth,
            'prevMonth' => $currentMonth->copy()->subMonth(),
            'nextMonth' => $currentMonth->copy()->addMonth(),
        ]);
    }
}
