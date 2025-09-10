<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\AttendanceApplication;
use App\Models\AttendanceApplicationItem;

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

    public function listIndex(Request $request)
    {
        $user = $request->user();

        $currentMonth = $this->resolveMonth($request);

        $attendanceList = $this->getMonthlyAttendanceList($user->id, $currentMonth);

        return view('attendance_list', [
            'attendanceList' => $attendanceList,
            'currentMonth' => $currentMonth,
            'prevMonth' => $currentMonth->copy()->subMonth(),
            'nextMonth' => $currentMonth->copy()->addMonth(),
        ]);
    }

    private function getAttendanceDetail(Request $request, $key)
    {
        $user = auth()->user();
        $routeName = $request->route()->getName();

        switch ($routeName) {
            case 'attendance.detail':
                $attendance = Attendance::with('breakTimes')
                    ->where('id', $key)
                    ->firstOrFail();

                $application = AttendanceApplication::where('user_id', $user->id)
                    ->where('date', $attendance->date)
                    ->where('status', 'pending')
                    ->first();

                if ($application) {
                    $data = $application->setAttribute('breaks', $application->items->map(function ($item) {
                        return (object) [
                            'start' => $item->start,
                            'end' => $item->end,
                        ];
                    })->toArray());
                    $data->date = Carbon::parse($data->date);
                    $data->setAttribute('is_editable', false);

                    return [
                        'type' => 'application',
                        'user_id' => $application->user_id,
                        'user' => $user,
                        'data' => $data,
                        'status' => 'submitted',
                        'reason' => $application->reason,
                    ];
                }

                $data = $attendance->setAttribute('breaks', $attendance->breakTimes->map(function ($item) {
                    return (object) [
                        'start' => $item->start_time,
                        'end' => $item->end_time,
                    ];
                })->toArray());
                $data->date = Carbon::parse($data->date);
                $data->setAttribute('is_editable', true);

                return [
                    'type' => 'attendance',
                    'user_id' => $attendance->user_id,
                    'user' => $user,
                    'data' => $data,
                    'status' => 'editable',
                    'reason' => '',
                ];

            case 'attendance.new':
                try {
                    $date = Carbon::parse($key)->startOfDay();
                } catch (\Exception $e) {
                    abort(400, '不正な日付形式です');
                }

                $application = AttendanceApplication::where('user_id', $user->id)
                    ->where('date', $date)
                    ->where('status', 'pending')
                    ->first();

                if ($application) {
                    $data = $application->setAttribute('breaks', $application->items->map(function ($item) {
                        return (object) [
                            'start' => $item->start,
                            'end' => $item->end,
                        ];
                    })->toArray());
                    $data->date = Carbon::parse($data->date);
                    $data->setAttribute('is_editable', false);

                    return [
                        'type' => 'application',
                        'user_id' => $application->user_id,
                        'user' => $user,
                        'data' => $data,
                        'status' => 'submitted',
                        'reason' => $application->reason,
                    ];
                }

                return [
                    'type' => 'new_entry',
                    'user_id' => $user->id,
                    'data' => (object) [
                        'id' => null,
                        'user_id' => $user->id,
                        'user' => $user,
                        'date' => $date,
                        'clock_in' => null,
                        'clock_out' => null,
                        'breaks' => [],
                        'reason' => '',
                        'is_editable' => true,
                    ],
                    'status' => 'new_entry',
                ];

            case 'attendance.application':
                $application = AttendanceApplication::with('items')
                    ->where('id', $key)
                    ->firstOrFail();
                $data = $application->setAttribute('breaks', $application->items->map(function ($item) {
                    return (object) [
                        'start' => $item->start,
                        'end' => $item->end,
                    ];
                })->toArray());
                $data->date = Carbon::parse($data->date);
                $data->setAttribute('is_editable', false);

                return [
                    'type' => 'application',
                    'user_id' => $application->user_id,
                    'user' => $user,
                    'data' => $data,
                    'status' => 'submitted',
                    'reason' => $application->reason,
                ];

            default:
                abort(404);
        }
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
