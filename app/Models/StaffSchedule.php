<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StaffSchedule extends Model
{
    use HasUuids, BelongsToTenant;

    protected $fillable = [
        'user_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
    ];

    // Relación: el horario pertenece a un usuario (staff)
    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
