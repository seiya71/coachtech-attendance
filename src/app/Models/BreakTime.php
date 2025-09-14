<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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

    public function getStartAttribute()
    {
        return $this->start_time ? Carbon::parse($this->start_time) : null;
    }

    public function getEndAttribute()
    {
        return $this->end_time ? Carbon::parse($this->end_time) : null;
    }

}
