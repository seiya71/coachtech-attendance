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

}
