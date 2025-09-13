<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceApplication;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;

class ApplicationController extends Controller
{
    private function getApplications(string $status)
    {
        return AttendanceApplication::with(['items', 'user'])
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function requestsList(Request $request)
    {
        if ($request->route()->getName() === 'admin.applications.list.approved') {
            $applications = $this->getApplications('approved');
            $isApproved = true;
        } else {
            $applications = $this->getApplications('pending');
            $isApproved = false;
        }

        return view('admin.requests_list', compact('applications', 'isApproved'));
    }

    public function approveForm($applicationId)
    {
        $application = AttendanceApplication::with(['items', 'user'])
            ->findOrFail($applicationId);

        return view('admin.application_detail', compact('application'));
    }

    public function approve(Request $request, $applicationId)
    {
        $application = AttendanceApplication::with('items')->findOrFail($applicationId);

        DB::transaction(function () use ($application) {
            $application->update(['status' => 'approved']);

            $attendance = Attendance::firstOrNew([
                'user_id' => $application->user_id,
                'date' => $application->date,
            ]);

            $attendance->clock_in = $application->clock_in;
            $attendance->clock_out = $application->clock_out;
            $attendance->save();

            $attendance->breakTimes()->delete();
            foreach ($application->items as $item) {
                $attendance->breakTimes()->create([
                    'start_time' => $item->start,
                    'end_time' => $item->end,
                ]);
            }
        });

        return redirect()->route('admin.application.approve.form', [
            'attendance_application_id' => $application->id
        ])->with('status', '申請を承認しました');
    }
}
