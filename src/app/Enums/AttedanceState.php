<?php

namespace App\Enums;

enum AttendanceState: string
{
    case OFF_DUTY = '勤務外';
    case WORKING = '勤務中';
    case ON_BREAK = '休憩中';
    case CLOCKED_OUT = '退勤済';
}
