<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceApplicationItem;

class AttendanceApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'status',
        'reason',
        'date',
        'clock_in',
        'clock_out'
    ];

    protected $casts = [
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'pending' => '承認待ち',
            'approved' => '承認済み',
            default => '不明',
        };
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function items()
    {
        return $this->hasMany(AttendanceApplicationItem::class);
    }

}
