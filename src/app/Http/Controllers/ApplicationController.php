<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\AttendanceApplication;
use App\Models\AttendanceApplicationItem;
use App\Models\BreakApplication;
use App\Http\Requests\ApplicationRequest;

class ApplicationController extends Controller
{
    public function submit(ApplicationRequest $request)
    {
        $user = auth()->user();

        $date = $request->input('date');

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        DB::beginTransaction();
        try {
            $isNew = !$attendance;

            $application = AttendanceApplication::create([
                'user_id' => $user->id,
                'attendance_id' => $isNew ? null : $attendance->id,
                'date' => $request->input('date'),
                'status' => 'pending',
                'reason' => $request->input('reason'),
                'clock_in' => $request->input('clock_in'),
                'clock_out' => $request->input('clock_out'),
            ]);


            $breaks = $request->input('breaks', []);
            foreach ($breaks as $break) {
                $start = $break['start'] ?? null;
                $end = $break['end'] ?? null;

                if ($start && $end) {
                    AttendanceApplicationItem::create([
                        'attendance_application_id' => $application->id,
                        'break_time_id' => $break['id'] ?? null,
                        'start' => $start,
                        'end' => $end,
                    ]);
                }
            }

            DB::commit();
            
            return redirect()->route('attendance.application', ['id' => $application->id])
                ->with('success', $isNew ? '勤怠作成の申請を送信しました' : '勤怠修正の申請を送信しました');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => '申請処理中にエラーが発生しました']);
        }
    }

    public function unapproved_list(Request $request)
    {
        $userId = Auth::id();

        $query = AttendanceApplication::with('breakApplications')
            ->where('user_id', $userId);

        if ($request->get('status') === 'approved') {
            $query->where('status', 'approved');
        } else {
            $query->where('status', 'pending');
        }

        $applications = $query->orderBy('created_at', 'desc')->get();

        return $applications;
    }

    public function approved_list()
    {
        $userId = Auth::id();

        $applications = Application::with('breakApplications')
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();

        return $applications;
    }

}
