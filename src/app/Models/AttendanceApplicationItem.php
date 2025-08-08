<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AttendanceApplication;
use App\Models\BreakTime;

class AttendanceApplicationItem extends Model
{
    use HasFactory;

    public function request()
    {
        return $this->belongsTo(AttendanceApplication::class);
    }

    public function breakTime()
    {
        return $this->belongsTo(BreakTime::class);
    }

}
