<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;

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
        $staffs = User::where('role', 'user')->get(['id', 'name', 'email']);

        return view('admin.staff_list', compact('staffs'));
    }

}
