<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasUuids, BelongsToTenant;

    protected $fillable = [
        'service_id',
        'staff_user_id',
        'client_user_id',
        'starts_at',
        'ends_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    // Scope: reservas futuras no canceladas
    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>', now())
                     ->where('status', '!=', 'cancelled')
                     ->orderBy('starts_at');
    }
}
