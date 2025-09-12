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
        return Attendance::with(['user', 'breaks'])
            ->whereDate('date', $date)
            ->get();
    }

}
