<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AttendanceApplication;
use App\Models\BreakTime;

class AttendanceApplicationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_application_id',
        'break_time_id',
        'start',
        'end'
    ];

    protected $casts = [
        'start' => 'datetime:H:i',
        'end' => 'datetime:H:i',
    ];

    public function request()
    {
        return $this->belongsTo(AttendanceApplication::class);
    }

    public function breakTime()
    {
        return $this->belongsTo(BreakTime::class);
    }

}
