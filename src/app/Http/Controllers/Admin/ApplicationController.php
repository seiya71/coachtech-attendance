<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceApplication;

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

}
