<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BreakTime;
use App\Models\AttendanceApplication;
use Carbon\Carbon;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
    ];

    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function attendanceRequests()
    {
        return $this->hasMany(AttendanceApplication::class);
    }

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'date' => 'date',
    ];

    public function getClockInFormattedAttribute()
    {
        return $this->clock_in ? Carbon::parse($this->clock_in)->format('H:i') : '-';
    }

    public function getClockOutFormattedAttribute()
    {
        return $this->clock_out ? Carbon::parse($this->clock_out)->format('H:i') : '-';
    }

    public function getBreakTimeFormattedAttribute()
    {
        $minutes = $this->breakTimes->reduce(function ($total, $break) {
            if ($break->start_time && $break->end_time) {
                $total += \Carbon\Carbon::parse($break->end_time)
                    ->diffInMinutes(\Carbon\Carbon::parse($break->start_time));
            }
            return $total;
        }, 0);

        return sprintf('%02d:%02d', floor($minutes / 60), $minutes % 60);
    }

    public function getWorkTimeFormattedAttribute()
    {
        if (!$this->clock_in || !$this->clock_out) {
            return '00:00';
        }

        $workMinutes = \Carbon\Carbon::parse($this->clock_out)
            ->diffInMinutes(\Carbon\Carbon::parse($this->clock_in));

        $breakMinutes = $this->breakTimes->reduce(function ($total, $break) {
            if ($break->start_time && $break->end_time) {
                $total += \Carbon\Carbon::parse($break->end_time)
                    ->diffInMinutes(\Carbon\Carbon::parse($break->start_time));
            }
            return $total;
        }, 0);

        $netMinutes = max(0, $workMinutes - $breakMinutes);

        return sprintf('%02d:%02d', floor($netMinutes / 60), $netMinutes % 60);
    }
}
