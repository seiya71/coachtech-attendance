<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    protected $fillable = [
        'attendance_id',
        'start_time',
        'end_time',
    ];

    use HasFactory;

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];
}
